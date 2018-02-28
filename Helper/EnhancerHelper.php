<?php

namespace MauticPlugin\MauticEnhancerBundle\Helper;

use Mautic\PluginBundle\Helper\IntegrationHelper;

/**
 * Class EnhancerHelper
 * 
 * @package \MauticPlugin\MauticEnhancerBundle\Helper
 */
class EnhancerHelper
{
    /**
     * The list of available plugin integrations
     *
     * @return string[]
     */
    final static public function IntegrationNames()
    {
        return  ['AgeFromBirthdate', 'Alcazar', 'Random', 'Fourleaf', 'Xverify'];
    }
    
    /**
     * @var Mautic\PluginBundle\Helper\IntegrationHelper Mautic's helper to help us
     */
    protected $integration_helper;
    
    /**
     * Constructor
     * 
     * @param \Mautic\PluginBundle\Helper\IntegrationHelper $integration_helper
     */
    public function __construct(IntegrationHelper $integration_helper)
    {
        $this->integration_helper = $integration_helper;
    }

    /**
     * Getter for Mautic's IntegrationHelper
     * 
     * @return IntegrationHelper
     */
    public function getInegrationHelper()
    {
        return $this->integration_helper;
    }

    /**
     * Returns an AbstractIntegration Typed object
     *
     * A concrete instance from plugin_integration_settings or false if none exists
     * 
     * @param $name The integration's name
     *
     * @return \Mautic\PluginBundle\Integration\AbstractIntegration|bool 
     */
    public function getIntegration($name)
    {
        return $integration = $this->integration_helper->getIntegrationObject($name);
    }
    
    /**
     * Returns the array of available EnhancerIntegrations 
     *
     * @return \Mautic\PluginBundle\Integration\AbstractIntegration[] 
     */
    public function getIntegrations()
    {
        return $integrations = $this->integration_helper->getIntegrationObjects(EnhancerHelper::IntegrationNames());        
    }
   
}