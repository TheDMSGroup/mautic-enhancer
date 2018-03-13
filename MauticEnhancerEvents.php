<?php

namespace MauticPlugin\MauticEnhancerBundle;

/**
 * Class MauticEnhancerEvents.
 */
final class MauticEnhancerEvents
{
    /**
     * Fired when an enhancer completes.
     *
     * The event listener receives a
     * MauticPlugin\MauticEnhancerBundle\Event\MauticEnhancerEvent.
     *
     * @var string
     */
    const ENHANCER_COMPLETED = 'mauticplugin.mautic_enhancer.enhancer_complete';
}
