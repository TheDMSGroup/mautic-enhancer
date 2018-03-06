<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticEnhancerBundle\Helper;

use Mautic\PluginBundle\Helper\IntegrationHelper;

class EnhancerHelper
{
    /** @var array */
    public static $enhancerIntegrations = ['AgeFromBirthdate', 'Alcazar', 'Random', 'Fourleaf', 'Xverify'];

    /**
     * @var IntegrationHelper
     */
    private $integrationHelper;

    /**
     * @param IntegrationHelper $helper
     */
    public function __construct(IntegrationHelper $helper)
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
     *
     * @return bool|\Mautic\PluginBundle\Integration\AbstractIntegration
     */
    public function getIntegration($name)
    {
        return $integration = $this->integrationHelper->getIntegrationObject($name);
    }

    /**
     * @return mixed
     */
    public function getIntegrations()
    {
        return $this->integrationHelper->getIntegrationObjects(self::$enhancerIntegrations);
    }
}
