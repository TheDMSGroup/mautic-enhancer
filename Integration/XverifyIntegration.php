<?php
/*
 * @author      Scott Shipman
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 *
 * Allow verification of a lead's email address using X-verify on a configurable
 * list of campaigns
 */

namespace MauticPlugin\MauticEnhancerBundle\Integration;

use Mautic\LeadBundle\Entity\Lead;
use Mautic\CampaignBundle\Entity\Campaign;

class XverifyIntegration extends AbstractEnhancerIntegration
{
    const INTEGRATION_NAME = 'Xverify';

    public function getAuthenticationType()
    {
        return 'keys';
    }

    public function getName()
    {
        return self::INTEGRATION_NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function getDisplayName()
    {
        return self::INTEGRATION_NAME . ' Data Enhancer';
    }

    public function getSupportedFeatures()
    {
        return [
            'push_lead',
        ];
    }

    public function getRequiredKeyFields()
    {
        return [
            'server' => 'mautic.integration.xverify.server.label',
            'apikey' => 'mautic.integration.xverify.apikey.label'
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


    public function getAvailableLeadFields($settings = [])
    {
        return [
            'email'      => ['type' => 'string'],
            'home_phone' => ['type' => 'string'],
            'cell_phone' => ['type' => 'string'],
            'work_phone' => ['type' => 'string'],
        ];
    }

    protected function getEnhancerFieldArray()
    {
        return ['emailIsValid' => ['label' => 'emailIsValid', 'type'  => 'string'],
                'work_phoneIsValid' => ['label' => 'work_phoneIsValid', 'type'  => 'string'],
                'cell_phoneIsValid' => ['label' => 'cell_phoneIsValid', 'type'  => 'string'],
                'home_phoneIsValid' => ['label' => 'home_phoneIsValid', 'type'  => 'string'],
          ];
    }

    public function doEnhancement(Lead $lead)
    {
        if (!empty($lead)) {
            $settings = $this->getIntegrationSettings()->getFeatureSettings();
            $contactFieldMapping = $settings['leadFields'];
            $keys = $this->getDecryptedApiKeys();

            $params = [
              'apikey' => $keys['apikey'],
              'domain' => $keys['server'],
              'type' => 'json',
            ];


            foreach ($contactFieldMapping as $integrationFieldName => $mauticFieldName) {
              try {
                $fieldValue = $lead->$mauticFieldName;
                switch ($integrationFieldName) {
                  case "cell_phone":
                  case "home_phone":
                  case "work_phone":
                    // phone API call
                    $service = "phone";
                    $fieldKey = "phone";
                    $response = $this->makeCall($service, $params, $fieldKey, $fieldValue);
                    $status = $this->getResponseStatus($response, $fieldKey);
                    $lead->addUpdatedField($integrationFieldName . 'IsValid', $status);
                    break;

                  case "email":
                    // email API call
                    $service = "emails";
                    $fieldKey = "email";
                    $response = $this->makeCall($service, $params, $fieldKey, $fieldValue);
                    $status = $this->getResponseStatus($response, $fieldKey);
                    $lead->addUpdatedField($integrationFieldName . 'IsValid', $status);
                    break;

                  default:      // no matching case
                    continue; // dont do anything - go to next loop iteration
                }
              } catch (\Exception $e) {
                  $this->logIntegrationError($e);
                  throw $e;
              }
            }
        }
    }


    protected function makeCall($service, $params, $fieldKey, $fieldValue) {

      // the response object has a lot of value-add data, that may help to enhance lead data, for a future feature request

      // set a timeout default to 20 seconds
      $settings = ['curl_options' => ['CURLOPT_CONNECTTIMEOUT' => 20]];

      $url = "http://www.xverify.com/services/$service/verify/?$fieldKey=$fieldValue"; // valid entries for service: "emails", "phone", "address"
      $response = $this->makeRequest(
        $url,
        ['append_to_query' => $params],
        'GET',
        $settings
      );
      return $response;

    }

    protected function getResponseStatus($response, $fieldKey){
      $status = 'invalid'; // default because if we cant get it, its because its invalid
      if(!empty($response) && !empty($fieldKey)){
        $status = $response[$fieldKey]['status'];
      }

      return $status;

    }
}