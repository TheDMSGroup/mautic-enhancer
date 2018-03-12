<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Nicholai Bush <nbush@thedmsgrp.com>
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticEnhancerBundle\Integration;

use Mautic\LeadBundle\Entity\Lead;
use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\PluginBundle\Integration\AbstractIntegration;
use MauticPlugin\MauticEnhancerBundle\MauticEnhancerEvents;
use MauticPlugin\MauticEnhancerBundle\Event\MauticEnhancerEvent;

/**
 * Class AbstractEnhancerIntegration.
 *
 * @method string getAuthorizationType()
 * @method string getName()
 */
abstract class AbstractEnhancerIntegration extends AbstractIntegration
{
    /**
     * @throws \Doctrine\DBAL\DBALException
     */
    public function buildEnhancerFields()
    {
        $integration = $this->getIntegrationSettings();

        $count = count($this->fieldModel->getLeadFields());

        if ($integration->getIsPublished()) {
            $feature_settings = $integration->getFeatureSettings();
            $created          = isset($feature_settings['installed']) ? $feature_settings['installed'] : [];
            $creating         = $this->getEnhancerFieldArray();

            foreach ($creating as $alias => $properties) {
                if (in_array($alias, $created)) {
                    //do not build an existing column
                    continue;
                }

                $new_field = $this->fieldModel->getEntity();
                $new_field->setAlias($alias);
                $new_field->setOrder(++$count);

                foreach ($properties as $property => $value) {
                    $method = 'set'.implode('', array_map('ucfirst', explode('_', $property)));

                    try {
                        $new_field->$method($value);
                    } catch (\Exception $e) {
                        error_log('Failed with "'.$e->getMessage().'"');
                    }
                }

                $this->fieldModel->saveEntity($new_field);
                $created[] = $alias;
            }

            $feature_settings['installed'] = $created;
            $integration->setFeatureSettings($feature_settings);
        }
    }

    /**
     * @param Lead  $lead
     * @param array $config
     *
     * @return bool|void
     */
    abstract public function doEnhancement(Lead &$lead);

    /**
     * @return string
     */
    public function getDisplayName()
    {
        $spaced_name = preg_replace('/([a-z])([A-Z])/', '$1 $2', $this->getName());

        return sprintf('%s Data Enhancer', $spaced_name);
    }

    /**
     * @returns array[]
     */
    abstract protected function getEnhancerFieldArray();

    /**
     * @param array $settings
     *
     * @return array
     */
    public function getFormLeadFields($settings = [])
    {
        static $fields = [];

        if (empty($fields)) {
            // $name = $this->getName();
            $available = $this->getAvailableLeadFields($settings);
            if (empty($available) || !is_array($available)) {
                return [];
            }

            foreach ($available as $field => $details) {
                $label = (!empty($details['label'])) ? $details['label'] : false;
                $fn    = $this->matchFieldName($field);
                switch ($details['type']) {
                    case 'string':
                    case 'boolean':
                        $fields[$fn] = (!$label)
                            ? $this->translator->transConditional(
                                "mautic.integration.common.{$fn}",
                                "mautic.integration.{$s}.{$fn}.label"
                            )
                            : $label;
                        break;
                    case 'object':
                        if (isset($details['fields'])) {
                            foreach ($details['fields'] as $f) {
                                $fn          = $this->matchFieldName($field, $f);
                                $fields[$fn] = (!$label)
                                    ? $this->translator->transConditional(
                                        "mautic.integration.common.{$fn}",
                                        "mautic.integration.{$s}.{$fn}.label"
                                    )
                                    : $label;
                            }
                        } else {
                            $fields[$field] = (!$label)
                                ? $this->translator->transConditional(
                                    "mautic.integration.common.{$fn}",
                                    "mautic.integration.{$s}.{$fn}.label"
                                )
                                : $label;
                        }
                        break;
                    case 'array_object':
                        if ('urls' == $field || 'url' == $field) {
                            foreach ($details['fields'] as $f) {
                                $fields["{$p}Urls"] = (!$label)
                                    ? $this->translator->transConditional(
                                        "mautic.integration.common.{$f}Urls",
                                        "mautic.integration.{$s}.{$f}Urls"
                                    )
                                    : $label;
                            }
                        } elseif (isset($details['fields'])) {
                            foreach ($details['fields'] as $f) {
                                $fn          = $this->matchFieldName($field, $f);
                                $fields[$fn] = (!$label)
                                    ? $this->translator->transConditional(
                                        "mautic.integration.common.{$fn}",
                                        "mautic.integration.{$s}.{$fn}.label"
                                    )
                                    : $label;
                            }
                        } else {
                            $fields[$fn] = (!$label)
                                ? $this->translator->transConditional(
                                    "mautic.integration.common.{$fn}",
                                    "mautic.integration.{$s}.{$fn}.label"
                                )
                                : $label;
                        }
                        break;
                }
            }
            if ($this->sortFieldsAlphabetically()) {
                uasort($fields, 'strnatcmp');
            }
        }

        return $fields;
    }

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->getIntegrationSettings()->getId();;
    }

    /**
     * @return string[]
     */
    public function getSupportedFeatures()
    {
        return ['push_lead'];
    }

    /**
     * @param Lead  $lead
     * @param array $config
     */
    public function pushLead(Lead &$lead, array $config = [])
    {
        $this->logger->warning('Pushing to Enhancer '.$this->getName().' Config: '.print_r($config, true));
        $this->doEnhancement($lead);
        if ($this->dispatcher->hasListeners(MauticEnhancerEvents::ENHANCER_COMPLETED)) {
            $this->logger->warning('Enhancer completed event triggered');
            //Extract campaign from $config (or other method)
            $campaign = new Campaign();  // BLOCKER: ???
            $complete = new MauticEnhancerEvent($this, $lead, $campaign);
            $this->dispatcher->dispatch(MauticEnhancerEvents::ENHANCER_COMPLETED, $complete);
        }
    }

}
