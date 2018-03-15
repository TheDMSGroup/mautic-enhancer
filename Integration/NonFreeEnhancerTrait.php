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
    protected $cost_per_enhancement;

    /**
     * @return string|float
     */
    public function getCostPerEnhancement()
    {
        if (!isset($this->cost_per_enhancement)) {
            $settings                   = $this->getIntegrationSettings()->getFeatureSettings();
            $this->cost_per_enhancement = $settings['cost_per_enhancement'];
        }

        return $this->cost_per_enhancement;
    }

    /**
     * @param \Symfony\Component\Form\FormBuilderInterface $builder
     * @param array                                        $data
     * @param string                                       $formArea
     * @param bool                                         $overrideArea
     */
    public function appendToForm(&$builder, $data, $formArea, $overrideArea = false)
    {
        if ('features' === $formArea) {
            $builder
                ->add(
                    'cost_per_enhancement',
                    'number',
                    [
                        'label'      => $this->translator->trans('mautic.integration.cpe.label'),
                        'data'       => !isset($data['cost_per_enhancement']) ? '0.0000' : $data['cost_per_enhancement'],
                        'required'   => true,
                        'label_attr' => ['class' => 'control-label'],
                        'attr'       => [
                            'class'   => 'form-control',
                            'tooltip' => $this->translator->trans('mautic.integration.cpe.tooltip'),
                        ],
                    ]
                )
                ->add(
                    'autorun_enabled',
                    'hidden',
                    [
                        'data' => false,
                    ]
                );
        }
    }
}
