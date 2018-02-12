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
        return self::INTEGRATION_NAME . 'Age From Birthdate Data Enhancer';    
    }

    protected function getEnhancerFieldArray()
    {
        $settings = $this->getIntegrationSettings()->getFeatureSettings();
        
        return [
             'age_from_dob' => [
                'label' => 'Age from DoB'
            ]
        ];
    }
    
    public function doEnhancement(Lead $lead)
    {
            $this->leadModel->saveEntity($lead);
            $this->em->flush();
    }
}