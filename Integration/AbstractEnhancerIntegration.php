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


abstract class AbstractEnhancerIntegration extends AbstractIntegration
{
    // Integrations of this type should use this constant to define the name
    // const INTEGRATION_NAME = null;
   
    /**
     * This class does not implement the core abstract methods:
     *  getAuthenticationType
     *  getName
     */

    abstract protected function getEnhancerFieldArray();
    abstract public function doEnhancement(Lead $lead);
    
    public function buildEnhancerFields()
    {
        $integration = $this->getIntegrationSettings();
        
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
            $s         = $this->getName();
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
                            ? $this->translator->transConditional("mautic.integration.common.{$fn}", "mautic.integration.{$s}.{$fn}.label")
                            : $label;
                        break;
                    case 'object':
                        if (isset($details['fields'])) {
                            foreach ($details['fields'] as $f) {
                                $fn          = $this->matchFieldName($field, $f);
                                $fields[$fn] = (!$label)
                                    ? $this->translator->transConditional("mautic.integration.common.{$fn}", "mautic.integration.{$s}.{$fn}.label")
                                    : $label;
                            }
                        } else {
                            $fields[$field] = (!$label)
                                ? $this->translator->transConditional("mautic.integration.common.{$fn}", "mautic.integration.{$s}.{$fn}.label")
                                : $label;
                        }
                        break;
                    case 'array_object':
                        if ($field == 'urls' || $field == 'url') {
                            foreach ($details['fields'] as $f) {
                                $fields["{$p}Urls"] = (!$label)
                                    ? $this->translator->transConditional("mautic.integration.common.{$f}Urls", "mautic.integration.{$s}.{$f}Urls")
                                    : $label;
                            }
                        } elseif (isset($details['fields'])) {
                            foreach ($details['fields'] as $f) {
                                $fn          = $this->matchFieldName($field, $f);
                                $fields[$fn] = (!$label)
                                    ? $this->translator->transConditional("mautic.integration.common.{$fn}", "mautic.integration.{$s}.{$fn}.label")
                                    : $label;
                            }
                        } else {
                            $fields[$fn] = (!$label)
                                ? $this->translator->transConditional("mautic.integration.common.{$fn}", "mautic.integration.{$s}.{$fn}.label")
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