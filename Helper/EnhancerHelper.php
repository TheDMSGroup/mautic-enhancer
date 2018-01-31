<?php

namespace MauticPlugin\MauticEnhancerBundle\Helper;

use Mautic\PluginBundle\Helper\IntegrationHelper;

class EnhancerHelper extends IntegrationHelper
{
    /**
     * @var IntegrationHelper
     */
    protected static $integrationHelper;
    
    /**
     * @param IntegrationHelper $helper
     */
    public static function init(IntegrationHelper $helper)
    {
        self::$integrationHelper = $helper;
    }

    /**
     * @return IntegrationHelper
     */
    public static function getInegrations()
    {
        return self::$integrationHelper;
    }
    
      /**
     * @param $integration
     *
     * @return AbstractIntegration
     */
    public static function getIntegration($integration)
    {
        try {
            return self::$integratonHelper->getIntegrationObject($integration);
        } catch (\Exception $e) {
            // do nothing
        }

        return null;
    }

    public static function getIntegrations()
    {
        return self::$integrationHelper->getIntegrationObjects(['Alcazar', 'Random']);
    }
   
}