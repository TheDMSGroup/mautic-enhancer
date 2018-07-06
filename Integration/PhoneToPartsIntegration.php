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
 * Class PhoneToPartsIntegration.
 */
class PhoneToPartsIntegration extends AbstractEnhancerIntegration
{
    /**
     * @return string
     */
    public function getName()
    {
        return 'PhoneToParts';
    }

    /**
     * @return string
     */
    public function getDisplayName()
    {
        return 'Split Phone Into Parts';
    }

    /**
     * @returns array[]
     */
    protected function getEnhancerFieldArray()
    {
        return [
            'ptp_area_code'   => [
                'label' => 'Area Code',
            ],
            'ptp_prefix'      => [
                'label' => 'Prefix',
            ],
            'ptp_line_number' => [
                'label' => 'Line Number',
            ],
        ];
    }

    /**
     * @param Lead $lead
     *
     * @return bool
     */
    public function doEnhancement(Lead &$lead)
    {
        $phone = preg_replace('/\D+/', '', $lead->getPhone());

        if ((11 === strlen($phone)) && (0 === strpos($phone, '1'))) {
            $phone = substr($phone, 1);
        }

        if (10 === strlen($phone)) {
            $lead->addUpdatedField('ptp_area_code', substr($phone, 0, 3));
            $lead->addUpdatedField('ptp_prefix', substr($phone, 3, 3));
            $lead->addUpdatedField('ptp_line_number', substr($phone, 6, 4));

            return true;
        }

        return false;
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
                \Symfony\Component\Form\Extension\Core\Type\HiddenType::class,
                [
                    'data' => true,
                ]
            );
        }
    }

    /**
     * Get the type of authentication required for this API.  Values can be none, key, oauth2 or callback
     * (will call $this->authenticationTypeCallback).
     *
     * @return string
     */
    public function getAuthenticationType()
    {
        return 'none';
    }
}
