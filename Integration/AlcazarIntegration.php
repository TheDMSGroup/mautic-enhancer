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
 * Class AlcazarIntegration
 *
 * @package \MauticPlugin\MauticPluginBundle\Integration
 */
class AlcazarIntegration extends AbstractEnhancerIntegration implements NonFreeEnhancerInterface
{
    /**
     * Pull in a trait to handle the interfacce
     * 
     * @var \MauticPlugin\MauticEnhancerBundle\Integration\NonFreeEnhancerTrait 
     */   
    use NonFreeEnhancerTrait {
        appendToForm as private appendNonFreeEnhancer;
        getRequiredKeyFields as private getNonFreeKeys;
    }
 
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'Alcazar';
    }
    
    /**
     * {@inheritdoc}
     */
    public function getAuthenticationType()
    {
        return 'keys';
    }
    
    /**
     * {@inheritdoc}
     */
    public function getRequiredKeyFields()
    {
        return [
            'server' => $this->translator->trans('mautic.integration.alcazar.server.label'),
            'apikey' => $this->translator->trans('mautic.integration.alcazar.apikey.label'),
    ]; 
    }
    
    /**
     * {@inheritdoc}
     */
    public function getSupportedFeatures()
    {
        return ['push_lead'];
    }

    /**
     * {@inheritdoc}
     */
    public function appendToForm(&$builder, $data, $formArea)
    {
        if ('features' === $formArea) {
            $builder
                ->add(
                    'output',
                    'choice',
                    [
                        'choices' => [
                            'json' => 'JSON',
                            'xml' => 'XML',
                            'text' => 'text',
                        ],
                        'label' => $this->translator->trans('mautic.integration.alcazar.output.label'),
                        'data'  =>  isset($data['output']) ? $data['output'] : 'text',
                        'required'    => false,
                        'empty_value' => false,
                        'label_attr'  => ['class' => 'control-label'],
                        'attr'        => [
                            'class' => 'form-control',
                            'tooltip' => $this->translator->trans('mautic.integration.alcazar.output.tooltip'),
                        ],
                    ]
                )
                ->add(
                    'extended',
                    'yesno_button_group',
                    [
                        'label' => $this->translator->trans('mautic.integration.alcazar.extended.label'),
                        'data'  => !isset($data['extended']) ? false : $data['extended'],
                        'required'    => false,
                        'empty_value' => false,
                        'label_attr'  => ['class' => 'control-label'],
                        'attr'        => [
                            'class' => 'form-control',
                            'tooltip' => $this->translator->trans('mautic.integration.alcazar.extended.tooltip'),
                        ],
                    ]
                )
                ->add(
                    'ani',
                    'yesno_button_group',
                    [
                        'label' => $this->translator->trans('mautic.integration.alcazar.ani.label'),
                        'data'  => !isset($data['ani']) ? false : $data['ani'],
                        'required'    => false,
                        'empty_value' => false,
                        'label_attr'  => ['class' => 'control-label'],
                        'attr'        => [
                            'class' => 'form-control',
                            'tooltip' => $this->translator->trans('mautic.integration.alcazar.ani.tooltip'),
                        ],
                    ]
                )
                ->add(
                    'dnc',
                    'yesno_button_group',
                    [
                        'label' => $this->translator->trans('mautic.integration.alcazar.dnc.label'),
                        'data'  => !isset($data['dnc']) ? false : $data['dnc'],
                        'required'    => false,
                        'empty_value' => false,
                        'label_attr'  => ['class' => 'control-label'],
                        'attr'        => [
                            'class' => 'form-control',
                            'tooltip' => $this->translator->trans('mautic.integration.alcazar.dnc.tooltip'),
                        ],
                    ]
                );       
        }
        elseif ('keys' === $formArea) {
            $this->appendNonFreeEnhancer($builder, $data, $formArea);
        }
    }
             
    /**
     * {@inheritdoc}
     */
    protected function getEnhancerFieldArray()
    {
        $object_name = class_exists('MauticPlugin\MauticExtendedFieldBundle\MauticExtendedFieldBundle') ? 'extendedField' : 'lead';

        $field_list = [
            'alcazar_lrn' => [
                'label' => 'LRN',
                'object' => $object_name
            ]
        ];
        
        $integration = $this->getIntegrationSettings();
        $feature_settings = $integration->getFeatureSettings();
        
        if ($feature_settings['extended']) {        
            $field_list += $this->getAlcazarExtendedFields($object_name);
        }
        
        return $field_list;
    }
    
    /**
     * Additional fields that Alcazar can provide if enabled
     *
     * ANI requires a phone number representing the dialed party
     *
     * @param string $object_name the field obkect to use (lead, company, extendedField)
     *
     * @return array[] [lead_field.alias => [ead_field.column => column.value, ...] ...]
     */
    private function getAlcazarExtendedFields(string $object_name = 'lead')
    {
      return [
            'alcazar_spid'     => [
                'label' => 'SPID',
                'object'=>$object_name
            ],
            'alcazar_ocn'      => [
                'label' => 'OCN',
                'object'=>$object_name
            ],
            'alcazar_lata'     => [
                'label' => 'LATA',
                'object'=>$object_name
            ],
            'alcazar_city'     => [
                'label' => 'CITY',
                'object' => $object_name
            ],
            'alcazar_state'    => [
                'label' => 'STATE',
                'object' => $object_name
            ],
            'alcazar_lec'      => [
                'label' => 'LEC',
                'object'=> $object_name
            ],
            'alcazar_linetype' => [
                'label' => 'LINETYPE',
                'object'=> $object_name],
            'alcazar_dnc'      => [
                'label' => 'DNC',
                'object'=>$object_name
            ],
            'alcazar_jurisdiction' => [
                'label' => 'JURISDICTION',
                'object'=>$object_name
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function doEnhancement(Lead $lead, array $config = [])
    {
        
        if ($lead->getFieldValue('alcazar_lrn') || !$lead->getPhone()) {
            return;
        }
        
        $phone = $lead->getPhone();
        if (strlen($phone) === 10) {
            $phone = '1' . $phone;
        }
        
        if (strlen($phone) !== 11) {
            //not a proper phone number
            return false;
        }
        
        $keys = $this->getKeys();     
        $params = [
            'key' => $keys['apikey'],
            'tn' =>  $phone,
        ];
        
        $settings = $this->getIntegrationSettings()->getFeatureSettings();    
        foreach ($settings as $param => $value) {
            if ($param === 'installed') {
                continue;
            }
            elseif ($param === 'ani') {
                if (!$value) {
                    continue;
                }
                if (strlen($value) === 10) {
                    $value = '1' . $value;
                }
                $params['ani'] = $value;
            }
            elseif ($param === 'output') {
                $params['output'] = $value;
            }
            elseif (in_array($param, ['extended', 'dnc'])) {
                $params[$param] = $value ? 'true' : 'false';
            }
        }
       
        try {
            $response = $this->makeRequest(
                $keys['server'],
                ['append_to_query' => $params],
                'GET',
                ['ignore_event_dispatch' => 1]
            );       
        
            foreach ($response as $label => $value) {
                $alias = 'alcazar_' . strtolower($label);
                $default = $lead->getFieldValue($alias);
                $lead->addUpdatedField($alias, $value, $default);        
            }
            
            $this->leadModel->saveEntity($lead);
            $this->em->flush();
        } catch (Exception $e) {
            return false;
        }
                
        return true;
    }
}

