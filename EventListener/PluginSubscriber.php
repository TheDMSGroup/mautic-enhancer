<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticEnhancerBundle\EventListener;

use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\PluginBundle\Event\PluginIntegrationEvent;
use Mautic\PluginBundle\PluginEvents;
use MauticPlugin\MauticEnhancerBundle\Helper\EnhancerHelper;

/**
 * Class PluginSubscriber.
 */
class PluginSubscriber extends CommonSubscriber
{
    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            PluginEvents::PLUGIN_ON_INTEGRATION_CONFIG_SAVE => ['buildEnhancerFields', 0],
        ];
    }

    /**
     * @param PluginIntegrationEvent $event
     */
    public function buildEnhancerFields(PluginIntegrationEvent $event)
    {
        $integration = $event->getIntegration();
        if (in_array($integration->getName(), EnhancerHelper::IntegrationNames())) {
            $integration->buildEnhancerFields();
        }
    }
}
