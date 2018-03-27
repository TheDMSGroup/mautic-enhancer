<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Nicholai Bush <nbush@thedmsgrp.com>
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticEnhancerBundle\Integration;

use Mautic\LeadBundle\Entity\Lead;

/**
 * Class AlcazarIntegration.
 */
class AlcazarIntegration extends AbstractEnhancerIntegration implements NonFreeEnhancerInterface
{
    /*
     * @var \MauticPlugin\MauticEnhancerBundle\Integration\NonFreeEnhancerTrait
     */
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
            'server' => $this->translator->trans('mautic.integration.alcazar.server.label'),
            'apikey' => $this->translator->trans('mautic.integration.alcazar.apikey.label'),
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
                        'label'       => $this->translator->trans('mautic.integration.alcazar.output.label'),
                        'data'        => isset($data['output']) ? $data['output'] : 'text',
                        'required'    => true,
                        'empty_value' => false,
                        'label_attr'  => ['class' => 'control-label'],
                        'attr'        => [
                            'class'   => 'form-control',
                            'tooltip' => $this->translator->trans('mautic.integration.alcazar.output.tooltip'),
                        ],
                    ]
                )
                ->add(
                    'extended',
                    'yesno_button_group',
                    [
                        'label'       => $this->translator->trans('mautic.integration.alcazar.extended.label'),
                        'data'        => !isset($data['extended']) ? false : $data['extended'],
                        'required'    => true,
                        'empty_value' => false,
                        'label_attr'  => ['class' => 'control-label'],
                        'attr'        => [
                            'class'   => 'form-control',
                            'tooltip' => $this->translator->trans('mautic.integration.alcazar.extended.tooltip'),
                        ],
                    ]
                )
                ->add(
                    'ani',
                    'yesno_button_group',
                    [
                        'label'       => $this->translator->trans('mautic.integration.alcazar.ani.label'),
                        'data'        => !isset($data['ani']) ? false : $data['ani'],
                        'required'    => false,
                        'empty_value' => false,
                        'label_attr'  => ['class' => 'control-label'],
                        'attr'        => [
                            'class'   => 'form-control',
                            'tooltip' => $this->translator->trans('mautic.integration.alcazar.ani.tooltip'),
                        ],
                    ]
                )
                ->add(
                    'dnc',
                    'yesno_button_group',
                    [
                        'label'       => $this->translator->trans('mautic.integration.alcazar.dnc.label'),
                        'data'        => !isset($data['dnc']) ? false : $data['dnc'],
                        'required'    => false,
                        'empty_value' => false,
                        'label_attr'  => ['class' => 'control-label'],
                        'attr'        => [
                            'class'   => 'form-control',
                            'tooltip' => $this->translator->trans('mautic.integration.alcazar.dnc.tooltip'),
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
                'label'  => 'LRN',
            ],
        ];

        $feature_settings = $this->getIntegrationSettings()->getFeatureSettings();

        if ($feature_settings['extended']) {
            $field_list += $this->getAlcazarExtendedFields();
        }

        return $field_list;
    }

    /**
     * @param string $object_name
     *
     * @return array[]
     */
    private function getAlcazarExtendedFields()
    {
        return [
            'alcazar_spid'         => [
                'label'  => 'SPID',
            ],
            'alcazar_ocn'          => [
                'label'  => 'OCN',
            ],
            'alcazar_lata'         => [
                'label'  => 'LATA',
            ],
            'alcazar_city'         => [
                'label'  => 'CITY',
            ],
            'alcazar_state'        => [
                'label'  => 'STATE',
            ],
            'alcazar_lec'          => [
                'label'  => 'LEC',
            ],
            'alcazar_linetype'     => [
                'label'  => 'LINETYPE',
            ],
            'alcazar_dnc'          => [
                'label'  => 'DNC',
            ],
            'alcazar_jurisdiction' => [
                'label'  => 'JURISDICTION',
            ],
        ];
    }

    /**
     * @param Lead $lead
     *
     * @return bool|mixed|void
     */
    public function doEnhancement(Lead &$lead)
    {
        if (!empty($lead)) {
            if ($lead->getFieldValue('alcazar_lrn') || !$lead->getPhone()) {
                return;
            }

            $phone = $lead->getPhone();
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

            $response = $this->makeRequest(
                $keys['server'],
                ['append_to_query' => $params],
                'GET',
                ['ignore_event_dispatch' => 1]
            );

            $this->applyCost($lead);

            foreach ($response as $label => $value) {
                $alias   = 'alcazar_'.strtolower($label);
                $default = $lead->getFieldValue($alias);
                $lead->addUpdatedField($alias, $value, $default);
            }

            $this->saveLead($lead);
        }
    }
}
