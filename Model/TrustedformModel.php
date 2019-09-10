<?php

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Digital Media Solutions, LLC
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticEnhancerBundle\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Mautic\CoreBundle\Model\AbstractCommonModel;
use Mautic\LeadBundle\Entity\Lead as Contact;
use Mautic\LeadBundle\Model\LeadModel as ContactModel;
use MauticPlugin\MauticEnhancerBundle\Entity\PluginEnhancerTrustedform;
use MauticPlugin\MauticEnhancerBundle\Entity\PluginEnhancerTrustedformRepository;
use MauticPlugin\MauticEnhancerBundle\Integration\TrustedformIntegration;
use Symfony\Component\Console\Output\OutputInterface;

class TrustedformModel extends AbstractCommonModel
{
    /** @var string */
    const CERT_REAL_HOST = 'cert.trustedform.com';

    /** @var string */
    const CERT_URL_FIELD = 'xx_trusted_form_cert_url';

    /** @var bool */
    protected $realtime = false;

    /** @var array */
    protected $keys;

    /** @var array Statuses that can be reattempted, 0 being the default */
    protected $statusesToAttempt = [0, 500, 502, 503];

    /** @var ContactModel */
    protected $contactModel;

    /**
     * TrustedformModel constructor.
     *
     * @param ContactModel $contactModel
     */
    public function __construct(
        ContactModel $contactModel
    ) {
        $this->contactModel = $contactModel;
    }

    /**
     * @param TrustedformIntegration $integration
     */
    public function setup(TrustedformIntegration $integration)
    {
        $settings       = $integration->getIntegrationSettings()->getFeatureSettings();
        $this->keys     = $integration->getKeys();
        $this->realtime = (bool) $settings['realtime'];
    }

    /**
     * Claim certificates in bulk in the background.
     *
     * @param int                  $threadId
     * @param int                  $maxThreads
     * @param int                  $batchLimit
     * @param int                  $attemptLimit
     * @param OutputInterface|null $output
     */
    public function claimCertificates(
        int $threadId = 1,
        int $maxThreads = 1,
        int $batchLimit = 100,
        int $attemptLimit = 10,
        OutputInterface $output = null
    ) {
        while ($entities = $this->getRepository()->findBatchToClaim(
            $threadId,
            $maxThreads,
            $attemptLimit,
            $batchLimit,
            $this->statusesToAttempt
        )) {
            /** @var PluginEnhancerTrustedform $entity */
            foreach ($entities as $entity) {
                $persist = $this->makeRequestAndPersist($entity, $attemptLimit);
                if ($output) {
                    $output->write($persist ? '.' : '!');
                }
            }
        }
    }

    /**
     * @return PluginEnhancerTrustedformRepository
     */
    public function getRepository()
    {
        return $this->em->getRepository($this->getEntityName());
    }

    /**
     * @return string
     */
    public function getEntityName()
    {
        return PluginEnhancerTrustedform::class;
    }

    /**
     * @param PluginEnhancerTrustedform $entity
     * @param int                       $attemptLimit
     *
     * @return bool
     */
    private function makeRequestAndPersist(PluginEnhancerTrustedform $entity, int $attemptLimit)
    {
        if ($persist = $this->makeApiRequest($entity, $attemptLimit)) {
            // We should persist changes to the contact now.
            $this->contactModel->saveEntity($entity->getContact());
        }

        return $persist;
    }

