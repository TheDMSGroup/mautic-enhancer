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
    const INTEGRATION_NAME = 'Xverify';

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
        return self::INTEGRATION_NAME;
    }

    /**
     * @return string
     */
    public function getDisplayName()
    {
        return 'Email Validation with '.self::INTEGRATION_NAME;

        //return self::INTEGRATION_NAME . ' Data Enhancer';
    }

    /**
     * @return array
     */
    public function getSupportedFeatures()
    {
        return [
            'push_lead',
        ];
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
     * @param \Symfony\Component\Form\FormBuilder $builder
     * @param array                               $data
     * @param string                              $formArea
     */
    public function appendToForm(&$builder, $data, $formArea)
    {
        $this->appendCostToForm($builder, $data, $formArea);
    }

    /**
     * @param array $settings
     *
     * @return array
     */
    public function getAvailableLeadFields($settings = [])
    {
        return [
            'email'     => ['type' => 'string'],
            'homephone' => ['type' => 'string'],
            'cellphone' => ['type' => 'string'],
            'workphone' => ['type' => 'string'],
        ];
    }

    /**
     * @return array|mixed
     */
    protected function getEnhancerFieldArray()
    {
        $object = class_exists(
            'MauticPlugin\MauticExtendedFieldBundle\MauticExtendedFieldBundle'
        ) ? 'extendedField' : 'lead';

        return [
            'email_valid'     => ['label' => 'emailIsValid', 'type' => 'boolean', 'object' => $object],
            'workphone_valid' => ['label' => 'work_phoneIsValid', 'type' => 'boolean', 'object' => $object],
            'cellphone_valid' => ['label' => 'cell_phoneIsValid', 'type' => 'boolean', 'object' => $object],
            'homephone_valid' => ['label' => 'home_phoneIsValid', 'type' => 'boolean', 'object' => $object],
        ];
    }

    /**
     * @param Lead $lead
     *
     * @return mixed|void
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Exception
     */
    public function doEnhancement(Lead $lead)
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
                    $fieldValue = $lead->$mauticFieldName;
                    if (!empty($fieldValue)) {
                        switch ($integrationFieldName) {
                            case 'cellphone':
                            case 'homephone':
                            case 'workphone':
                                // phone API call
                                $service  = 'phone';
                                $fieldKey = 'phone';
                                if (is_null($lead->$fieldToUpdate)) { // only if we havent checked already
                                    $response = $this->makeCall($service, $params, $fieldKey, $fieldValue);
                                    $status   = $this->getResponseStatus($response, $fieldKey);
                                    if (!is_null($status)) {
                                        $lead->addUpdatedField($fieldToUpdate, $status);
                                        $persist = true;
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
                                if (is_null($lead->$fieldToUpdate)) { // only if we havent checked already
                                    $response = $this->makeCall($service, $params, $fieldKey, $fieldValue);
                                    $status   = $this->getResponseStatus($response, $fieldKey);
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
                    throw $e;
                }
            }
            if ($persist) {
                $this->em->persist($lead);
                $this->em->flush();
            } // TODO why wont custom fields persist to DB?
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
