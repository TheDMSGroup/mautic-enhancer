<?php

namespace MauticPlugin\MauticEnhancerIntegration\Helper;

use Mautic\PluginBundle\Helper\IntegrationHelper;

class EnhancementHelper
{
    public static $integration_helper;
    
    public static function init(IntegrationHelper $helper)
    {
        self::$integration_helper = $helper;
    }

    
}