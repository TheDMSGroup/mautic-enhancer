<?php

namespace MauticPlugin\MauticEnhancerBundle\Helper;

use Mautic\PluginBundle\Helper\IntegrationHelper;

class EnhancerHelper extends IntegrationHelper
{
    /**
     * @var IntegrationHelper
     */
    protected static $integration_helper;
    
    /**
     * @param IntegrationHelper $helper
     */
    public static function init(IntegrationHelper $helper)
    {
        self::$integration_helper = $helper;
    }

    /**
     * @return IntegrationHelper
     */
    public static function getHelper()
    {
        return self::$integration_helper;
    }
}