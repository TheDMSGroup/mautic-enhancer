<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticEnhancerBundle\Integration;

use Mautic\LeadBundle\Entity\Lead;

/**
 * Class RandomIntegration.
 */
class RandomIntegration extends AbstractEnhancerIntegration
{
    /**
     * @return string
     */
    public function getAuthenticationType()
    {
        return 'none';
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'Random';
    }

    /**
     * @return string
     */
    public function getDisplayName()
    {
        return 'Generate Random Number Token';
    }

    /**
     * @param \Symfony\Component\Form\FormBuilderInterface $builder
     * @param array                                        $data
     * @param string                                       $formArea
     */
    public function appendToForm(&$builder, $data, $formArea)
    {
        if ('features' === $formArea && !isset($data['random_field_name'])) {
            $builder->add(
                'random_field_name',
                'text',
                [
                    'label' => $this->translator->trans('mautic.plugin.random.field_name.label'),
                    'attr'  => [
                        'tooltip' => $this->translator->trans('mautic.plugin.random.field_name.tooltip'),
                    ],
                    'data'  => '',
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
     * @return array[]
     */
    protected function getEnhancerFieldArray()
    {
        $settings = $this->getIntegrationSettings()->getFeatureSettings();

        return [
            $settings['random_field_name'] => [
                'label'  => 'Random Value',
                'object' => 'lead',
                'type'   => 'number',
            ],
        ];
    }

    /**
     * @param Lead $lead
     * @param array $config
     *
     * @return mixed|void
     *
     * @throws \Doctrine\ORM\OptimisticLockException
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
