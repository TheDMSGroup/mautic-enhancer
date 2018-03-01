<?php

namespace MauticPlugin\MauticEnhancerBundle\Integration;

use Mautic\LeadBundle\Entity\Lead;

/**
 * Class AgeFromBirthdateIntegration
 *
 * @package \MauticPlugin\MauticEnhancerBundle
 */
class AgeFromBirthdateIntegration extends AbstractEnhancerIntegration
{
    /**
     * {@inheritdoc}
     */
    public function getAuthenticationType()
    {
        return 'none';
    }
    
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'AgeFromBirthdate';
    }
    
    /**
     * {@inheritdoc}
     */
    public function getSupportedFeatures()
    {
        return ['push_lead'];
    }

    /**
     * {@inheritdoc}
     */
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
    
    /**
     * {@inheritdoc}
     */
    public function appendToForm(&$builder, $data, $formArea)
    {
        if ($formArea === 'keys') {
            $builder->add(
                'autorun_enabled',
                'hidden',
                [
                    'data' => true,
                ]
            );
        }        
    }
    
    /**
     * {@inheritdoc}
     */
    public function doEnhancement(Lead $lead, array $config = [])
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