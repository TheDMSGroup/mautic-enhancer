<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

return [
    'name'        => 'Mautic Data Enhancers',
    'description' => 'Adds Integrations for validating or manipulating Lead Data. Includes Alcazar, Random, and xVerify.',
    'version'     => '1.0.0',
    'author'      => 'Nicholai Bush',

    'services' => [
        'events' => [
            'mautic.enhancer.event.lead'   => [
                'class'     => 'MauticPlugin\MauticEnhancerBundle\EventListener\LeadSubscriber',
                'arguments' => [
                    '@mautic.enhancer.helper.enhancer',
                ],
            ],
            'mautic.enhancer.event.plugin' => [
                'class' => \MauticPlugin\MauticEnhancerBundle\EventListener\PluginSubscriber::class,
            ],
        ],

        'integrations' => [
            'mautic.enhancer.integration.alcazar'  => [
                'class' => \MauticPlugin\MauticEnhancerBundle\Integration\AlcazarIntegration::class,
            ],
            'mautic.enhancer.integration.random'   => [
                'class' => \MauticPlugin\MauticEnhancerBundle\Integration\RandomIntegration::class,
            ],
            'mautic.enhancer.integration.fourleaf' => [
                'class' => \MauticPlugin\MauticEnhancerBundle\Integration\FourleafIntegration::class,
            ],
            'mautic.enhancer.integration.xverify'  => [
                'class' => \MauticPlugin\MauticEnhancerBundle\Integration\XverifyIntegration::class,
            ],
        ],
        'other'        => [
            'mautic.enhancer.helper.enhancer' => [
                'class'     => \MauticPlugin\MauticEnhancerBundle\Helper\EnhancerHelper::class,
                'arguments' => [
                    '@mautic.helper.integration',
                ],
            ],
        ],
    ],
];
