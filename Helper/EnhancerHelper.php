<?php

namespace MauticPlugin\MauticEnhancerBundle\Helper;

use Exception;
use Mautic\PluginBundle\Helper\IntegrationHelper;

class EnhancerHelper
{
    public static $enhancerIntegrations = ['AgeFromBirthdate', 'Alcazar', 'Random', 'Fourleaf', 'Xverify'];
    
    /**
     * @var IntegrationHelper
     */
    private $integrationHelper;
    /**
     * @param IntegrationHelper $helper
     */
    public function _construct(IntegrationHelper $helper)
    {
        $this->integrationHelper = $helper;
    }

    /**
     * @return IntegrationHelper
     */
    public function getInegrationHelper()
    {
        return $this->integrationHelper;
    }

    /**
     * @param $name
     * @return bool|\Mautic\PluginBundle\Integration\AbstractIntegration
     */
    public function getIntegration($name)
    {
        return $integration = $this->integrationHelper->getIntegrationObject($name);
    }

    public function getIntegrations()
    {
        $foo = 'bar';
        return $integrations = $this->integrationHelper->getIntegrationObjects(self::$enhancerIntegrations);
    }
   
}