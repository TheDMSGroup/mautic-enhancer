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
     * @return array[]
     */
    protected function getEnhancerFieldArray()
    {
        return [
            'afb_age' => [
                'label'  => 'Age (D.o.B.)',
                'type'   => 'number',
            ],
            'afb_dob' => [
                'label'  => 'D.o.B.',
                'type'   => 'date',
            ],
        ];
    }

    /**
     * @param \Symfony\Component\Form\FormBuilder $builder
     * @param array                               $data
     * @param string                              $formArea
     */
    public function appendToForm(&$builder, $data, $formArea)
    {
        if ('features' === $formArea) {
            $builder->add(
                'autorun_enabled',
                'yesno_button_group',
                [
                    'label'       => $this->translator->trans('mautic.integration.autorun.label'),
                    'data'        => !isset($data['autorun_enabled']) ? false : $data['autorun_enabled'],
                    'required'    => false,
                    'empty_value' => false,
                    'label_attr'  => ['class' => 'control-label'],
                    'attr'        => [
                        'class'   => 'form-control',
                        'tooltip' => $this->translator->trans('mautic.integration.autorun.tooltip'),
                    ],
                ]
            );
        }
    }

    /**
     * @param Lead $lead
     *
     * @return mixed|void
     */
    public function doEnhancement(Lead &$lead)
    {
        if (!empty($lead)) {
            // Field name can be dynamic, with the field name picked up through the config
            // see the random plugin.
            $dob = $lead->getFieldValue('afb_dob');
            if (isset($dob)) {
                $today = new DateTime();
                $age   = $today->diff($dob)->format('%y');
                if ($lead->getFieldValue('afb_age') !== $age) {
                    $lead->addUpdatedField('afb_age', $age, $lead->getFieldValue('afb_age'));
                    $this->saveLead($lead);
                }
            }
        }
    }
}
