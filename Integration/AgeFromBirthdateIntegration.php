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
     * @return bool
     */
    public function doEnhancement(Lead &$lead)
    {
        $this->logger->info('AgeFromBirthdate:doEnhancemet');
        $year  = intval($lead->getFieldValue('dob_year'));
        $month = intval($lead->getFieldValue('dob_month'));
        $day   = intval($lead->getFieldValue('dob_day'));

        if ($year && $month && $month <= 12 && $day && $day <= 31) {
            $birthdate = sprintf('%04d-%02d-%02d 00:00:00', $year, $month, $day);
            $dob       = new DateTime($birthdate);
            $today     = new DateTime();
            $age       = (int) $today->diff($dob)->y;
            $prevAge   = (int) $lead->getFieldValue('afb_age');
            if ($age !== $prevAge && $age < 120) {
                $this->logger->info("calculated age is $age");
                $lead->addUpdatedField('afb_age', $age, $prevAge);

                return true;
            }
        }

        return false;
    }
}
