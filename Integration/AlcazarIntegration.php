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

class AlcazarIntegration extends AbstractEnhancerIntegration implements NonFreeEnhancerInterface
{
    const INTEGRATION_NAME = 'Alcazar';
    
    use NonFreeEnhancerTrait;
 
    public function getName()
    {
        return self::INTEGRATION_NAME;
    }
    
    public function getDisplayName()
    {
        return self::INTEGRATION_NAME . ' Data Enhancer';    
    }
        
    public function getAuthenticationType()
    {
        return 'keys';
    }
    
    public function getRequiredKeyFields()
    {
        return [
            'server' => $this->translator->trans('mautic.integration.alcazar.server.label'),
            'apikey' => $this->translator->trans('mautic.integration.alcazar.apikey.label'),
        ];
    }
    
    public function getSupportedFeatures()
    {
        return ['push_lead'];
    }

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
        $this->appendCostToForm($builder, $data, $formArea);
    }
             
    protected function getEnhancerFieldArray()
    {
        $field_list = ['alcazar_lrn' => ['label' => 'LRN']];
        
        $integration = $this->getIntegrationSettings();
        $feature_settings = $integration->getFeatureSettings();
        
        if ($feature_settings['extended']) {        
            $field_list += $this->getExtendedFields();
        }
        
        return $field_list;
    }
    
    private function getExtendedFields()

    {
      $object = class_exists('MauticPlugin\MauticExtendedFieldBundle\MauticExtendedFieldBundle') ? 'extendedField' : 'lead';

      return [
            'alcazar_spid'     => ['label' => 'SPID', 'object'=>$object],
            'alcazar_ocn'      => ['label' => 'OCN', 'object'=>$object],
            'alcazar_lata'     => ['label' => 'LATA', 'object'=>$object],
            'alcazar_city'     => ['label' => 'CITY', 'object'=>$object],
            'alcazar_state'    => ['label' => 'STATE', 'object'=>$object],
            'alcazar_lec'      => ['label' => 'LEC', 'object'=>$object],
            'alcazar_linetype' => ['label' => 'LINETYPE', 'object'=>$object],
            'alcazar_dnc'      => ['label' => 'DNC', 'object'=>$object],
            'alcazar_jurisdiction' => [
                'label' => 'JURISDICTION',
                'default_value' => 'INDETERMINATE',
                'object'=>$object
            ],
        ];
    }

    public function doEnhancement(Lead $lead)
    {
        
        if ($lead->getFieldValue('alcazar_lrn') || !$lead->getPhone()) {
            return;
        }
        
        $phone = $lead->getPhone();
        if (strlen($phone) === 10) {
            $phone = '1' . $phone;
        }
        
        $keys = $this->getDecryptedApiKeys();
          
        $params = [
            'key' => $keys['apikey'],
            'tn' =>  $phone,
        ];
        
        $integration = $this->getIntegrationSettings();
        $settings = $integration->getFeatureSettings();
        
        foreach ($settings as $param => $value) {
            if ($param === 'ani') {
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
                $value = $value ? 'true' : 'false';
                $params[$param] = $value;
            }
        }
       
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
    }
}

