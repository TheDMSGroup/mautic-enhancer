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

use Mautic\CoreBundle\Form\Type\YesNoButtonGroupType;

/**
 * Trait NonFreeEnhancerTrait.
 */
trait NonFreeEnhancerTrait
{
    /** @var int */
    protected $cost_per_enhancement;

    /**
     * @return string|float
     */
    public function getCostPerEnhancement()
    {
        if (!isset($this->cost_per_enhancement)) {
            $settings                   = $this->getIntegrationSettings()->getFeatureSettings();
            $this->cost_per_enhancement = !empty($settings['cost_per_enhancement']) ? $settings['cost_per_enhancement'] : 0;
        }

        return $this->cost_per_enhancement;
    }

    /**
     * @param \Symfony\Component\Form\FormBuilderInterface $builder
     * @param array                                        $data
     * @param string                                       $formArea
     * @param bool                                         $autorun_override
     */
    public function appendToForm(&$builder, $data, $formArea, $autorun_override = false)
    {
        if ('features' === $formArea) {
            $builder->add(
                'cost_per_enhancement',
                'number',
                [
                    'label'      => $this->translator->trans('mautic.enhancer.cpe.label'),
                    'data'       => isset($data['cost_per_enhancement']) ? $data['cost_per_enhancement'] : '0.0',
                    'required'   => true,
                    'label_attr' => ['class' => 'control-label'],
                    'attr'       => [
                        'class'   => 'form-control',
                        'tooltip' => $this->translator->trans('mautic.enhancer.cpe.tooltip'),
                    ],
                ]
            );

            if ($autorun_override) {
                //expose autorun option if override is true
                $builder->add(
                    'autorun_enabled',
                    YesNoButtonGroupType::class,
                    [
                        'label'       => $this->translator->trans('mautic.enhancer.autorun.label'),
                        'data'        => !isset($data['autorun_enabled']) ? false : $data['autorun_enabled'],
                        'required'    => true,
                        'empty_value' => false,
                        'label_attr'  => ['class' => 'control-label'],
                        'attr'        => [
                            'class'   => 'form-control',
                            'tooltip' => $this->translator->trans('mautic.enhancer.autorun.tooltip'),
                        ],
                    ]
                );
            } else {
                //typically, hide and set autorun to false
                $builder->add(
                    'autorun_enabled',
                    'hidden',
                    [
                        'data' => false,
                    ]
                );
            }
        }
    }
}
