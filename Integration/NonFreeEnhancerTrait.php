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

use Mautic\LeadBundle\Entity\Lead;

/**
 * Trait NonFreeEnhancerTrait.
 */
trait NonFreeEnhancerTrait
{
    /**
     * @var bool
     */
    protected $autorun_enabled = false;

    /**
     * @var string|float
     */
    protected $cost_per_enhancement = '0.0000';

    /**
     * @param Lead  $lead
     * @param array $config
     */
    public function pushLead(Lead $lead, array $config = [])
    {
        $this->doEnhancement($lead, $config);
    }

    /**
     * @return bool
     */
    public function getAutorunEnabled()
    {
        return $this->autorun_enabled;
    }

    /**
     * @param bool $enabled
     *
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
        } elseif (is_numeric($cost)) {
            $this->cost_per_enhancement = "$cost";
        }
    }

    /**
     * @return array
     */
    public function getRequiredKeyFields()
    {
        return [
            'autorun_enabled'     => $this->translator->trans('mautic.integration.autorun.label'),
            'cost_per_enhancmebt' => $this->translator->trans('mautic.integration.cpe.label'),
        ];
    }

    /**
     * @param \Symfony\Component\Form\FormBuilderInterface $builder
     * @param array                                        $data
     * @param string                                       $formArea
     * @param bool                                         $overrideArea
     */
    public function appendToForm(&$builder, $data, $formArea, $overrideArea = false)
    {
        if (($overrideArea ? 'features' : 'keys') === $formArea) {
            $builder
                ->add(
                    'autorun_enabled',
                    'yesno_button_group',
                    [
                        'label'       => $this->translator->trans('mautic.integration.autorun.label'),
                        'data'        => !isset($data['autorun_enabled']) ? false : $data['autorun_enabled'],
                        'required'    => false,
                        'empty_value' => false,
                        'label_attr'  => ['class' => 'control-label'],
                        'attr'        => [
                            'class'   => 'form-control',
                            'tooltip' => $this->translator->trans('mautic.integration.autorun.tooltip'),
                        ],
                    ]
                )
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
                );
        }
    }
}
