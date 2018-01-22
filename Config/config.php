<?php
return [
    'name' => 'Alcazar Enhancers',
    'description' => 'Adds Alcazar Network Integration for Phone Number Data Enhancement',
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
            'mautic.alcazar.integration.alcazar' => [
                'class' => \MauticPlugin\MauticEnhancerBundle\Integration\AlcazarIntegration::class,
                'arguments' => [],
            ],
            'mautic.alcazar.integration.random' => [
                'class' => \MauticPlugin\MauticEnhancerBundle\Integration\RandomIntegration::class,
                'arguments' => [],
            ],
        ],
    ],
];
