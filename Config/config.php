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
    'name'        => 'Enhancers',
    'description' => 'Adds Integrations for validating or manipulating Lead Data. Includes Alcazar, Random, and xVerify.',
    'version'     => '1.0.0',
    'author'      => 'Nicholai Bush',

    'services' => [
        'events' => [
            'mautic.enhancer.eventlistener.lead'   => [
                'class'     => \MauticPlugin\MauticEnhancerBundle\EventListener\LeadSubscriber::class,
                'arguments' => [
                    '@mautic.enhancer.helper.enhancer',
                ],
            ],
            'mautic.enhancer.eventlistener.plugin' => [
                'class' => \MauticPlugin\MauticEnhancerBundle\EventListener\PluginSubscriber::class,
            ],
        ],
        'models' => [
            'mautic.enhancer.model.citystatepostalcode' => [
                'class' => \MauticPlugin\MauticEnhancerBundle\Model\CityStatePostalCodeModel::class,
            ],
        ],
        'integrations' => [
            'mautic.enhancer.integration.agefrombirthdate' => [
                'class' => \MauticPlugin\MauticEnhancerBundle\Integration\AgeFromBirthdateIntegration::class,
            ],
            'mautic.enhancer.integration.alcazar'          => [
                'class' => \MauticPlugin\MauticEnhancerBundle\Integration\AlcazarIntegration::class,
            ],
            'mautic.enhancer.integration.random'           => [
                'class' => \MauticPlugin\MauticEnhancerBundle\Integration\RandomIntegration::class,
            ],
            'mautic.enhancer.integration.fourleaf'         => [
                'class' => \MauticPlugin\MauticEnhancerBundle\Integration\FourleafIntegration::class,
            ],
            'mautic.enhancer.integration.xverify'          => [
                'class' => \MauticPlugin\MauticEnhancerBundle\Integration\XverifyIntegration::class,
            ],
            'mautic.enhancer.integration.citystatefrompostalcode' => [
                'class' => \MauticPlugin\MauticEnhancerBundle\Integration\CityStateFromPostalCodeIntegration::class,
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
