<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticEnhancerBundle\Integration;

/**
 * Trait NonFreeEnhancerTrait.
 */
trait NonFreeEnhancerTrait
{
    /**
     * @var bool $autorun_enabled 
     */
    protected $autorun_enabled = false;
    
    /**
     * @var string|float $cost_per_enhancement
     */
    protected $cost_per_enhancement = "0.0000";
    
    /**
     * @return bool
     */
    public function getAutorunEnabled()
    {
        return $this->autorun_enabled;
    }
    
    /**
     * @param bool $enabled
     * @return $this
     */
    public function setAutorunEnabled(bool $enabled)
    {
       $this->autorun_enabled = $enabled;
       return $this;
    }
    
    /**
     * @return string|float
     */
    public function getCostPerEnhancement()
    {
        return $this->cost_per_enhancement;
    }
    
    public function setCostPerEnhancement($cost)
    {   
        if (is_string($cost) && (false !== floatval($cost))) {
            $this->cost_per_enhancement = $cost;
        }
        elseif (is_numeric($cost)) {
            $this->cost_per_enhancement = "$cost";
        }
    }
    
    /**
     * @return array
     */
    public function getRequiredKeyFields()
    {
        return [
            'autorun_enabled'     => $this->translator->trans('mautic.integration.aoutorun.label'),
            'cost_per_enhancmebt' => $this->translator->trans('mautic.integration.cpe.label'),
        ];
    }
    
    /**
     * @param $builder
     * @param $data
     * @param $formArea
     */
    public function appendToForm(&$builder, $data, $formArea)
    {
        if ('keys' === $formArea) {
            $builder->add(
                'autorun_enabled',
                'yesno_button_group',
                [
                    'label' => $this->translator->trans('mautic.integration.autorun.label'),
                    'data'  => !isset($data['autorun_enabled']) ? false : $data['autorun_enabled'],
                    'required'    => true,
                    'label_attr'  => ['class' => 'control-label'],
                    'attr'        => [
                        'class' => 'form-control',
                        'tooltip' => $this->translator->trans('mautic.integration.autorun.tooltip'),
                    ],
                ]
            )
                ->add(
                    'cost_per_enhancement',
                    'number',
                    [
                        'label'       => $this->translator->trans('mautic.integration.cpe.label'),
                        'data'        => !isset($data['cost_per_enhancement']) ? '0.0000' : $data['cost_per_enhancement'],
                        'required'    => true,
                        'label_attr'  => ['class' => 'control-label'],
                        'attr'        => [
                            'class'   => 'form-control',
                            'tooltip' => $this->translator->trans('mautic.integration.cpe.tooltip'),
                        ],
                    ]
                );
        }
    }
}
