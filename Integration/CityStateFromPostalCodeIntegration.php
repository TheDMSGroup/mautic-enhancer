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
use MauticPlugin\MauticEnhancerBundle\Entity\PluginEnhancerCityStatePostalCode;

/**
 * Class CityStateFromPostalCodeIntegration.
 */
class CityStateFromPostalCodeIntegration extends AbstractEnhancerIntegration
{
    /**
     * @var \MauticPlugin\MauticEnhancerBundle\Model\CityStatePostalCodeModel
     */
    protected $integrationModel;

    /**
     * @return string
     */
    public function getName()
    {
        return 'CityStateFromPostalCode';
    }

    /**
     * @return string
     */
    public function getDisplayName()
    {
        return 'Fill Missing City, State/Province and County From Postal Code';
    }

    /**
     * @return array
     */
    protected function getEnhancerFieldArray()
    {
        // hi-jacking build routine to create reference table
        // this will ensure the table is installed
        try {
            $this->getIntegrationModel()->verifyReferenceTable();
        } catch (\Exception $e) {
            $this->logger->error('CityStateFromPostalCode: '.$e->getMessage());
            $this->settings->setIsPublished(false);
            $this->session->getFlashBag()->add(
                'notice',
                $this->translator->trans('mautic.enhancer.integration.citystatefromzip.failure')
            );
        }

        return [
            'county' => [
                'label' => 'County',
                'type'  => 'text',
            ],
        ];
    }

    /**
     * @return \Mautic\CoreBundle\Model\AbstractCommonModel|\MauticPlugin\MauticEnhancerBundle\Model\CityStatePostalCodeModel
     */
    protected function getIntegrationModel()
    {
        if (!isset($this->integrationModel)) {
            $this->integrationModel = $this->factory->getModel('enhancer.citystatepostalcode');
        }

        return $this->integrationModel;
    }

    /**
     * @param Lead $lead
     *
     * @return bool
     */
    public function doEnhancement(Lead &$lead)
    {
        $persist = false;
        if (
            (empty($lead->getCity()) || empty($lead->getState()))
            && !empty($lead->getZipcode())
        ) {
            $country = $lead->getCountry();

            if (empty($country)) {
                $ipDetails = $this->factory->getIpAddress()->getIpDetails();
                $country   = isset($ipDetails['country']) ? $ipDetails['country'] : 'US';
            }
            //Mautic uses proper names, everything else use abbreviations
            if ('United States' === $country) {
                $country = 'US';
            }

            /** @var PluginEnhancerCityStatePostalCode $cityStatePostalCode */
            $cityStatePostalCode = $this->getIntegrationModel()->getRepository()->findOneBy(
                [
                    'postalCode' => $lead->getZipcode(),
                    'country'    => $country,
                ]
            );

            if (null !== $cityStatePostalCode) {
                if (empty($lead->getCity()) && !empty($cityStatePostalCode->getCity())) {
                    $this->logger->debug('CityStateFromPostalCode: Found city for lead '.$lead->getId());
                    $lead->addUpdatedField('city', $cityStatePostalCode->getCity());
                    $persist = true;
                }

                if (empty($lead->getState()) && !empty($cityStatePostalCode->getStateProvince())) {
                    $this->logger->debug('CityStateFromPostalCode: Found state/province for lead '.$lead->getId());
                    $lead->addUpdatedField('state', $cityStatePostalCode->getStateProvince());
                    $persist = true;
                }

                if (empty($lead->getFieldValue('county')) && !empty($cityStatePostalCode->getCounty())) {
                    $this->logger->debug('CityStateFromPostalCode: Found county for lead '.$lead->getId());
                    $lead->addUpdatedField('county', $cityStatePostalCode->getCounty());
                    $persist = true;
                }
            }
        }

        return $persist;
    }

    /**
     * @return string
     */
    public function getAuthenticationType()
    {
        return 'none';
    }

    /**
     * @param \Symfony\Component\Form\FormBuilderInterface $builder
     * @param array                                        $data
     * @param string                                       $formArea
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
     * @param $section
     *
     * @return mixed
     */
    public function getFormNotes($section)
    {
        if ('custom' === $section) {
            return $this->translator->trans('mautic.enhancer.integration.citystatefromzip.custom_note');
        }
    }
}
