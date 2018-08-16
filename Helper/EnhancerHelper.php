<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Digital Media Solutions, LLC
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticEnhancerBundle\Helper;

use Mautic\PluginBundle\Helper\IntegrationHelper;

/**
 * Class EnhancerHelper.
 */
class EnhancerHelper
{
    /**
     * @var IntegrationHelper
     */
    protected $integrationHelper;

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
        return $this->integrationHelper->getIntegrationObject($name);
    }

    /**
     * @return \MauticPlugin\MauticEnhancerBundle\Integration\AbstractEnhancerIntegration[]
     */
    public function getEnhancerIntegrations()
    {
        return $this->integrationHelper->getIntegrationObjects(self::IntegrationNames());
    }

    /**
     * @return string[]
     */
    final public static function IntegrationNames()
    {
        return [
            'AgeFromBirthdate',
            'Alcazar',
            'Random',
            'Fourleaf',
            'Xverify',
            'CityStateFromPostalCode',
            'GenderFromName',
            'PhoneToParts',
            'CorrectAddress',
            'NeustarMpic',
        ];
    }
}
