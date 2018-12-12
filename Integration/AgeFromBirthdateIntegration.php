<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Digital Media Solutions, LLC
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticEnhancerBundle\Integration;

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
                'label' => 'Age',
                'type'  => 'number',
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
                \Mautic\CoreBundle\Form\Type\YesNoButtonGroupType::class,
                [
                    'label'       => $this->translator->trans('mautic.enhancer.autorun.label'),
                    'data'        => !isset($data['autorun_enabled']) ? false : $data['autorun_enabled'],
                    'required'    => false,
                    'empty_value' => false,
                    'label_attr'  => ['class' => 'control-label'],
                    'attr'        => [
                        'class'   => 'form-control',
                        'tooltip' => $this->translator->trans('mautic.enhancer.autorun.tooltip'),
                    ],
                ]
            );
        }
    }

    /**
     * @param Lead $lead
     *
     * @return bool
     */
    public function doEnhancement(Lead $lead)
    {
        $save = false;
        $this->logger->info('AgeFromBirthdate:doEnhancemet');
        // Get original field values.
        $dobStr = $dobOrig = $lead->getFieldValue('dob');
        $day    = $dayOrig = $lead->getFieldValue('dob_day');
        $month  = $monthOrig = $lead->getFieldValue('dob_month');
        $year   = $yearOrig = $lead->getFieldValue('dob_year');
        $age    = $ageOrig = $lead->getFieldValue('afb_age');
        $today  = new \DateTime();
        try {
            if ($dobOrig instanceof \DateTime) {
                // For BC.
                $dobStr = $dobOrig = $dobOrig->format('Y-m-d');
            }
            if (
                $dobStr
                && '0000-00-00' !== $dobStr
                && $today->format('Y-m-d') != $dobStr
            ) {
                // DOB field to date/month/day fields.
                $dob   = new \DateTime($dobStr);
                $day   = (int) $dob->format('d');
                $month = (int) $dob->format('m');
                $year  = (int) $dob->format('Y');
            } elseif ($yearOrig) {
                // Date/month/day fields to DOB field with normalization.
                $year = (int) $yearOrig;
                if ($year) {
                    $day   = max(1, min(31, (int) $dayOrig));
                    $month = max(1, min(12, (int) $monthOrig));
                    $dob   = new \DateTime(sprintf('%04d-%02d-%02d 00:00:00', $year, $month, $day));
                }
            } elseif ($ageOrig) {
                // @todo - Support age back to DOB estimation.
            }
        } catch (\Exception $e) {
            // Allow DateTime to fail gracefully.
        }
        // Generate age if DOB was found valid.
        try {
            if (isset($dob) && $dob) {
                $yearDiff = (int) $today->diff($dob)->y;
                if ($yearDiff > -1 && $yearDiff < 120) {
                    $age    = $yearDiff;
                    $dobStr = $dob->format('Y-m-d');
                }
            }
        } catch (\Exception $e) {
            // Dont write dob fields because weirdness in what was sent
            return false;
        }
        // See if any field values changed (intentionally not type checking).
        if ($dobStr && $dobOrig != $dobStr) {
            $lead->addUpdatedField('dob', $dobStr, $dobOrig);
            $save = true;
        }
        if ($day && $dayOrig != $day) {
            $lead->addUpdatedField('dob_day', $day, $dayOrig);
            $save = true;
        }
        if ($month && $monthOrig != $month) {
            $lead->addUpdatedField('dob_month', $month, $monthOrig);
            $save = true;
        }
        if ($year && $yearOrig != $year) {
            $lead->addUpdatedField('dob_year', $year, $yearOrig);
            $save = true;
        }
        if ($age && $ageOrig != $age) {
            $lead->addUpdatedField('afb_age', $age, $ageOrig);
            $save = true;
        }

        return $save;
    }
}
