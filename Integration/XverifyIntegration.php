<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Scott Shipman
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticEnhancerBundle\Integration;

use Mautic\LeadBundle\Entity\Lead;

/**
 * Class XverifyIntegration.
 *
 * Allow verification of a lead's email address using X-verify on a configurable
 * list of campaigns.
 *
 * @todo Hook up to the plugin stats
 */
class XverifyIntegration extends AbstractEnhancerIntegration implements NonFreeEnhancerInterface
{
    /* @var \MauticPlugin\MauticEnhancerBundle\Integration\NonFreeEnhancerTrait */
    use NonFreeEnhancerTrait;

    /**
     * @return string
     */
    public function getAuthenticationType()
    {
        return 'keys';
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'Xverify';
    }

    /**
     * @return string
     */
    public function getDisplayName()
    {
        return 'Email Validation with '.$this->getName();
    }

    /**
     * @return array
     */
    public function getRequiredKeyFields()
    {
        return [
            'server' => 'mautic.integration.xverify.server.label',
            'apikey' => 'mautic.integration.xverify.apikey.label',
        ];
    }

    /**
     * Get the array key for clientId.
     *
     * @return string
     */
    public function getClientIdKey()
    {
        return 'mautic.integration.xverify.server.label';
    }

    /**
     * Get the array key for client secret.
     *
     * @return string
     */
    public function getClientSecretKey()
    {
        return 'mautic.integration.xverify.apikey.label';
    }

    /**
     * @param array $settings
     *
     * @return array
     */
    public function getAvailableLeadFields($settings = [])
    {
        return [
            'email'     => ['type' => 'text'],
            'homephone' => ['type' => 'text'],
            'cellphone' => ['type' => 'text'],
            'workphone' => ['type' => 'text'],
        ];
    }

    /**
     * @return array|mixed
     */
    protected function getEnhancerFieldArray()
    {
        return [
            'email_valid'     => [
                'label' => 'Xverify&quot;d Email',
                'type'  => 'boolean',
            ],
            'workphone_valid' => [
                'label' => 'Xverify&quot;d Work Phone',
                'type'  => 'boolean',
            ],
            'cellphone_valid' => [
                'label' => 'Xverify&quot;d Mobile Phone',
                'type'  => 'boolean',
            ],
            'homephone_valid' => [
                'label' => 'Xverify&quot;d Home Phone',
                'type'  => 'boolean',
            ],
        ];
    }

    /**
     * @param Lead $lead
     *
     * @return mixed|void
     *
     * @throws \Exception
     */
    public function doEnhancement(Lead &$lead)
    {
        if (!empty($lead)) {
            $settings            = $this->getIntegrationSettings()->getFeatureSettings();
            $contactFieldMapping = $settings['leadFields'];
            $keys                = $this->getDecryptedApiKeys();

            $params  = [
                'apikey' => $keys['apikey'],
                'domain' => $keys['server'],
                'type'   => 'json',
            ];
            $persist = false;

            foreach ($contactFieldMapping as $integrationFieldName => $mauticFieldName) {
                $response      = $status = $service = $fieldKey = null;
                $fieldToUpdate = $integrationFieldName.'_valid'; //which validation field will we update?
                try {
                    $fieldValue = $lead->getFieldValue($mauticFieldName);
                    if (!empty($fieldValue)) {
                        switch ($integrationFieldName) {
                            case 'cellphone':
                            case 'homephone':
                            case 'workphone':
                                // phone API call
                                $service  = 'phone';
                                $fieldKey = 'phone';
                                if (is_null(
                                    $lead->getFieldValue($fieldToUpdate)
                                )) { // only if we havent checked already
                                    $response = $this->makeCall($service, $params, $fieldKey, $fieldValue);
                                    $this->applyCost($lead);
                                    $persist = true;
                                    $status  = $this->getResponseStatus($response, $fieldKey);
                                    if (!is_null($status)) {
                                        $lead->addUpdatedField($fieldToUpdate, $status);
                                        $this->logger->addDebug(
                                            'XVERIFY: verification values to update: '.$fieldToUpdate.' => '.$status
                                        );
                                    }
                                }
                                break;

                            case 'email':
                                // email API call
                                $service  = 'emails';
                                $fieldKey = 'email';
                                if (is_null(
                                    $lead->getFieldValue($fieldToUpdate)
                                )) { // only if we havent checked already
                                    $response = $this->makeCall($service, $params, $fieldKey, $fieldValue);
                                    $this->applyCost($lead);
                                    $persist = true;
                                    $status  = $this->getResponseStatus($response, $fieldKey);
                                    if (!is_null($status)) {
                                        $lead->addUpdatedField($fieldToUpdate, $status, null);
                                        $persist = true;
                                        $this->logger->addDebug(
                                            'XVERIFY: verification values to update: '.$fieldToUpdate.' => '.$status
                                        );
                                    }
                                }
                                break;

                            default:      // no matching case
                                continue; // dont do anything - go to next loop iteration
                        }
                    }
                } catch (\Exception $e) {
                    $this->logIntegrationError($e);
                    if ($persist) {
                        // We don't want to potentially lose track of a cost.
                        $this->saveLead($lead);
                    }
                    throw $e;
                }
            }

            if ($persist) {
                $this->saveLead($lead);
            }
        }
    }

    /**
     * @param $service
     * @param $params
     * @param $fieldKey
     * @param $fieldValue
     *
     * @return mixed|string
     */
    protected function makeCall($service, $params, $fieldKey, $fieldValue)
    {
        // the response object has a lot of value-add data, that may help to enhance lead data, for a future feature request

        // @todo - Update to use Guzzle.

        // set a timeout default to 20 seconds
        $settings = ['curl_options' => [CURLOPT_CONNECTTIMEOUT => 20, CURLOPT_TIMEOUT => 20]];

        $url      = "http://www.xverify.com/services/$service/verify/?$fieldKey=$fieldValue"; // valid entries for service: "emails", "phone", "address"
        $response = $this->makeRequest(
            $url,
            ['append_to_query' => $params],
            'GET',
            $settings
        );

        return $response;
    }

    /**
     * @param $response
     * @param $fieldKey
     *
     * @return int|null
     */
    protected function getResponseStatus($response, $fieldKey)
    {
        $status = null; // default because if we cant get it, its because its invalid
        if (!empty($response) && !empty($fieldKey)) {
            if (isset($response[$fieldKey]['status']) && !empty($response[$fieldKey]['status'])) {
                $status = $response[$fieldKey]['status'] == 'valid' ? 1 : 0;
            }
        }

        return $status;
    }
}
