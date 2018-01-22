<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticEnhancerBundle\Integration;

use Mautic\LeadBundle\Entity\Lead;

class RandomIntegration extends AbstractEnhancerIntegration
{
    const INTEGRATION_NAME = 'Random';
    
    public function getAuthenticationType()
    {
        return 'none';
    }
    
    /**
     * @param FormBuilder|Form $builder
     * @param array            $data
     * @param string           $formArea
     */
    public function appendToForm(&$builder, $data, $formArea)
    {
        if ($formArea === 'features' && !$this->helper->getRandomField()) {
            $builder->add(
                'random_field_name',
                'text',
                [
                    'label' => 'mautic.plugin.random.field_name',
                    'attr'  => [
                        'tooltip' => 'mautic.plugin.random.field_name.tooltip',
                    ],
                    'data' => $data['random_field_name'],
                ]
            );
        }
    }
    
    protected function getEnhancerFieldArray()
    {
        return [
            $this->settings['random_field_name'] => [
                'label' => 'Random Value'
            ]
        ];
    }
    
    public function doEnhancement(Lead $lead)
    {
        if (!$lead->getField($this->settings['random_field_name'])) {
            $lead->addUpdatedField($this->settings['random_field_name']);
        }
    }
}
