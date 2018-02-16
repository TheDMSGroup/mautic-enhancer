<?php

namespace MauticPlugin\MauticEnhancerBundle\Integration;

use Mautic\LeadBundle\Entity\Lead;

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
        return 'Age From Date of Birth Data Enhancer';    
    }

    public function getSupportedFeatures()
    {
        return ['push_lead'];
    }

    protected function getEnhancerFieldArray()
    {
      $object = class_exists('MauticPlugin\MauticExtendedFieldBundle\MauticExtendedFieldBundle') ? 'extendedField' : 'lead';

      return [
            'afb_age' => [
               'label' => 'Age (D.o.B.)',
               'type' => 'number',
               'object' => $object
            ],
            'afb_dob' => [
                'label' => 'D.o.B.',
                'type' =>  'date',
                'object' => $object
            ],
        ];
    }
    
    public function doEnhancement(Lead $lead)
    {
        //field name can be dynamic, with the field name picked up througn the config
        // see the random plugin
        try {
            $dob = $lead->getFieldValue('afb_dob');
            if (isset($dob)) {
                $today = new DateTime();
                $age = $today->diff($dob)->format('%y');
                if ($lead->getFieldValue('afb_age') !== $age) {
                    $lead->addUpdatedField('afb_age', $age, $lead->getFieldValue('afb_age'));
                    $this->leadModel->saveEntity($lead);
                    $this->em->flush();
                }
            }
        } catch (Exception $e) {
            
        }
    }
}