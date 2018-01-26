<?php
/*
 * @author      Scott Shipman
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 *
 * Allow verification of a lead's email address using X-verify on a configurable
 * list of campaigns
 */

// TODO Hook up t the plugin stats

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
        return ['email_is_valid' => ['label' => 'emailIsValid', 'type'  => 'boolean'],
                'work_phone_is_valid' => ['label' => 'work_phoneIsValid', 'type'  => 'boolean'],
                'cell_phone_is_valid' => ['label' => 'cell_phoneIsValid', 'type'  => 'boolean'],
                'home_phone_is_valid' => ['label' => 'home_phoneIsValid', 'type'  => 'boolean'],
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
            $persist = false;

            foreach ($contactFieldMapping as $integrationFieldName => $mauticFieldName) {
              $response = $status = $service = $fieldKey = NULL;
              $fieldToUpdate = $integrationFieldName . '_is_valid'; //which validation field will we update?
              try {
                $fieldValue = $lead->$mauticFieldName;
                if(!empty($fieldValue)){
                  switch ($integrationFieldName) {
                    case "cell_phone":
                    case "home_phone":
                    case "work_phone":
                      // phone API call
                      $service = "phone";
                      $fieldKey = "phone";
                      if(is_null($lead->$fieldToUpdate)){ // only if we havent checked already
                        $response = $this->makeCall($service, $params, $fieldKey, $fieldValue);
                        $status = $this->getResponseStatus($response, $fieldKey);
                        if(!is_null($status)){
                          $lead->addUpdatedField($fieldToUpdate, $status);
                          $persist = true;
                        }
                      }
                      break;

                    case "email":
                      // email API call
                      $service = "emails";
                      $fieldKey = "email";
                      if(is_null($lead->$fieldToUpdate)){ // only if we havent checked already
                        $response = $this->makeCall($service, $params, $fieldKey, $fieldValue);
                        $status = $this->getResponseStatus($response, $fieldKey);
                        if(!is_null($status)){
                          $lead->addUpdatedField($fieldToUpdate, $status, null);
                          $persist = true;
                          $lead->__set('city','chicago');
                       //   $lead->__set($fieldToUpdate, $status);
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
          if($persist){
              $this->em->persist($lead);
              $this->em->flush();
            } // TODO why wont custom fields persist to DB?
        }
    }


    protected function makeCall($service, $params, $fieldKey, $fieldValue) {

      // the response object has a lot of value-add data, that may help to enhance lead data, for a future feature request

      // set a timeout default to 20 seconds
      $settings = ['curl_options' => [CURLOPT_CONNECTTIMEOUT => 20, CURLOPT_TIMEOUT => 20]];

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
      $status = NULL; // default because if we cant get it, its because its invalid
      if(!empty($response) && !empty($fieldKey)){
        if( isset($response[$fieldKey]['status']) && !empty($response[$fieldKey]['status'])){
          $status = $response[$fieldKey]['status'] == "valid" ? 1 : 0;
        }
      }
      return $status;

    }
}