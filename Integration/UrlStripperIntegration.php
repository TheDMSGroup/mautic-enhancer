<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Digital Media Solutions, LLC
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticEnhancerBundle\Integration;

use Mautic\CoreBundle\Form\Type\YesNoButtonGroupType;
use Mautic\LeadBundle\Entity\Lead;

/**
 * Class UrlStripperIntegration.
 *
 * Allow verification of a lead's email address using X-verify on a configurable
 * list of campaigns.
 *
 * @todo Hook up to the plugin stats
 */
class UrlStripperIntegration extends AbstractEnhancerIntegration
{
    /**
     * @return string
     */
    public function getAuthenticationType()
    {
        return 'none';
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'UrlStripper';
    }

    /**
     * @return string
     */
    public function getDisplayName()
    {
        return 'Normalize Consent URL with '.$this->getName();
    }

    /**
     * @return array|mixed
     */
    protected function getEnhancerFieldArray()
    {
        return [
            'consent_url_clean' => [
                'label' => 'Consent URL - Cleaned',
                'type'  => 'text',
            ],
        ];
    }

    /**
     * @return array
     */
    public function getSupportedFeatures()
    {
        return ['push_lead'];
    }

    /**
     * @param Lead $lead
     *
     * @return bool
     */
    public function doEnhancement(Lead $lead)
    {
        $persist = false;
        if (!empty($lead)) {
            $settings                = $this->getIntegrationSettings()->getFeatureSettings();
            $originalConsentUrlField = $settings['original_consent_url'];

            try {
                $fieldValue = $lead->getFieldValue($originalConsentUrlField);
                if (!empty($fieldValue)) {
                    // remove superfilious params
                    $cleanFieldValue = strtok($fieldValue, '?');
                    $lead->addUpdatedField('consent_url_clean', $cleanFieldValue);
                    $this->logger->addDebug(
                        'CONSENT URL NORMALIZER: verification values to update: '.$fieldValue.' => '.$cleanFieldValue
                    );
                    $persist = true;
                }
            } catch (\Exception $e) {
                $this->logIntegrationError($e);

                return false;
            }
        }

        return $persist;
    }

    /**
     * @param \Symfony\Component\Form\FormBuilder $builder
     * @param array                               $data
     * @param string                              $formArea
     */
    public function appendToForm(
        &$builder,
        $data,
        $formArea
    ) {
        // add a settings field for mapping what is the consent URL field for later retreival

        $fields = $this->factory->getModel('lead')->getRepository()->getCustomFieldList('lead');
        $choices=[];
        foreach ($fields[0] as $key=>$field) {
            $choices[$key] = $field['label'];
        }

        if ('features' === $formArea) {
            $builder->add(
                'autorun_enabled',
                YesNoButtonGroupType::class,
                [
                    'label'       => $this->translator->trans('mautic.enhancer.autorun.label'),
                    'data'        => !isset($data['autorun_enabled']) ? false : $data['autorun_enabled'],
                    'required'    => true,
                    'empty_value' => false,
                    'label_attr'  => ['class' => 'control-label'],
                    'attr'        => [
                        'class'   => 'form-control',
                        'tooltip' => $this->translator->trans('mautic.enhancer.autorun.tooltip'),
                    ],
                ]
            );
            $builder
                ->add(
                    'original_consent_url',
                    'choice',
                    [
                        'choices'     => $choices,
                        'label'       => $this->translator->trans(
                            'mautic.enhancer.integration.consentUrlClean.field.label'
                        ),
                        'data'        => isset($data['original_consent_url']) ? $data['original_consent_url'] : 'select a field',
                        'required'    => true,
                        'empty_value' => false,
                        'label_attr'  => ['class' => 'control-label'],
                        'attr'        => [
                            'class'   => 'form-control',
                            'tooltip' => $this->translator->trans(
                                'mautic.enhancer.integration.consentUrlClean.field.tooltip'
                            ),
                        ],
                    ]
                );
        }
    }
}