    /**
     * @param PluginEnhancerTrustedform $entity
     * @param int                       $attemptLimit
     *
     * @return bool
     */
    protected function makeApiRequest(PluginEnhancerTrustedform $entity, int $attemptLimit = 10)
    {
        $persist        = false;
        $certificateUrl = 'https://'.self::CERT_REAL_HOST.'/'.$entity->getToken();
        $contact        = $entity->getContact();
        $identifier     = $this->getIdentifier($contact);
        $settings       = [
            'authorize_session' => true,
            'content_type'      => 'application/json',
            'encode_parameters' => 'json',
            'headers'           => ['Accept: application/json'],
            'return_raw'        => true,
            'curl_options'      => [
                CURLOPT_USERPWD        => $this->keys['username'].':'.$this->keys['password'],
                CURLOPT_CONNECTTIMEOUT => 5,
                CURLOPT_TIMEOUT        => 10,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
            ],
        ];

        $parameters = $this->getParameters($contact);

        $attemptLimit = $attemptLimit - (int) $entity->getAttempts();
        for ($attempt = 1; $attempt <= $attemptLimit; ++$attempt) {
            $entity->setAttempts($entity->getAttempts() + 1);
            try {
                $response = $this->makeRequest($certificateUrl, $parameters, 'post', $settings);
                $entity->setStatus((int) $response->code);

                if (!$response || !isset($response->body)) {
                    $this->logger->error(
                        'TrustedForm: Failed to respond with lead '.$identifier.'. Body: '.(!empty($response->body) ? $response->body : 'null')
                    );
                } else {
                    $data = json_decode($response->body);
                    switch ($response->code) {
                        case 410:
                            $this->logger->error(
                                'TrustedForm: Certificate already expired ('.$certificateUrl.') with contact '.$identifier.': '.(!empty($data->expired_at) ? $data->expired_at : '')
                            );
                            break;

                        case 200:
                        case 201:
                            // Set new value for xx_trusted_form_cert_url from $data->xx_trusted_form_cert_url
                            if (
                                !empty($data->{self::CERT_URL_FIELD})
                                && $data->{self::CERT_URL_FIELD} !== $contact->getFieldValue(self::CERT_URL_FIELD)
                            ) {
                                $contact->addUpdatedField(self::CERT_URL_FIELD, $data->{self::CERT_URL_FIELD});
                                $persist = true;
                            }

                            // Set new value for trusted_form_created_at from created_at
                            if (
                                !empty($data->created_at)
                                && $data->created_at !== $contact->getFieldValue('trusted_form_created_at')
                            ) {
                                $contact->addUpdatedField('trusted_form_created_at', $data->created_at);
                                $persist = true;
                            }

                            // Set new value for trusted_form_expires_at from expires_at
                            if (
                                !empty($data->expires_at)
                                && $data->expires_at !== $contact->getFieldValue('trusted_form_expires_at')
                            ) {
                                $contact->addUpdatedField('trusted_form_expires_at', $data->expires_at);
                                $persist = true;
                            }

                            // Set new value for trusted_form_share_url from share_url
                            if (
                                !empty($data->share_url)
                                && $data->share_url !== $contact->getFieldValue('trusted_form_share_url')
                            ) {
                                $contact->addUpdatedField('trusted_form_share_url', $data->share_url);
                                $persist = true;
                            }

                            if ($persist) {
                                $this->logger->info(
                                    'TrustedForm: Contact '.$identifier.' updated. '.(!empty($data->message) ? $data->message : '')
                                );
                            }

                            if (!empty($data->warnings)) {
                                foreach ($data->warnings as $warning) {
                                    $this->logger->error(
                                        'TrustedForm: Warning with contact '.$identifier.': '.$warning
                                    );
                                }
                            }
                            break;

                        case 404:
                            $this->logger->error(
                                'TrustedForm: Invalid certificate ('.$certificateUrl.') with contact '.$identifier.': '.(!empty($data->message) ? $data->message : '')
                            );
                            break;

                        case 401:
                        case 403:
                            $this->logger->error(
                                'TrustedForm: Authentication Failure with contact '.$identifier.': '.(!empty($data->message) ? $data->message : '')
                            );
                            break;

                        case 500:
                            $this->logger->error(
                                'TrustedForm: Error with contact '.$identifier.': '.(!empty($data->message) ? $data->message : '')
                            );
                            break;

                        case 502:
                        case 503:
                            $this->logger->error(
                                'TrustedForm: Exceeded rate limit ('.$attempt.'/'.$attemptLimit.') with contact '.$identifier.'.'
                            );
                            if ($attempt < $attemptLimit) {
                                sleep(1);
                            }
                            break;

                        default:
                            $this->logger->error(
                                'TrustedForm: Unrecognized response code '.(!empty($response->code) ? '('.$response->code.')' : '').' ('.$attempt.'/'.$attemptLimit.') with contact '.$identifier.': '.(!empty($response->body) ? $response->body : '')
                            );
                            break;
                    }
                }
            } catch (\Exception $e) {
                $this->logger->error(
                    'TrustedForm: Unexpected exception '.$identifier.'. '.$e->getMessage()
                );
                $entity->setStatus(500);
            }

            // If the status is not in a retryable state, abort further attempts.
            if (!in_array($entity->getStatus(), $this->statusesToAttempt)) {
                break;
            }
        }
        $this->getRepository()->saveEntity($entity);

        return $persist;
    }

