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
    
    public function getName()
    {
        return self::INTEGRATION_NAME;
    }
    
    public function getDisplayName()
    {
        return self::INTEGRATION_NAME . ' Data Enhancer';    
    }

    /**
     * @param FormBuilder|Form $builder
     * @param array            $data
     * @param string           $formArea
     */
    public function appendToForm(&$builder, $data, $formArea)
    {
        if ($formArea === 'features' && !$data['random_field_name']) {
            $builder->add(
                'random_field_name',
                'text',
                [
                    'label' => 'mautic.plugin.random.field_name',
                    'attr'  => [
                        'tooltip' => 'mautic.plugin.random.field_name.tooltip',
                    ],
                    'data' => '',
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
        if (!$lead->getFieldValue($this->get)) {
            $lead->addUpdatedField(
                $this->settings['random_field_name'],
                rand(1, 100),
                0
            );
        }
    }
}
