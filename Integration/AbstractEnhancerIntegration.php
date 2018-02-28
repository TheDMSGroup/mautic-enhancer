<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Nicholai Bush <nbush@thedmsgrp.com>
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticEnhancerBundle\Integration;

use Mautic\PluginBundle\Integration\AbstractIntegration;
use Mautic\PluginBundle\Helper\IntegrationHelper;
use Mautic\PluginBundle\Entity\Integration;
use Mautic\LeadBundle\Model\FieldModel;
use Mautic\LeadBundle\Entity\Lead;


/**
 * Class AbstractEnhancerIntegration
 *
 * @package \MauticPlugin\MauticEnhancerBundle\Integration
 * @see \Mautic\PluginBundle\Integration\AbstractIntegration 
 *
 * This class also requires implementation of these abstract methods:
 *
 * @method getAuthenticationType()
 * @method getName()
 *  
 */
abstract class AbstractEnhancerIntegration extends AbstractIntegration
{
    /**
     * Provides an array of fields that will be created as Custom Fields when the plugin is activated and feature-specific settings are needed
     *
     * @returns array[]
     */
    abstract protected function getEnhancerFieldArray();
    
    /**
     * Performs the plugin's enhancemnt on the current Lead/Contact
     *
     * @param \Mautic\LeadBundle\Entity\Lead
     *
     * @return bool true if enhancemnt considered itself successful, otherwise false
     */
    abstract public function doEnhancement(Lead $lead);

    /**
     * {@inheritdoc}
     */
    public function getDisplayName()
    {
        $spaced_name = preg_replace('/([a-z])([A-Z])/', "$1 $2", $this->getName());
        return sprintf('%s Data Enhancer', $spaced_name);    
    }
        
    /**
     * {@inheritdoc}
     */
    public function pushLead(Lead $lead, array $config = [])
    {
        $this->doEnhancement($lead);    
    }
      
    /**
     * Creates the required fields
     * TODO: Unpublish the deconfigured feature fields
     */
    public function buildEnhancerFields()
    {
        $integration = $this->getIntegrationSettings();
        
        $count = count($this->fieldModel->getLeadFields());
        
        if ($integration->getIsPublished()) {
            $feature_settings = $integration->getFeatureSettings();
            $created =  isset($feature_settings['installed']) ? $feature_settings['installed'] : []; 
            $creating = $this->getEnhancerFieldArray();
            
            foreach ($creating as $alias => $properties) {
                
                if (in_array($alias, $created)) {
                    //do not build an existing column
                    continue;
                }
                
                $new_field = $this->fieldModel->getEntity();
                $new_field->setAlias($alias);
                $new_field->setOrder(++$count);
                
                foreach ($properties as $property => $value) {
                    
                    $method = "set" . implode('', array_map('ucfirst', explode('_',$property)));                
                    
                    try {
                        $new_field->$method($value);
                    } catch(Exception $e) {
                        error_log('Failed with "' . $e->getMessage() . '"');
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
     * {@inheritdoc}
     *
     * @param array $settings
     *
     * @return array
     */
    public function getFormLeadFields($settings = [])
    {
        static $fields = [];

        if (empty($fields)) {
            $name = $this->getName();
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
                            ? $this->translator->transConditional("mautic.integration.common.{$fn}", "mautic.integration.{$name}.{$fn}.label")
                            : $label;
                        break;
                    case 'object':
                        if (isset($details['fields'])) {
                            foreach ($details['fields'] as $f) {
                                $fn          = $this->matchFieldName($field, $f);
                                $fields[$fn] = (!$label)
                                    ? $this->translator->transConditional("mautic.integration.common.{$fn}", "mautic.integration.{$name}.{$fn}.label")
                                    : $label;
                            }
                        } else {
                            $fields[$field] = (!$label)
                                ? $this->translator->transConditional("mautic.integration.common.{$fn}", "mautic.integration.{$name}.{$fn}.label")
                                : $label;
                        }
                        break;
                    case 'array_object':
                        if ($field == 'urls' || $field == 'url') {
                            foreach ($details['fields'] as $f) {
                                $fields["{$p}Urls"] = (!$label)
                                    ? $this->translator->transConditional("mautic.integration.common.{$f}Urls", "mautic.integration.{$name}.{$f}Urls")
                                    : $label;
                            }
                        } elseif (isset($details['fields'])) {
                            foreach ($details['fields'] as $f) {
                                $fn          = $this->matchFieldName($field, $f);
                                $fields[$fn] = (!$label)
                                    ? $this->translator->transConditional("mautic.integration.common.{$fn}", "mautic.integration.{$name}.{$fn}.label")
                                    : $label;
                            }
                        } else {
                            $fields[$fn] = (!$label)
                                ? $this->translator->transConditional("mautic.integration.common.{$fn}", "mautic.integration.{$name}.{$fn}.label")
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

}