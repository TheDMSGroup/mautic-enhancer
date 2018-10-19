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

use Mautic\CoreBundle\Form\Type\YesNoButtonGroupType;
use Mautic\CoreBundle\Model\AbstractCommonModel;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\UtmTag;
use MauticEnhancerBundle\Model\AnuraModel;
use Symfony\Component\Form\Extension\Core\Type\TextType;

/**
 * Class AlcazarIntegration.
 */
class AnuraIntegration extends AbstractEnhancerIntegration
{    /* @var \MauticPlugin\MauticEnhancerBundle\Integration\NonFreeEnhancerTrait */
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
            $this->integrationModel = $this->factory->getModel('mautic.enhancer.model.anura');
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
        $builder
            ->add(
                'dafault_user_agent',
                TextType::class,
                [
                    'label'       => $this->translator->trans('mautic.enhancer.integration.anura.user_agent.label'),
                    'data'        => isset($data['dafault_user_agent']) ? $data['dafault_user_agent'] : '',
                    'required'    => true,
                    'empty_value' => false,
                    'label_attr'  => ['class' => 'control-label'],
                    'attr'        => [
                        'class'   => 'form-control',
                        'tooltip' => $this->translator->trans('mautic.enhancer.integration.anura.user_agent.tooltip'),
                    ],

                ]
            )
            ->add(
                'autorun_enabled',
                YesNoButtonGroupType::class,
                [
                    'label'       => $this->translator->trans('mautic.enhancer.autorun.label'),
                    'data'        => !isset($data['autorun_enabled']) ? false : $data['autorun_enabled'],
                    'required'    => false,
                    'empty_value' => false,
                    'label_attr'  => ['class' => 'control-label'],
                    'attr'        => [
                        'class'   => 'form-control',
                        'tooltip' => $this->translator->trans('mautic.enhancer.autorun.tooltip'),
                    ],
                ]
            );

        $this->appendNonFreeFields($builder, $data, $formArea);
    }

    /**
     * @return array|mixed
     */
    protected function getEnhancerFieldArray()
    {
        return [
            'anura_is_suspicious' => [
                'label' => $this->translator->trans('mautic.enhancer.integration.anura.is_suspicious.label'),
            ],
        ];
    }

    /**
     * @param Lead $lead
     *
     * @return bool
     */
    public function doEnhancement(Lead &$lead)
    {
        if (!empty($lead)) {
            $ipAddress = null;
            $userAgent = 'default';

            $ipAddresses = $lead->getIpAddresses();
            if (is_array($ipAddresses)) {
                $ipAddress = array_pop($ipAddresses);
            }

            $utmTags = $lead->getUtmTags();
            if (is_array($utmTags)) {
                /** @var UtmTag $lastTags */
                $lastTags = array_pop($utmTags);
                if ($lastTags->getUserAgent()) {
                    $userAgent = $lastTags->getUserAgent();
                }
            }

            $lead->addUpdatedField('anuraIsSuspicious', $this->getModel()->isSuspect($ipAddresses, $userAgent));
        }

        return true;
    }
}
