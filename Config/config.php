<?php
return [
    'name' => 'Mautic Data Enhancers',
    'description' => 'Adds Integrations for validating or manipulating Lead Data. Includes Alcazar, Random, and xVerify.',
    'version'     => '1.0.0',
    'author'      => 'Nicholai Bush',
    'services' => [
        'events' => [
            'mautic.enhancer.event.lead' => [
                'class' => \MauticPlugin\MauticEnhancerBundle\EventListener\LeadSubscriber::class,
                'arguments' => [], 
            ],
            'mautic.enhancer.event.plugin' => [
                'class' => \MauticPlugin\MauticEnhancerBundle\EventListener\PluginSubscriber::class,
                'arguments' => [],
            ],
        ],
        'integrations' => [
            'mautic.enhancer.integration.alcazar' => [
                'class' => \MauticPlugin\MauticEnhancerBundle\Integration\AlcazarIntegration::class,
                'arguments' => [],
            ],
            'mautic.enhancer.integration.random' => [
                'class' => \MauticPlugin\MauticEnhancerBundle\Integration\RandomIntegration::class,
                'arguments' => [],
            ],
            'mautic.enhancer.integration.xverify' => [
                'class' => \MauticPlugin\MauticEnhancerBundle\Integration\XverifyIntegration::class,
                'arguments' => [],
            ],
        ],
    ],
];
