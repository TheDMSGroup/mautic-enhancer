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
     * The list of available MauticEnhancerIntegrations
     *
     * @return string[]
     */
    final static public function IntegrationNames()
    {
        return  ['AgeFromBirthdate', 'Alcazar', 'Random', 'Fourleaf', 'Xverify'];
    }
    
    /**
     * @var \Mautic\PluginBundle\Helper\IntegrationHelper $integration_helper Mautic's helper to help us
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
     * Getter for IntegrationHelper
     * 
     * @return \Mautic\PluginBundle\Helper\IntegrationHelper
     */
    public function getInegrationHelper()
    {
        return $this->integration_helper;
    }

    /**
     * Returns an AbstractIntegration Typed object
     *
     * A concrete inegration instance of $name or false if not found.
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
     * Returns an array of available AbstractEnhancerIntegrations
     *
     * @return \MauticPlugin\MauticEnhancerBundle\Integration\AbstractEnhancerIntegration[] 
     */
    public function getEnhancerIntegrations()
    {
        return $this->integration_helper->getIntegrationObjects(EnhancerHelper::IntegrationNames());
    }

}