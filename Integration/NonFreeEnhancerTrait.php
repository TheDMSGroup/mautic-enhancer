<?php

namespace MauticPlugin\MauticEnhancerBundle\Integration;

trait NonFreeEnhancerTrait
{
    public function getCostPerEnhancement()
    {
        $keys = $this->getDecryptedApiKeys();
        
        return $keys['cpe'];
    }
    
    public function appendCostToForm(&$builder, $data, $formArea)
    {
        if ($formArea === 'keys') {
            $builder->add(
                'autorun_enabled',
                'yesno_button_group',
                [
                    'label' => $this->translator->trans('mautic.integration.autorun.label'),
                    'data'  => !isset($data['autorun_enabled']) ? false : $data['autorun_enabled'],
                    'attr'  => [
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
                    'attr'  => [
                        'tooltip' => $this->translator->trans('mautic.integration.cpe.tooltip'),
                    ]
                ]
            );
        }        
    }
}