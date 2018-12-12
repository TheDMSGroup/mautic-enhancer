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

use Mautic\CoreBundle\Model\AbstractCommonModel;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\UtmTag;
use MauticPlugin\MauticEnhancerBundle\Model\AnuraModel;
use Symfony\Component\Form\Extension\Core\Type\TextType;

/**
 * Class AlcazarIntegration.
 */
class AnuraIntegration extends AbstractEnhancerIntegration
{
    /* @var \MauticPlugin\MauticEnhancerBundle\Integration\NonFreeEnhancerTrait */
    use NonFreeEnhancerTrait {
        appendToForm as appendNonFreeFields;
    }

    /**
     * @var AnuraModel
     */
    protected $integrationModel;

    /**
     * @return string
     */
    public function getName()
    {
        return 'Anura';
    }

    /**
     * @return string
     */
    public function getDisplayName()
    {
        return 'Anura Suspicious Check';
    }

    /**
     * @return string
     */
    public function getAuthenticationType()
    {
        return 'keys';
    }

    /**
     * @return AnuraModel|AbstractCommonModel
     */
    public function getModel()
    {
        if (!isset($this->integrationModel)) {
            $this->integrationModel = $this->factory->getModel('enhancer.anura');
            $this->integrationModel->setup($this);
        }

        return $this->integrationModel;
    }

    /**
     * @return array
     */
    public function getRequiredKeyFields()
    {
        return [
            'endpoint' => $this->translator->trans('mautic.enhancer.integration.anura.endpoint.label'),
            'instance' => $this->translator->trans('mautic.enhancer.integration.anura.instance.label'),
        ];
    }

    /**
     * @return array
     */
    public function getSupportedFeatures()
    {
        return ['push_lead'];
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
                'default_user_agent',
                TextType::class,
                [
                    'label'      => $this->translator->trans('mautic.enhancer.integration.anura.user_agent.label'),
                    'data'       => isset($data['default_user_agent']) ? $data['default_user_agent'] : '',
                    'required'   => true,
                    'label_attr' => ['class' => 'control-label'],
                    'attr'       => [
                        'class'   => 'form-control',
                        'tooltip' => $this->translator->trans('mautic.enhancer.integration.anura.user_agent.tooltip'),
                    ],
                ]
            );

            $this->appendNonFreeFields($builder, $data, $formArea, true);
        }
    }

    /**
     * @return array|mixed
     */
    protected function getEnhancerFieldArray()
    {
        return [
            'anura_result' => [
                'label' => $this->translator->trans('mautic.enhancer.integration.anura.anura_result.label'),
                'group' => 'enhancement',
            ],
        ];
    }

    /**
     * @param Lead $lead
     *
     * @return bool
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function doEnhancement(Lead $lead)
    {
        $didEnhnacement = false;

        if (!empty($lead)) {
            $ipAddresses = $lead->getIpAddresses()->getKeys();
            if (count($ipAddresses)) {
                $ipAddress = array_pop($ipAddresses);

                $utmTags = $lead->getUtmTags();
                if (count($utmTags)) {
                    /** @var UtmTag $utmTag */
                    $utmTag    = array_pop($utmTags);
                    $userAgent = $utmTag->getUserAgent();
                } else {
                    $settings  = $this->getIntegrationSettings()->getFeatureSettings();
                    $userAgent = $settings['default_user_agent'];
                }

                $result = $this->getModel()->getResult($ipAddress, $userAgent);

                $lead->addUpdatedField('anura_result', $result);
                $didEnhnacement = true;
            }
        }

        return $didEnhnacement;
    }
}
