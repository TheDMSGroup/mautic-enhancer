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

        $zipcode = $lead->getZipcode();
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
        $builder
            ->add(
                'resource',
                'text',
                [
                    'data' => $data['resource']
                ]
            )
            ->add(
                'import',
                'yesno_button_group',
                [
                    'data' => $data['import']
                ]
            );

    }
}