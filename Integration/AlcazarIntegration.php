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
use MauticPlugin\MauticEnhancerBundle\MauticEnhancerEvents;
use MauticPlugin\MauticEnhancerBundle\Event\MauticEnhancerEvent;

/**
 * Class AlcazarIntegration.
 */
class AlcazarIntegration extends AbstractEnhancerIntegration implements NonFreeEnhancerInterface
{
    /*
     * @var \MauticPlugin\MauticEnhancerBundle\Integration\NonFreeEnhancerTrait
     */
    use NonFreeEnhancerTrait {
        appendToForm as appendNonFreeKeyFields;
        getRequiredKeyFields as getNonFreeKeyFields;
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
                        'required'    => false,
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
                        'required'    => false,
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
        } else {
            $this->appendNonFreeKeyFields($builder, $data, $formArea);
        }
    }

    /**
     * @return array|mixed
     */
    protected function getEnhancerFieldArray()
    {
        $object_name = class_exists(
            'MauticPlugin\MauticExtendedFieldBundle\MauticExtendedFieldBundle'
        ) ? 'extendedField' : 'lead';

        $field_list = [
            'alcazar_lrn' => [
                'label'  => 'LRN',
                'object' => $object_name,
            ],
        ];

        $feature_settings = $this->getIntegrationSettings()->getFeatureSettings();

        if ($feature_settings['extended']) {
            $field_list += $this->getAlcazarExtendedFields($object_name);
        }

        return $field_list;
    }

    /**
     * @param string $object_name
     *
     * @return array[]
     */
    private function getAlcazarExtendedFields($object_name)
    {
        return [
            'alcazar_spid'         => [
                'label'  => 'SPID',
                'object' => $object_name,
            ],
            'alcazar_ocn'          => [
                'label'  => 'OCN',
                'object' => $object_name,
            ],
            'alcazar_lata'         => [
                'label'  => 'LATA',
                'object' => $object_name,
            ],
            'alcazar_city'         => [
                'label'  => 'CITY',
                'object' => $object_name,
            ],
            'alcazar_state'        => [
                'label'  => 'STATE',
                'object' => $object_name,
            ],
            'alcazar_lec'          => [
                'label'  => 'LEC',
                'object' => $object_name,
            ],
            'alcazar_linetype'     => [
                'label'  => 'LINETYPE',
                'object' => $object_name,
            ],
            'alcazar_dnc'          => [
                'label'  => 'DNC',
                'object' => $object_name,
            ],
            'alcazar_jurisdiction' => [
                'label'  => 'JURISDICTION',
                'object' => $object_name,
            ],
        ];
    }

    /**
     * @param Lead $lead
     * @param array $config
     *
     * @return mixed|void
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function doEnhancement(Lead $lead, array $config = [])
    {
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
                if (!$value) {
                    continue;
                }
                if (10 === strlen($value)) {
                    $value = '1'.$value;
                }
                $params['ani'] = $value;
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

        foreach ($response as $label => $value) {
            $alias   = 'alcazar_'.strtolower($label);
            $default = $lead->getFieldValue($alias);
            $lead->addUpdatedField($alias, $value, $default);
        }
//        $this->leadModel->saveEntity($lead);
//        $this->em->flush();

        if ($this->dispatcher->hasListeners(MauticEnhancerEvents::ENHANCER_COMPLETED)) {
            $isNew = !$lead->getId();
            $complete = new MauticEnhancerEvent($this, $lead, $isNew);
            $this->dispatcher->dispatch(MauticEnhancerEvents::ENHANCER_COMPLETED, $complete);
        }
    }
}
