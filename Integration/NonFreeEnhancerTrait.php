<?php

namespace MauticPlugin\MauticEnhancerBundle\Integration;

trait NonFreeEnhancerTrait
{
    /**
     * @var bool $autorun_enabled Whether or not to run this automatically at Lead creation or only when actively pushed, must be added to the required field array
     */
    protected $autorun_enabled = false;
    
    /**
     * @var string|float $cost_per_enhancement Really a decimal type representing the currency cost of the enhancer
     */
    protected $cost_per_enhancement = "0.0000";
    
    /**
     * $autorun_enabled getter
     *
     * @return bool
     */
    public function getAutorunEnabled()
    {
        return $this->autorun_enabled;
    }
    
    /**
     * $autorun_enabled setter
     *
     * @param bool $enabled state to set $autorun_enabled to
     *
     * @return $this
     */
    public function setAutorunEnabled(bool $enabled)
    {
       $this->autorun_enabled = $rnabled;
       
       return $this;
    }
    
    /**
     * $cost_per_enhancement getter
     *
     * @return string|float
     */
    public function getCostPerEnhancement()
    {
        return $this->cost_per_enhancement;
    }
    
    /**
     * $cost_per_enhancement setter
     *
     * @param string|float $cost The cost to credit for each enhancement run
     *
     * @return $this
     */
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
     * Overrides AbstractIntegration definition, can be overriden by user Class,
     * 
     * Forces autorun_enabled and cost_per_enhancment into the api_keys field
     *
     * {@inheritdoc}
     */
    public function getRequiredKeyFields()
    {
        return [
            'autorun_enabled'     => $this->translator->trans('mautic.integration.aoutorun.label'),
            'cost_per_enhancmebt' => $this->translator->trans('mautic.integration.cpe.label'),
        ];
    }
    
    /**
     * {@inheritdoc}
     */
    public function appendToForm(&$builder, $data, $formArea)
    {
        
        if ($formArea === 'keys') {
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
                    ]
                ]
                
            )
            ->add(
                'cost_per_enhancement',
                'number',
                [
                    'label' => $this->translator->trans('mautic.integration.cpe.label'),
                    'data'  => !isset($data['cost_per_enhancement']) ? '0.0000' : $data['cost_per_enhancement'],
                    'required'    => true,
                    'label_attr'  => ['class' => 'control-label'],
                    'attr'        => [
                        'class' => 'form-control',
                        'tooltip' => $this->translator->trans('mautic.integration.cpe.tooltip'),
                    ]
                ]
            );
        }        
    }
}
