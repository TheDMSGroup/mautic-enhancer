<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticEnhancerBundle\Integration;

use DateTime;
use Exception;
use Mautic\LeadBundle\Entity\Lead;

/**
 * Class AgeFromBirthdateIntegration.
 */
class AgeFromBirthdateIntegration extends AbstractEnhancerIntegration
{
    /**
     * @return string
     */
    public function getAuthenticationType()
    {
        return 'none';
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'AgeFromBirthdate';
    }

    /**
     * @return string
     */
    public function getDisplayName()
    {
        return 'Age From Date of Birth Data Enhancer';
    }

    /**
     * @return string[]
     */
    public function getSupportedFeatures()
    {
        return ['push_lead'];
    }

    /**
     * @return array[]
     */
    protected function getEnhancerFieldArray()
    {
        $object = class_exists(
            'MauticPlugin\MauticExtendedFieldBundle\MauticExtendedFieldBundle'
        ) ? 'extendedField' : 'lead';

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
     * @param \Symfony\ComponentForm\FormBuilderInterface $builder
     * @param array $data
     * @param string $formArea
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
     * @param Lead $lead
     * @param array $config
     *
     * @return mixed|void
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
