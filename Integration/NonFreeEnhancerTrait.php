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
     * @return mixed
     */
    public function getCostPerEnhancement()
    {
        $keys = $this->getDecryptedApiKeys();

        return $keys['cpe'];
    }

    /**
     * @param $builder
     * @param $data
     * @param $formArea
     */
    public function appendCostToForm(&$builder, $data, $formArea)
    {
        if ('keys' === $formArea) {
            $builder->add(
                'autorun_enabled',
                'yesno_button_group',
                [
                    'label' => $this->translator->trans('mautic.integration.autorun.label'),
                    'data'  => !isset($data['autorun_enabled']) ? false : $data['autorun_enabled'],
                    'attr'  => [
                        'tooltip' => $this->translator->trans('mautic.integration.autorun.tooltip'),
                    ],
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
                        ],
                    ]
                );
        }
    }
}
