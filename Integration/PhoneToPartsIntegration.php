<?php
/**
 * Created by PhpStorm.
 * User: nbush
 * Date: 5/16/18
 * Time: 11:51 AM.
 */

namespace MauticPlugin\MauticEnhancerBundle\Integration;

use Mautic\LeadBundle\Entity\Lead;

class PhoneToPartsIntegration extends AbstractEnhancerIntegration
{
    public function getName()
    {
        return 'PhoneToParts';
    }

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
            'ptp_areacode' => [
                'label' => 'Area Code',
            ],
            'ptp_prefix' => [
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
        if (10 === strlen($phone)) {
            $lead->addUpdatedField('ptp_area_code', substr($phone, 0, 3));
            $lead->addUpdatedField('ptp_prefix', substr($phone, 3, 3));
            $lead->addUpdatedField('ptp_line_number', substr($phone, 6, 4));
        }

        return true;
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
