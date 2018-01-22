<?php
return [
    'name' => 'Alcazar Enhancers',
    'description' => 'Adds Alcazar Network Integration for Phone Number Data Enhancement',
    'version'     => '1.0.0',
    'author'      => 'Nicholai Bush',
    'services' => [
        'events' => [
            'mautic.alcazar.event.lead' => [
                'class' => \MauticPlugin\MauticEnhancerBundle\EventListener\LeadSubscriber::class,
                'arguments' => [
                    'mautic.helper.integration',
                ], 
            ],
            'mautic.alcazar.event.plugin' => [
                'class' => \MauticPlugin\MauticEnhancerBundle\EventListener\PluginSubscriber::class,
                'arguments' => [
                    'mautic.helper.integration',
                ],
            ],
        ],
        'integrations' => [
            'mautic.alcazar.integration.alcazar' => [
                'class' => \MauticPlugin\MauticAlcazarBundle\Integration\AlcazarIntegration::class,
                'arguments' => [
			''
		],
            ],
        ],
    ],
];
