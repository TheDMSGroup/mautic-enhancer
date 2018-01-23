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
use Mautic\LeadBundle\Model\FieldModel;
use Mautic\LeadBundle\Entity\Lead;


abstract class AbstractEnhancerIntegration extends AbstractIntegration
{
    // Integrations of this type should use this constant to define the name
    const INTEGRATION_NAME = null;
   
    /**
     * This class does not implement the core abstract methods:
     *  getAuthenticationType
     *  getName
     */
     
    
    abstract protected function getEnhancerFieldArray();
    abstract public function doEnhancement(Lead $lead);
    
    protected $integration_helper;
    
    public function buildEnhancerFields()
    {
        $creating = $this->getEnhancerFieldArray();
        $created = [];
        
        //TODO: error trapping/ column consideration
        foreach ($creating as $alias => $properties) {
            
            error_log("Building $alias");
                     
            $new_field = $this->fieldModel->getEntity();
            $new_field->setAlias($alias);
            
            foreach ($properties as $property => $value) {
                
                error_log("Setting $property to $value");  
                
                $method = "set" . implode('', array_map('ucfirst', explode('_',$property)));                
                error_log("Attempting to call $alias->$method($value)");
                
                try {
                    $new_field->$method($value);
                } catch(Exception $e) {
                    error_log('Failed with "' . $e->getMessage() . '"');
                    continue;
                }
            }
            
            error_log("Saving LeadField");
            $this->fieldModel->saveEntity($new_field);
            $this->em->flush();
            
            $created[] = $alias;
        }
        error_log(print_r($created));
        return empty(array_diff(array_keys($creating), $created));
    }
}