    private function getIdentifier(Contact $contact)
    {
        if ($contact->getId()) {
            return (string) $contact->getId();
        } else {
            return (string) $contact->getEmail();
        }
    }

    /**
     * @param Contact $contact
     *
     * @return array
     */
    protected function getParameters(Contact $contact)
    {
        $parameters = [];

        if ($contact->getEmail()) {
            $parameters['email'] = strtolower($contact->getEmail());
        }

        if ($contact->getPhone()) {
            $parameters['phone'] = preg_replace('/\D/', '', $contact->getPhone());
        }

        if ($contact->getMobile()) {
            $parameters['mobile'] = preg_replace('/\D/', '', $contact->getMobile());
        }

        if ($contact->getId()) {
            $parameters['reference'] = (string) $contact->getId();
        }

        $parameters['vendor'] = '';

        /** @var ArrayCollection|array $utmData */
        $utmData = $contact->getUtmTags();

        // Get the UTM Tags as an array of entities.
        if ($utmData instanceof ArrayCollection) {
            $utmData = $utmData->toArray();
        }
        if (is_array($utmData) && !empty($utmData)) {
            // Get the last UTM Source.
            $utmSources = [];
            /** @var UtmTag $utmTag */
            foreach ($utmData as $utmTag) {
                if (!empty(trim($utmTag->getUtmSource()))) {
                    $utmSources[$utmTag->getDateAdded()->getTimestamp()] = $utmTag->getUtmSource();
                }
            }
            ksort($utmSources);
            $parameters['vendor'] = array_pop($utmSources);
        }

        return $parameters;
    }

    /**
     * @param Contact $contact
     *
     * @return array
     *
     * @throws \Exception
     */
    public function queueContact(Contact $contact)
    {
        $entities   = [];
        $fieldValue = $contact->getFieldValue(self::CERT_URL_FIELD);
        if ($fieldValue && !$contact->getFieldValue('trusted_form_created_at')) {
            $fieldValue      = trim(str_replace([',', ';', "\r\n", "\t", "\n"], ' ', $fieldValue));
            $certificateUrls = explode(' ', $fieldValue);
            foreach ($certificateUrls as $certificateUrl) {
                $certificateUrl = trim($certificateUrl);
                $parts          = parse_url($certificateUrl);
                if (
                    !isset($parts['scheme'])
                    || 'https' !== $parts['scheme']
                    || !isset($parts['host'])
                    || self::CERT_REAL_HOST !== $parts['host']
                    || !isset($parts['path'])
                    || !preg_match('/^\/[0-9a-f]{40}$/i', $parts['path'])
                ) {
                    $this->logger->error(
                        'TrustedForm: Invalid URL with contact '.$this->getIdentifier($contact).': '.$certificateUrl
                    );
                } else {
                    $entity = new PluginEnhancerTrustedform();
                    $entity->setDateAdded(new \DateTime());
                    $entity->setContact($contact);
                    $entity->setToken($parts['path']);
                    $this->em->persist($entity);
                    if ($this->realtime && 'cli' !== php_sapi_name()) {
                        // Limit realtime attempts to 1 time per request.
                        $persist = $this->makeRequestAndPersist($entity, 1);
                    }
                    $entities[] = $entity;
                }
            }
        }

        return $entities;
    }
}
