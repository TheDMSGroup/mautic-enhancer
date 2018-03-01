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

/**
 * Class RandomIntegration
 *
 * @package \MauticPlugin\MauticEnhancerBundle\Integration
 */
class RandomIntegration extends AbstractEnhancerIntegration
{
    /**
     * {@inheritdoc}
     */
    public function getAuthenticationType()
    {
        return 'none';
    }
    
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'Random';
    }
    
    /**
     * {@inheritdoc}
     */
    public function appendToForm(&$builder, $data, $formArea)
    {
        
        if ($formArea === 'features' && !isset($data['random_field_name'])) {
            $builder->add(
                'random_field_name',
                'text',
                [
                    'label' => $this->translator->trans('mautic.plugin.random.field_name.label'),
                    'attr'  => [
                        'tooltip' => $this->translator->trans('mautic.plugin.random.field_name.tooltip'),
                    ],
                    'data' => '',
                ]
            );
        }
        elseif ($formArea === 'keys') {
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
     * {@inheritdoc}
     */
    protected function getEnhancerFieldArray()
    {
        $settings = $this->getIntegrationSettings()->getFeatureSettings();
        
        return [
             $settings['random_field_name'] => [
                'label' => 'Random Value',
                'object' => 'lead',
                'type'  => 'number'
            ]
        ];
    }
    
    /**
     * {@inheritdoc}
     */
    public function doEnhancement(Lead $lead, array $config = [])
    {
        $settings = $this->getIntegrationSettings()->getFeatureSettings();
        
        if (!$lead->getFieldValue($settings['random_field_name'])) {
            $lead->addUpdatedField(
                $settings['random_field_name'],
                rand(1, 100),
                0
            );
            $this->leadModel->saveEntity($lead);
            $this->em->flush();
        }
    }
}
