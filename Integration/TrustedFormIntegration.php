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
    use NonFreeEnhancerTrait;

    const CERT_URL_FIELD = 'xx_trusted_form_cert_url';

    const CERT_REAL_HOST = 'cert.trustedform.com';

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
     * @returns array[]
     */
    protected function getEnhancerFieldArray()
    {
        return [
            self::CERT_URL_FIELD => [
                'type'  => 'url',
                'label' => 'Trusted Form Cert',
            ],
            'created_at' => [
                'type'  => 'datetime',
                'label' => 'Created At',
            ],
            'expires_at' => [
                'type'  => 'datetime',
                'label' => 'Expires At',
            ],
            'share_url' => [
                'type'  => 'url',
                'label' => 'Shareable Cert',
            ],
        ];
    }

    /**
     * @param Lead $lead
     *
     * @return bool
     */
    public function doEnhancement(Lead $lead)
    {
        if ($lead->getFieldValue(self::CERT_URL_FIELD) && !$lead->getFieldValue('share_url')) {
            $trustedFormClaim = $lead->getFieldValue(self::CERT_URL_FIELD);

            $parts = parse_url($trustedFormClaim);

            if ('https' !== $parts['scheme'] || self::CERT_REAL_HOST !== $parts['host']) {
                $this->logger->warning('Not Processing Suspicious TrustedForm URL: '.$trustedFormClaim);

                return false;
            }

            $parameters = $this->getFingers($lead);

            if ($lead->getId()) {
                $parameters['reference'] = ''.$lead->getId();
            }

            /** @var ArrayCollection $utmData */
            $utmData = $lead->getUtmTags();
            if (!$utmData->isEmpty()) {
                /** @var UtmTag $utmTag */
                $utmTag = $utmData->last();
                if ($utmTag->getUtmSource()) {
                    $parameters['vendor'] = $utmTag->getUtmSource();
                }
            }

            $authKeys = $this->getKeys();
            $settings = [
                'authorize_session' => true,
                'content_type'      => 'application/json',
                'encode_parameters' => 'json',
                'headers'           => ['Accept: application/json'],
                'return_raw'        => true,
                'curl_options'      => [
                    CURLOPT_USERPWD => "$authKeys[username]:$authKeys[password]",
                ],
            ];

            $message = 'Number of request types exceeded';
            for ($try = 0; $try < 5; ++$try) {
                $response = $this->makeRequest($trustedFormClaim, $parameters, 'post', $settings);
                $data     = json_decode($response->body);
                switch ($response->code) {
                    case 201:
                        $fieldsToSet = array_keys($this->getEnhancerFieldArray());
                        foreach ($fieldsToSet as $field) {
                            $lead->addUpdatedField($field, $data->$field);
                        }

                        $prefix = 'TrustedForm['.$lead->getId().'] ';
                        foreach ($data->warnings as $warning) {
                            $this->logger->warning($prefix.$warning);
                        }
                        if (isset($data->message)) {
                            $this->logger->info($prefix.$data->message);
                        }

                        return true;
                    case 404:
                        $message = 'Invalid Certificate: '.$data->message;
                        break 2;
                    case 401:
                    case 403:
                        $message = 'Authentication Failure: '.$data->message;
                        break 2;
                    case 502:
                    case 503:
                        usleep(100);
                        break;
                    default:
                        $message = "Unrecognized response code: $response->code $data->message";
                        break 2;
                }
            }
            $this->logger->info($message);
        }

        return false;
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
}
