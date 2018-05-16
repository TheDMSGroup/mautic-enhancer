<?php
/**
 * Created by PhpStorm.
 * User: nbush
 * Date: 5/16/18
 * Time: 11:51 AM
 */

namespace MauticEnhancerBundle\Integration;

use Mautic\LeadBundle\Entity\Lead;
use MauticPlugin\MauticEnhancerBundle\Integration\AbstractEnhancerIntegration;

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
                'type' => 'text',
            ],
            'ptp_prefix' => [
                'label' => 'Prefix',
                'type' => 'text',
            ],
            'ptp_line_number' => [
                'label' => 'Line Number',
                'type' => 'text',
            ],
        ];
    }

    /**
     * @param Lead $lead
     *
     * @return mixed
     */
    public function doEnhancement(Lead &$lead)
    {
        $phone = preg_replace('/\D+/', '', $lead->getPhone());
        if (10 === strlen($phone)) {
            $lead->addUpdatedField('ptp_area_code', substr($phone, 0, 3));
            $lead->addUpdatedField('ptp_prefix', substr($phone, 3, 3));
            $lead->addUpdatedField('ptp_line_number', substr($phone, 6, 4));
        }
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
                'hidden',
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
