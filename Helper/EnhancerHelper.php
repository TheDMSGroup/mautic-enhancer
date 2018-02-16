<?php

namespace MauticPlugin\MauticEnhancerBundle\Helper;

use Exception;
use Mautic\PluginBundle\Helper\IntegrationHelper;

class EnhancerHelper extends IntegrationHelper
{
    protected static $enhancerIntegrations = ['AgeFromBirthdate', 'Alcazar', 'Random', 'Fourleaf', 'Xverify'];
    
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
    public static function getInegrationHelper()
    {
        return self::$integrationHelper;
    }

    /**
     * @param $name
     * @return bool|\Mautic\PluginBundle\Integration\AbstractIntegration
     */
    public static function getIntegration($name)
    {
        return $integration = self::$integrationHelper->getIntegrationObject($name);
    }

    public static function getIntegrations()
    {
        return self::$integrationHelper->getIntegrationObjects(self::$enhancerIntegrations);
    }
   
}