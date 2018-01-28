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
            
            //TODO: error trapping/ column consideration
            foreach ($creating as $alias => $properties) {
                if (in_array($alias, $created)) {
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
}