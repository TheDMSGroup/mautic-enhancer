<?php
/**
 * Created by PhpStorm.
 * User: nbush
 * Date: 4/3/18
 * Time: 4:39 PM.
 */

namespace MauticPlugin\MauticEnhancerBundle\Integration;

use Doctrine\DBAL\DBALException;
use Mautic\LeadBundle\Entity\Lead;

class CityStateFromPostalCodeIntegration extends AbstractEnhancerIntegration
{
    /**
     * @var \MauticPlugin\MauticEnhancerBundle\Model\CityStatePostalCodeModel
     */
    protected $cspcModel;

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
        return 'Fill Missing City, State/Province From Postal Code';
    }

    /**
     * @return \Mautic\CoreBundle\Model\AbstractCommonModel|\MauticPlugin\MauticEnhancerBundle\Model\CityStatePostalCodeModel
     */
    protected function getCSPCModel()
    {
        if (!isset($this->cspcModel)) {
            $this->cspcModel = $this->factory->getModel('enhancer.citystatepostalcode');
        }

        return $this->cspcModel;
    }

    /**
     * @return array
     */
    protected function getEnhancerFieldArray()
    {
        // hi-jacking build routine to create reference table
        // this will ensure the table is installed
        try {
            $this->getCSPCModel()->verifyReferenceTable();
        } catch (DBALException $e) {
            $this->logger->error($e->getMessage());
            $this->settings->setIsPublished(false);
            $this->session->getFlashBag()->add(
                'notice',
                $this->translator->trans('mautic.enhancer.integration.citystatefromzip.failure')
            );
        }
        //but at least no enhancer specific fields?
        return [];
    }

    /**
     * @param Lead $lead
     */
    public function doEnhancement(Lead &$lead)
    {
        $stop='here';

        if ((empty($lead->getCity()) or empty($lead->getState())) and !empty($lead->getZipcode())) {
            $country = $lead->getCountry();
            if (empty($country)) {
                $ipDetails = $this->factory->getIpAddress()->getIpDetails();
                $country   = isset($ipDetails['country']) ? $ipDetails['country'] : 'US';
            }

            //Mautic uses proper names, everything else use abbreviations
            if ('United States' === $country) {
                $country = 'US';
            }

            $cityStatePostalCode = $this->getCSPCModel()->getRepository()->findOneBy([
                'postalCode' => $lead->getZipcode(), 'country' => $country,
            ]);

            if (false !== $cityStatePostalCode) {
                if (empty($lead->getCity()) and !empty($cityStatePostalCode->getCity())) {
                    $this->logger->info('found city for lead '.$lead->getId());
                    $lead->addUpdatedField('city', $cityStatePostalCode->getCity());
                }

                if (empty($lead->getState()) and !empty($cityStatePostalCode->getStateProvince())) {
                    $this->logger->info('found state/province for lead '.$lead->getId());
                    $lead->addUpdatedField('state', $cityStatePostalCode->getStateProvince());
                }
            }
        }
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
                'hidden',
                [
                    'data' => true,
                ]
            );
        }
    }

    /**
     * @param $section
     *
     * @return string|void
     */
    public function getFormNotes($section)
    {
        if ('custom' === $section) {
            return $this->translator->trans('mautic.enhancer.integration.citystatefromzip.custom_note');
        }
    }
}
