<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticEnhancerBundle\EventListener;

use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\PluginBundle\Event\PluginIntegrationEvent;
use Mautic\PluginBundle\PluginEvents;
use MauticPlugin\MauticEnhancerBundle\Helper\EnhancerHelper;

/**
 * Class PluginSubscriber
 * @package \MauticPlugin\MauticEnhancerBundle\EventListener
 */
class PluginSubscriber extends CommonSubscriber
{    
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            PluginEvents::PLUGIN_ON_INTEGRATION_CONFIG_SAVE => ['buildCustomFields', 0],
        ];
    }

    /**
     * @param \Mautic\PluginBundle\Event\PluginIntegrationEvent $event
     */
    public function buildCustomFields(PluginIntegrationEvent $event)
    {
        $integration = $event->getIntegration();
        if (in_array($integration->getName(), EnhancerHelper::IntegrationNames())) {
            $integration->buildEnhancerFields();
        }
    }
}
