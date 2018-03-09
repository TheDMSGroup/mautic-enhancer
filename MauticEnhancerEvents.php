<?php

namespace MauticPlugin\MauticEnhancerBundle;

/**
 * Class MauticEnhancerEvents
 */
final class MauticEnhancerEvents
{
    /**
     * Fired when an enhancer completes
     *
     * The event listener receives a
     * MauticPlugin\MauticEnhancerBundle\Event\EnhancerCompleted instance.
     *
     * @var string
     */
    const ENHANCER_COMPLETED = 'mauticplugin.enhancer_complete';
}
