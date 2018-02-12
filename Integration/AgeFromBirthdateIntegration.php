<?php

namespace MauticPlugin\MauticEnhancerBundle\Integration;

class AgeFromBirthdateIntegration extends AbstractEnhancerIntegration
{
    const INTEGRATION_NAME = 'AgeFromBirthdate';
    
    public function getAuthenticationType()
    {
        return 'none';
    }
    
    public function getName()
    {
        return self::INTEGRATION_NAME;
    }
    
    public function getDisplayName()
    {
        return self::INTEGRATION_NAME . 'Age From Date of Birth Data Enhancer';    
    }

    protected function getEnhancerFieldArray()
    {
        
        return [
            'afb_age' => [
               'label' => 'Age from DoB'
            ],
            'afb_dob' => [
                'label' => 'DoB',
                'type' =>  BirthdayType::class,
                
            ],
        
        ];
    }
    
    public function doEnhancement(Lead $lead)
    {
        //field name can be dynamic, with the field name picked up througn the config
        // see the random plugin
        try {
            $dob = $lead->getFieldValue('afb_dob');
            $age = $lead->getFieldValue('afb_age');
        
            $this->leadModel->saveEntity($lead);
            $this->em->flush();
        } catch (Exception $e) {
            
        }
    }
}