<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Nicholai Bush <nbush@thedmsgrp.com>
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticEnhancerBundle;

use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\PluginBundle\Bundle\PluginBundleBase;
use Mautic\PluginBundle\Entity\Plugin;
use MauticPlugin\MauticEnhancerBundle\Integration\AbstractEnhancerIntegration;

/**
 * Class MauticEnhancerBundle.
 */
class MauticEnhancerBundle extends PluginBundleBase
{
    public static function onPluginUpdate(Plugin $plugin, MauticFactory $factory, $metadata = null, Schema $installedSchema = null)
    {
        /** @var AbstractEnhancerIntegration $integration */
        foreach ($plugin->getIntegrations() as $integration) {
            $stop = 'here';
            $integration->buildEnhancerFields();
        }
    }
}
