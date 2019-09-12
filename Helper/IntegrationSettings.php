<?php

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Digital Media Solutions, LLC
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticEnhancerBundle\Helper;

use Mautic\PluginBundle\Entity\Integration;
use Mautic\PluginBundle\Helper\IntegrationHelper;
use Mautic\PluginBundle\Integration\AbstractIntegration;

/**
 * Class Api.
 */
class IntegrationSettings
{
    /** @var IntegrationHelper */
    protected $integrationHelper;

    /** @var array */
    private $integrationEntityFeatureSettings;

    /** @var AbstractIntegration */
    private $integrationObject;

    /** @var Integration $integrationEntity */
    private $integrationEntity;

    /**
     * Api constructor.
     *
     * @param IntegrationHelper $integrationHelper
     */
    public function __construct(
        IntegrationHelper $integrationHelper
    ) {
        $this->integrationHelper = $integrationHelper;
    }

    /**
     * @param        $integrationName
     * @param string $key
     * @param string $default
     *
     * @return array|mixed|string
     */
    public function getIntegrationSetting($integrationName, $key = '', $default = '')
    {
        $this->loadIntegrationSettings($integrationName);

        if ($key) {
            if (isset($this->integrationEntityFeatureSettings[$key])) {
                return $this->integrationEntityFeatureSettings[$key];
            } else {
                return $default;
            }
        } else {
            return $this->integrationEntityFeatureSettings;
        }
    }

    /**
     * @param $name
     *
     * @return array
     */
    private function loadIntegrationSettings($name)
    {
        if (null === $this->integrationEntityFeatureSettings) {
            $this->integrationEntityFeatureSettings = [];
            $this->integrationObject                = $this->integrationHelper->getIntegrationObject($name);
            /* @var Integration $integrationEntity */
            $this->integrationEntity = $this->integrationObject->getIntegrationSettings();
            if ($this->integrationEntity) {
                $this->integrationEntityFeatureSettings = array_merge(
                    $this->integrationEntity->getFeatureSettings(),
                    $this->integrationObject->getKeys()
                );
            }
        }

        return $this->integrationEntityFeatureSettings;
    }

    /**
     * @param $key
     * @param $value
     *
     * @return array
     */
    public function setIntegrationSetting($integrationName, $key, $value)
    {
        $this->loadIntegrationSettings($integrationName);

        $this->integrationEntityFeatureSettings = array_merge(
            $this->integrationEntityFeatureSettings,
            [$key => $value]
        );
        $this->integrationEntity->setFeatureSettings($this->integrationEntityFeatureSettings);
        $this->integrationObject->setIntegrationSettings($this->integrationEntity);
        $this->integrationObject->persistIntegrationSettings();

        return $this->integrationEntityFeatureSettings;
    }
}
