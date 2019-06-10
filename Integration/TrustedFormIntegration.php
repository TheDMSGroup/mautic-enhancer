<?php
/**
 * Created by PhpStorm.
 * User: nbush
 * Date: 2/11/19
 * Time: 11:56 AM.
 */

namespace MauticPlugin\MauticEnhancerBundle\Integration;

use Doctrine\Common\Collections\ArrayCollection;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\UtmTag;

class TrustedFormIntegration extends AbstractEnhancerIntegration
{
    /* @var \MauticPlugin\MauticEnhancerBundle\Integration\NonFreeEnhancerTrait */
    use NonFreeEnhancerTrait {
        appendToForm as appendNonFreeFields;
    }

    /** @var string */
    const CERT_REAL_HOST = 'cert.trustedform.com';

    /** @var string */
    const CERT_URL_FIELD = 'xx_trusted_form_cert_url';

    /**
     * @return string
     */
    public function getName()
    {
        return 'TrustedForm';
    }

    /**
     * @return string
     */
    public function getDisplayName()
    {
        return 'Trusted Form';
    }

    /**
     * @param Lead $lead
     *
     * @return bool
     */
    public function doEnhancement(Lead $lead)
    {
        $persist = false;
        if ($lead->getFieldValue(self::CERT_URL_FIELD) && !$lead->getFieldValue('trusted_form_created_at')) {
            $parameters = $this->getFingers($lead);
            if ($lead->getId()) {
                $parameters['reference'] = ''.$lead->getId();
                $identifier              = $lead->getId();
            } else {
                $identifier = $lead->getEmail();
            }

            $trustedFormClaim = $lead->getFieldValue(self::CERT_URL_FIELD);
            $parts            = parse_url($trustedFormClaim);
            if (
                !isset($parts['scheme'])
                || 'https' !== $parts['scheme']
                || !isset($parts['host'])
                || self::CERT_REAL_HOST !== $parts['host']
                // Should end with an SHA1 hash
                || !preg_match('/^\/[0-9a-f]{40}$/i', $parts['path'])
            ) {
                $this->logger->error(
                    'TrustedForm: Invalid URL with contact '.$identifier.': '.$trustedFormClaim
                );

                return false;
            }

            /** @var ArrayCollection|array $utmData */
            $utmData = $lead->getUtmTags();
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

            $authKeys = $this->getKeys();
            $settings = [
                'authorize_session' => true,
                'content_type'      => 'application/json',
                'encode_parameters' => 'json',
                'headers'           => ['Accept: application/json'],
                'return_raw'        => true,
                'curl_options'      => [
                    CURLOPT_USERPWD        => "$authKeys[username]:$authKeys[password]",
                    CURLOPT_CONNECTTIMEOUT => 5,
                    CURLOPT_TIMEOUT        => 20,
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_SSL_VERIFYHOST => false,
                ],
            ];

            $tryLimit = 5;
            for ($try = 1; $try <= $tryLimit; ++$try) {
                $response = $this->makeRequest($trustedFormClaim, $parameters, 'post', $settings);
                if (!$response || !isset($response->body)) {
                    $this->logger->error(
                        'TrustedForm: Failed to respond with lead '.$identifier.'. Body: '.(!empty($response->body) ? $response->body : 'null')
                    );
                } else {
                    $data = json_decode($response->body);
                    switch ($response->code) {
                        case 200:
                        case 201:
                        case 410:
                            // Set new value for xx_trusted_form_cert_url from $data->xx_trusted_form_cert_url
                            if (
                                !empty($data->{self::CERT_URL_FIELD})
                                && $data->{self::CERT_URL_FIELD} !== $lead->getFieldValue(self::CERT_URL_FIELD)
                            ) {
                                $lead->addUpdatedField(self::CERT_URL_FIELD, $data->{self::CERT_URL_FIELD});
                                $persist = true;
                            }

                            // Set new value for trusted_form_created_at from created_at
                            if (
                                !empty($data->created_at)
                                && $data->created_at !== $lead->getFieldValue('trusted_form_created_at')
                            ) {
                                $lead->addUpdatedField('trusted_form_created_at', $data->created_at);
                                $persist = true;
                            }

                            // Set new value for trusted_form_expires_at from expires_at
                            if (
                                !empty($data->expires_at)
                                && $data->expires_at !== $lead->getFieldValue('trusted_form_expires_at')
                            ) {
                                $lead->addUpdatedField('trusted_form_expires_at', $data->expires_at);
                                $persist = true;
                            }

                            // Set new value for trusted_form_share_url from share_url
                            if (
                                !empty($data->share_url)
                                && $data->share_url !== $lead->getFieldValue('trusted_form_share_url')
                            ) {
                                $lead->addUpdatedField('trusted_form_share_url', $data->share_url);
                                $persist = true;
                            }
                            $this->logger->info(
                                'TrustedForm: Contact '.$identifier.' '.(!$persist ? 'NOT ' : '').'updated. '.(!empty($data->message) ? $data->message : '')
                            );

                            if (!empty($data->warnings)) {
                                foreach ($data->warnings as $warning) {
                                    $this->logger->error('TrustedForm: Warning with contact '.$identifier.': '.$warning);
                                }
                            }
                            break 2;

                        case 404:
                            $this->logger->error(
                                'TrustedForm: Invalid certificate ('.$trustedFormClaim.') with contact '.$identifier.': '.(!empty($data->message) ? $data->message : '')
                            );
                            break 2;

                        case 401:
                        case 403:
                            $this->logger->error(
                                'TrustedForm: Authentication Failure with contact '.$identifier.': '.(!empty($data->message) ? $data->message : '')
                            );
                            break 2;

                        case 502:
                        case 503:
                            $this->logger->error(
                                'TrustedForm: Exceeded rate limit (try '.$try.'/'.$tryLimit.') with contact '.$identifier.'.'
                            );
                            // 500ms delay before retrying.
                            usleep(250000);
                            break;

                        default:
                            $this->logger->error(
                                'TrustedForm: Unrecognized response code '.(!empty($response->code) ? '('.$response->code.')' : '').' (try '.$try.'/'.$tryLimit.') with contact '.$identifier.': '.(!empty($response->body) ? $response->body : '')
                            );
                            // 500ms delay before retrying.
                            usleep(250000);
                            break;
                    }
                }
            }
        }

        return $persist;
    }

