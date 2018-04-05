<?php
/**
 * Created by PhpStorm.
 * User: nbush
 * Date: 4/3/18
 * Time: 4:39 PM
 */

namespace MauticPlugin\MauticEnhancerBundle\Integration;

use Mautic\LeadBundle\Entity\Lead;
use MauticPlugin\MauticEnhancerBundle\Integration\AbstractEnhancerIntegration;

class CityStateFromZipIntegration extends AbstractEnhancerIntegration
{
    public function getName()
    {
        return 'CityStateFromZip';
    }

    public function getDisplayName()
    {
        return 'Fill Missing City, State From Zipcode';
    }

    protected function getEnhancerFieldArray()
    {
        return [];
    }

    public function doEnhancement(Lead &$lead)
    {
        if (!($lead->getCity() && $lead->getState()) && $lead->getZipcode()) {
            /** @var \MauticPlugin\MauticEnhancerBundle\Entity\PluginEnhancerCityStateZipRepository $repo */
            $repo = $this->em->getRepository('\MauticPlugin\MauticEnhancerBundle\Entity\PluginEnhancerCityStateZip');

            $cityStateZip = $repo->findOneBy(['zip_code' => $lead->getZipcode()]);
            if ($cityStateZip) {
                if (!$lead->getCity()) {
                    $lead->addUpdatedField('city', $cityStateZip->getCity());
                }
                if (!$lead->getState()) {
                    $lead->addUpdatedField('state', $cityStateZip->getState());
                }
            }
        }

    }

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
}