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

use MauticPlugin\MauticEnhancemerBundle\Inegration\AbsractEnhancementIntegration;

class AlcazarIntegration extends AbstractEnhancerIntegration
{
    const INTEGRATION_NAME = 'Alcazar';
 
    public function getAuthenticationType()
    {
        return 'keys';
    }
    
    public function getRequiredKeyFields()
    {
        return [
            'server' => 'mautic.integration.alcazar.server.label',
            'apikey' => 'mautic.integration.alcazar.apikey.label'
        ];
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
                        'label' => 'mautic.integration.alcazar.output.label',
                        'data'  =>  isset($data['output']) ? $data['output'] : 'text',
                        'required'    => false,
                        'empty_value' => false,
                        'label_attr'  => ['class' => 'control-label'],
                        'attr'        => [
                            'class' => 'form-control',
                            'tooltip' => 'mautic.integration.alcazar.output.tooltip',
                        ],
                    ]
                )
                ->add(
                    'extended',
                    'yesno_button_group',
                    [
                        'label' => 'mautic.integration.alcazar.extended.label',
                        'data'  => !isset($data['extended']) ? false : $data['extended'],
                        'required'    => false,
                        'empty_value' => false,
                        'label_attr'  => ['class' => 'control-label'],
                        'attr'        => [
                            'class' => 'form-control',
                            'tooltip' => 'mautic.integration.alcazar.extended.tooltip',
                        ],
                    ]
                )
                ->add(
                    'ani',
                    'yesno_button_group',
                    [
                        'label' => 'mautic.integration.alcazar.ani.label',
                        'data'  => !isset($data['ani']) ? false : $data['ani'],
                        'required'    => false,
                        'empty_value' => false,
                        'label_attr'  => ['class' => 'control-label'],
                        'attr'        => [
                            'class' => 'form-control',
                            'tooltip' => 'mautic.integration.alcazar.ani.tooltip',
                        ],
                    ]
                )
                ->add(
                    'dnc',
                    'yesno_button_group',
                    [
                        'label' => 'mautic.integration.alcazar.dnc.label',
                        'data'  => !isset($data['dnc']) ? false : $data['dnc'],
                        'required'    => false,
                        'empty_value' => false,
                        'label_attr'  => ['class' => 'control-label'],
                        'attr'        => [
                            'class' => 'form-control',
                            'tooltip' => 'mautic.integration.alcazar.dnc.tooltip',
                        ],
                    ]
                );       
        }
    }
             
    private function getEnhancerFieldArray()
    {
        $field_list = ['alcazar_lrn' => ['label' => 'LRN']];
        
        if ($this->getIntegrationSettings()->getIsPublished()) {
            $field_list += $this->getExtendedFields();
        }
        
        error_log(print_r($field_list, true));        
        return $field_list;
    }
    
    private function getExtendedFields()
    {
        return [
            'alcazar_spid'     => ['label' => 'SPID'],
            'alcazar_ocn'      => ['label' => 'OCN'],
            'alcazar_lata'     => ['label' => 'LATA'],
            'alcazar_city'     => ['label' => 'CITY'],
            'alcazar_state'    => ['label' => 'STATE'],
            'alcazar_lec'      => ['label' => 'LEC'],
            'alcazar_linetype' => ['label' => 'LINETYPE'],
            'alcazar_dnc'      => ['label' => 'DNC'],
            'alcazar_jurisdiction' => [
                                        'label' => 'JURISDICTION',
                                        'default_value' => 'INDETERMINATE',
                                      ],
        ];
    }
}

