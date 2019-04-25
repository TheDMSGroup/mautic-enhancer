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

use Exception;
use Mautic\CoreBundle\Helper\PhoneNumberHelper;
use Mautic\CoreBundle\Model\AbstractCommonModel;
use Mautic\LeadBundle\Entity\Lead;
use MauticPlugin\MauticEnhancerBundle\Entity\PluginEnhancerBlacklist;
use MauticPlugin\MauticEnhancerBundle\Model\BlacklistModel;

/**
 * Class BlacklistIntegration.
 */
class BlacklistIntegration extends AbstractEnhancerIntegration
{
    /* @var \MauticPlugin\MauticEnhancerBundle\Integration\NonFreeEnhancerTrait */
    use NonFreeEnhancerTrait {
        appendToForm as appendNonFreeFields;
    }

    /** @var int Default number of minutes to consider a blacklist lookup valid, before making the call again. */
    const AGE_DEFAULT = 1440;

    /** @var string Default URL to check a single Phone number against The Blacklist service */
    const ENDPOINT_DEFAULT = 'https://api.theblacklist.click/standard/api/v1';

    /**
     * @var BlacklistModel
     */
    protected $integrationModel;

    /** @var PhoneNumberHelper */
    private $phoneHelper;

    /**
     * @return string
     */
    public function getName()
    {
        return 'Blacklist';
    }

    /**
     * @return string
     */
    public function getDisplayName()
    {
        return 'Blacklist Check';
    }

    /**
     * @return string
     */
    public function getAuthenticationType()
    {
        return 'keys';
    }

    /**
     * @return array
     */
    public function getRequiredKeyFields()
    {
        return [
            'key' => $this->translator->trans('mautic.enhancer.integration.blacklist.key.label'),
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
                'endpoint',
                'text',
                [
                    'label'      => $this->translator->trans('mautic.enhancer.integration.blacklist.endpoint.label'),
                    'data'       => !empty($data['endpoint']) ? $data['endpoint'] : self::ENDPOINT_DEFAULT,
                    'required'   => true,
                    'label_attr' => ['class' => 'control-label'],
                    'attr'       => [
                        'class'   => 'form-control',
                        'tooltip' => $this->translator->trans('mautic.enhancer.integration.blacklist.endpoint.tooltip'),
                    ],
                ]
            );

            $builder->add(
                'age',
                'text',
                [
                    'label'      => $this->translator->trans('mautic.enhancer.integration.blacklist.age.label'),
                    'data'       => !empty($data['age']) ? $data['age'] : self::AGE_DEFAULT,
                    'required'   => true,
                    'label_attr' => ['class' => 'control-label'],
                    'attr'       => [
                        'class'   => 'form-control',
                        'tooltip' => $this->translator->trans('mautic.enhancer.integration.blacklist.age.tooltip'),
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
            'blacklist_result'   => [
                'label' => $this->translator->trans('mautic.enhancer.integration.blacklist.blacklist_result.label'),
                'type'  => 'boolean',
                'group' => 'enhancement',
            ],
            'blacklist_code'     => [
                'label' => $this->translator->trans('mautic.enhancer.integration.blacklist.blacklist_code.label'),
                'group' => 'enhancement',
            ],
            'blacklist_wireless' => [
                'label' => $this->translator->trans('mautic.enhancer.integration.blacklist.blacklist_wireless.label'),
                'type'  => 'boolean',
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
        $save = false;

        if (!empty($lead)) {
            // Only perform lookup on the first occurring phone number, home and then (if not found) mobile.
            // Presumably phone numbers should be validated by this point, but let's make sure, just in case.
            $phone = $this->phoneValidate($lead->getPhone());
            if (!$phone) {
                $phone = $this->phoneValidate($lead->getMobile());
            }
            if ($phone) {
                $settings   = $this->getIntegrationSettings()->getFeatureSettings();
                $ageMinutes = isset($settings['age']) ? intval($settings['age']) : self::AGE_DEFAULT;

                try {
                    /** @var PluginEnhancerBlacklist $record */
                    $record = $this->getModel()->getRecord($phone, $ageMinutes);
                } catch (Exception $exception) {
                    $this->handleEnchancerException('Blacklist', $exception);
                    $this->logger->error('Blacklist Enhancer: '.$exception->getMessage());
                }
                if ($record) {
                    $lead->addUpdatedField('blacklist_result', $record->getResult());
                    $lead->addUpdatedField('blacklist_code', $record->getCode());
                    $lead->addUpdatedField('blacklist_wireless', $record->getWireless());
                    $save = true;
                }
            }
        }

        return $save;
    }

    /**
     * Get the old E164 format, regardless of how the number came in.
     *
     * @param $phone
     *
     * @return string
     */
    private function phoneValidate($phone)
    {
        $result = null;
        $phone  = trim($phone);
        if (!empty($phone)) {
            if (!$this->phoneHelper) {
                $this->phoneHelper = new PhoneNumberHelper();
            }
            try {
                $phone = $this->phoneHelper->format($phone);
                if (!empty($phone)) {
                    $result = $phone;
                }
            } catch (\Exception $e) {
            }
        }

        return $result;
    }

    /**
     * @return BlacklistModel|AbstractCommonModel
     */
    public function getModel()
    {
        if (!isset($this->integrationModel)) {
            $this->integrationModel = $this->factory->getModel('enhancer.blacklist');
            $this->integrationModel->setup($this);
        }

        return $this->integrationModel;
    }
}
