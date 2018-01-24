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

    public function appendToForm(&$builder, $data, $formArea)
    {
        if ('features' === $formArea) {
            $builder
                ->add('validatePhone',
                    'yesno_button_group',
                    [
                        'label' => 'mautic.integration.xverify.validate_phone.label',
                        'data'  => !isset($data['validatePhone']) ? false : $data['validatePhone'],
                        'required'    => false,
                        'empty_value' => false,
                        'label_attr'  => ['class' => 'control-label'],
                        'attr'        => [
                            'class' => 'form-control',
                            'tooltip' => 'mautic.integration.xverify.validate_phone.tooltip',
                        ],
                    ])
                ->add('validateEmail',
                    'yesno_button_group',
                    [
                        'label' => 'mautic.integration.xverify.validate_email.label',
                        'data'  => !isset($data['validatePhone']) ? false : $data['validateEmail'],
                        'required'    => false,
                        'empty_value' => false,
                        'label_attr'  => ['class' => 'control-label'],
                        'attr'        => [
                            'class' => 'form-control',
                            'tooltip' => 'mautic.integration.xverify.validate_email.tooltip',
                        ],
                    ]
                );
        }
    }

    protected function getEnhancerFieldArray()
    {
        return [];
    }

    public function doEnhancement(Lead $lead)
    {
        if ($this->getIsPublished()) {

            $keys = $this->getDecryptedApiKeys();
            $params = [
                'key' => $keys[apikey],
            ];

            $params = array_merge(
                $params,
                $this->getFeatureSettings()
            );

            $params['tn'] = $lead->getPhone();

            $response = $this->makeRequest(
                $keys['server'],
                ['append_to_query' => $params]
            );

            error_log(print_r($response, true));
        }
    }

}