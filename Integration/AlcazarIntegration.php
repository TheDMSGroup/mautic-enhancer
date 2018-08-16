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

use Mautic\LeadBundle\Entity\Lead;

/**
 * Class AlcazarIntegration.
 */
class AlcazarIntegration extends AbstractEnhancerIntegration
{
    /* @var \MauticPlugin\MauticEnhancerBundle\Integration\NonFreeEnhancerTrait */
    use NonFreeEnhancerTrait {
        appendToForm as appendNonFreeFields;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'Alcazar';
    }

    /**
     * @return string
     */
    public function getAuthenticationType()
    {
        return 'keys';
    }

    /**
     * @return array
     */
    public function getRequiredKeyFields()
    {
        return [
            'server' => $this->translator->trans('mautic.enhancer.integration.alcazar.server.label'),
            'apikey' => $this->translator->trans('mautic.enhancer.integration.alcazar.apikey.label'),
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
     * @param \Symfony\Component\Form\FormBuilderInterface $builder
     * @param array                                        $data
     * @param string                                       $formArea
     */
    public function appendToForm(&$builder, $data, $formArea)
    {
        if ('features' === $formArea) {
            $builder
                ->add(
                    'output',
                    'choice',
                    [
                        'choices'     => [
                            'json' => 'JSON',
                            'xml'  => 'XML',
                            'text' => 'text',
                        ],
                        'label'       => $this->translator->trans('mautic.enhancer.integration.alcazar.output.label'),
                        'data'        => isset($data['output']) ? $data['output'] : 'text',
                        'required'    => true,
                        'empty_value' => false,
                        'label_attr'  => ['class' => 'control-label'],
                        'attr'        => [
                            'class'   => 'form-control',
                            'tooltip' => $this->translator->trans('mautic.enhancer.integration.alcazar.output.tooltip'),
                        ],
                    ]
                )
                ->add(
                    'extended',
                    'yesno_button_group',
                    [
                        'label'       => $this->translator->trans('mautic.enhancer.integration.alcazar.extended.label'),
                        'data'        => !isset($data['extended']) ? false : $data['extended'],
                        'required'    => true,
                        'empty_value' => false,
                        'label_attr'  => ['class' => 'control-label'],
                        'attr'        => [
                            'class'   => 'form-control',
                            'tooltip' => $this->translator->trans('mautic.enhancer.integration.alcazar.extended.tooltip'),
                        ],
                    ]
                )
                ->add(
                    'ani',
                    'yesno_button_group',
                    [
                        'label'       => $this->translator->trans('mautic.enhancer.integration.alcazar.ani.label'),
                        'data'        => !isset($data['ani']) ? false : $data['ani'],
                        'required'    => false,
                        'empty_value' => false,
                        'label_attr'  => ['class' => 'control-label'],
                        'attr'        => [
                            'class'   => 'form-control',
                            'tooltip' => $this->translator->trans('mautic.enhancer.integration.alcazar.ani.tooltip'),
                        ],
                    ]
                )
                ->add(
                    'dnc',
                    'yesno_button_group',
                    [
                        'label'       => $this->translator->trans('mautic.enhancer.integration.alcazar.dnc.label'),
                        'data'        => !isset($data['dnc']) ? false : $data['dnc'],
                        'required'    => false,
                        'empty_value' => false,
                        'label_attr'  => ['class' => 'control-label'],
                        'attr'        => [
                            'class'   => 'form-control',
                            'tooltip' => $this->translator->trans('mautic.enhancer.integration.alcazar.dnc.tooltip'),
                        ],
                    ]
                );
        }
        $this->appendNonFreeFields($builder, $data, $formArea);
    }

    /**
     * @return array|mixed
     */
    protected function getEnhancerFieldArray()
    {
        $field_list = [
            'alcazar_lrn' => [
                'label' => 'Alcazar LRN',
            ],
        ];

        $feature_settings = $this->getIntegrationSettings()->getFeatureSettings();

        if ($feature_settings['extended']) {
            $field_list += $this->getAlcazarExtendedFields();
        }

        return $field_list;
    }

    /**
     * @return array[]
     */
    private function getAlcazarExtendedFields()
    {
        return [
            'alcazar_spid'         => [
                'label' => 'Alcazar SPID',
            ],
            'alcazar_ocn'          => [
                'label' => 'Alcazar OCN',
            ],
            'alcazar_lata'         => [
                'label' => 'Alcazar LATA',
            ],
            'alcazar_city'         => [
                'label' => 'Alcazar City',
            ],
            'alcazar_state'        => [
                'label' => 'Alcazar State',
            ],
            'alcazar_lec'          => [
                'label' => 'Alcazar LEC',
            ],
            'alcazar_linetype'     => [
                'label' => 'Alcazar Line Type',
            ],
            'alcazar_dnc'          => [
                'label' => 'Alcazar DNC',
            ],
            'alcazar_jurisdiction' => [
                'label' => 'Alcazar Jurisdiction',
            ],
        ];
    }

    /**
     * @param Lead $lead
     *
     * @return bool
     */
    public function doEnhancement(Lead &$lead)
    {
        if (!empty($lead)) {
            if ($lead->getFieldValue('alcazar_lrn') || !$lead->getPhone()) {
                return false;
            }

            $phone = $lead->getPhone();
            $phone = preg_replace('/[^0-9]/', '', $phone);
            if (10 === strlen($phone)) {
                $phone = '1'.$phone;
            }
            if (11 !== strlen($phone)) {
                return false;
            }

            $keys = $this->getKeys();

            $params = [
                'key' => $keys['apikey'],
                'tn'  => $phone,
            ];

            $settings = $this->getIntegrationSettings()->getFeatureSettings();
            foreach ($settings as $param => $value) {
                if ('ani' === $param) {
                    //the value of ani should be a phone number
                    //but this service is currently unused
                    continue;
                } elseif ('output' === $param) {
                    $params['output'] = $value;
                } elseif (in_array($param, ['extended', 'dnc'])) {
                    $params[$param] = ($value ? 'true' : 'false');
                }
            }

            try {
                $response = $this->makeRequest(
                    $keys['server'],
                    ['append_to_query' => $params],
                    'GET',
                    ['ignore_event_dispatch' => 1]
                );
            } catch (\Exception $e) {
                $this->logger->error($e->getMessage());

                return false;
            }

            if ($response) {
                $this->applyCost($lead);

                $allowedAliases = $this->getEnhancerFieldArray();
                foreach ($response as $label => $value) {
                    $alias = 'alcazar_'.strtolower($label);
                    if (isset($allowedAliases[$alias])) {
                        $default = $lead->getFieldValue($alias);
                        $lead->addUpdatedField($alias, $value, $default);
                    }
                }

                return true;
            }

            return false;
        }
    }
}
