<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Digital Media Solutions, LLC
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

return [
    'name'        => 'Enhancers',
    'description' => 'Adds Integrations for validating or manipulating Lead Data. Includes Alcazar, Random, xVerify, Blacklist, Anura.',
    'version'     => '1.10.2',
    'author'      => 'Nicholai Bush',

    'services' => [
        'events'       => [
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
        'models'       => [
            'mautic.enhancer.model.anura'               => [
                'class' => \MauticPlugin\MauticEnhancerBundle\Model\AnuraModel::class,
            ],
            'mautic.enhancer.model.blacklist'           => [
                'class' => \MauticPlugin\MauticEnhancerBundle\Model\BlacklistModel::class,
            ],
            'mautic.enhancer.model.citystatepostalcode' => [
                'class' => \MauticPlugin\MauticEnhancerBundle\Model\CityStatePostalCodeModel::class,
            ],
            'mautic.enhancer.model.gendername'          => [
                'class' => \MauticPlugin\MauticEnhancerBundle\Model\GenderNameModel::class,
            ],
            'mautic.enhancer.model.trustedform'         => [
                'class'     => \MauticPlugin\MauticEnhancerBundle\Model\TrustedformModel::class,
                'arguments' => [
                    'mautic.lead.model.lead',
                ],
            ],
        ],
        'integrations' => [
            'mautic.enhancer.integration.agefrombirthdate'        => [
                'class' => \MauticPlugin\MauticEnhancerBundle\Integration\AgeFromBirthdateIntegration::class,
            ],
            'mautic.enhancer.integration.anura'                   => [
                'class' => \MauticPlugin\MauticEnhancerBundle\Integration\AnuraIntegration::class,
            ],
            'mautic.enhancer.integration.alcazar'                 => [
                'class' => \MauticPlugin\MauticEnhancerBundle\Integration\AlcazarIntegration::class,
            ],
            'mautic.enhancer.integration.blacklist'               => [
                'class' => \MauticPlugin\MauticEnhancerBundle\Integration\BlacklistIntegration::class,
            ],
            'mautic.enhancer.integration.random'                  => [
                'class' => \MauticPlugin\MauticEnhancerBundle\Integration\RandomIntegration::class,
            ],
            'mautic.enhancer.integration.fourleaf'                => [
                'class' => \MauticPlugin\MauticEnhancerBundle\Integration\FourleafIntegration::class,
            ],
            'mautic.enhancer.integration.xverify'                 => [
                'class' => \MauticPlugin\MauticEnhancerBundle\Integration\XverifyIntegration::class,
            ],
            'mautic.enhancer.integration.citystatefrompostalcode' => [
                'class' => \MauticPlugin\MauticEnhancerBundle\Integration\CityStateFromPostalCodeIntegration::class,
            ],
            'mautic.enhancer.integration.genderfromname'          => [
                'class' => \MauticPlugin\MauticEnhancerBundle\Integration\GenderFromNameIntegration::class,
            ],
            'mautic.enhancer.integration.phonetoparts'            => [
                'class' => \MauticPlugin\MauticEnhancerBundle\Integration\PhoneToPartsIntegration::class,
            ],
            'mautic.enhancer.integration.correctaddress'          => [
                'class' => \MauticPlugin\MauticEnhancerBundle\Integration\CorrectAddressIntegration::class,
            ],
            'mautic.enhancer.integration.neustarmpic'             => [
                'class' => MauticPlugin\MauticEnhancerBundle\Integration\NeustarMpicIntegration::class,
            ],
            'mautic.enhancer.integration.trustedform'             => [
                'class' => MauticPlugin\MauticEnhancerBundle\Integration\TrustedFormIntegration::class,
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