    /**
     * Creates an array of fingerprintable fields.
     *
     * @param Lead $lead
     *
     * @return array
     */
    protected function getFingers(Lead $lead)
    {
        $fingers = [];
        //Trusted form "should" convert these...
        if ($lead->getEmail()) {
            $fingers['email'] = strtolower($lead->getEmail());
        }

        if ($lead->getPhone()) {
            $fingers['phone'] = preg_replace('/\D/', '', $lead->getPhone());
        }

        if ($lead->getMobile()) {
            $fingers['mobile'] = preg_replace('/\D/', '', $lead->getMobile());
        }

        return $fingers;
    }

    /**
     * Get the type of authentication required for this API.  Values can be none, key, oauth2 or callback
     * (will call $this->authenticationTypeCallback).
     *
     * @return string
     */
    public function getAuthenticationType()
    {
        return 'basic';
    }

    /**
     * @param \Symfony\Component\Form\FormBuilderInterface $builder
     * @param array                                        $data
     * @param string                                       $formArea
     */
    public function appendToForm(&$builder, $data, $formArea)
    {
        $this->appendNonFreeFields($builder, $data, $formArea, true);
    }

    /**
     * @returns array[]
     */
    protected function getEnhancerFieldArray()
    {
        return [
            self::CERT_URL_FIELD      => [
                'type'  => 'url',
                'label' => 'Trusted Form Cert',
            ],
            'trusted_form_created_at' => [
                'type'  => 'datetime',
                'label' => 'Trusted Form Cert Claimed',
            ],
            'trusted_form_expires_at' => [
                'type'  => 'datetime',
                'label' => 'Trusted Form Cert Expires',
            ],
            'trusted_form_share_url'  => [
                'type'  => 'url',
                'label' => 'Trusted Form Shareable Cert',
            ],
        ];
    }
}
