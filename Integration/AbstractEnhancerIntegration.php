<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Nicholai Bush <nbush@thedmsgrp.com>
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticEnhancerBundle\Integration;

use Mautic\LeadBundle\Entity\Lead;
use Mautic\PluginBundle\Exception\ApiErrorException;
use Mautic\PluginBundle\Integration\AbstractIntegration;
use MauticPlugin\MauticEnhancerBundle\Event\ContactLedgerContextEvent;
use MauticPlugin\MauticEnhancerBundle\Event\MauticEnhancerEvent;
use MauticPlugin\MauticEnhancerBundle\MauticEnhancerEvents;

/**
 * Class AbstractEnhancerIntegration.
 *
 * @method string getAuthorizationType()
 * @method string getName()
 */
abstract class AbstractEnhancerIntegration extends AbstractIntegration
{
    /** @var array */
    protected $config;

    /** @var \Mautic\CampaignBundle\Entity\Campaign */
    protected $campaign;

    /**
     * @throws \Doctrine\DBAL\DBALException
     */
    public function buildEnhancerFields()
    {
        $integration = $this->getIntegrationSettings();

        $count = count($this->fieldModel->getLeadFields());

        if ($integration->getIsPublished()) {
            $feature_settings = $integration->getFeatureSettings();
            $created          = isset($feature_settings['installed']) ? $feature_settings['installed'] : [];
            $creating         = $this->getEnhancerFieldArray();
            $fieldList        = $this->fieldModel->getFieldList(false);

            foreach ($creating as $alias => $properties) {
                if (in_array($alias, $created) && in_array($alias, $fieldList)) {
                    // The field already exists.
                    continue;
                }

                $new_field = $this->fieldModel->getEntity();
                $new_field->setAlias($alias);
                $new_field->setOrder(++$count);
                //set extended/lead in one place,
                $new_field->setObject(self::getObjectName());

                foreach ($properties as $property => $value) {
                    $method = 'set'.implode('', array_map('ucfirst', explode('_', $property)));

                    try {
                        $new_field->$method($value);
                    } catch (\Exception $e) {
                        error_log('Failed with "'.$e->getMessage().'"');
                    }
                }

                $this->fieldModel->saveEntity($new_field);
                if (!in_array($alias, $created)) {
                    $created[] = $alias;
                }
            }

            $feature_settings['installed'] = $created;
            $integration->setFeatureSettings($feature_settings);
        }
    }

    /**
     * @returns array[]
     */
    abstract protected function getEnhancerFieldArray();

    /**
     * @return string
     */
    private static function getObjectName()
    {
        if (class_exists('MauticPlugin\MauticExtendedFieldBundle\MauticExtendedFieldBundle')) {
            return 'extendedField';
        }

        return 'lead';
    }

    /**
     * @return string
     */
    public function getDisplayName()
    {
        $spaced_name = preg_replace('/([a-z])([A-Z])/', '$1 $2', $this->getName());

        return sprintf('%s Data Enhancer', $spaced_name);
    }

    /**
     * @param array $settings
     *
     * @return array
     */
    public function getFormLeadFields($settings = [])
    {
        static $fields = [];

        if (empty($fields)) {
            $name      = $this->getName();
            $available = $this->getAvailableLeadFields($settings);
            if (empty($available) || !is_array($available)) {
                return [];
            }

            foreach ($available as $field => $details) {
                $label            = empty($details['label']) ? false : $details['label'];
                $matchedFieldName = $this->matchFieldName($field);

                switch ($details['type']) {
                    case 'string':
                    case 'boolean':
                        $fields[$matchedFieldName] = (!$label)
                            ? $this->translator->transConditional(
                                "mautic.integration.common.{$matchedFieldName}",
                                "mautic.integration.{$name}.{$matchedFieldName}.label"
                            )
                            : $label;
                        break;
                    case 'object':
                        if (isset($details['fields'])) {
                            foreach ($details['fields'] as $property) {
                                $matchedFieldName          = $this->matchFieldName($field, $property);
                                $fields[$matchedFieldName] = (!$label)
                                    ? $this->translator->transConditional(
                                        "mautic.integration.common.{$matchedFieldName}",
                                        "mautic.integration.{$name}.{$matchedFieldName}.label"
                                    )
                                    : $label;
                            }
                        } else {
                            $fields[$field] = (!$label)
                                ? $this->translator->transConditional(
                                    "mautic.integration.common.{$matchedFieldName}",
                                    "mautic.integration.{$name}.{$matchedFieldName}.label"
                                )
                                : $label;
                        }
                        break;
                    case 'array_object':
                        if ('urls' == $field || 'url' == $field) {
                            foreach ($details['fields'] as $property) {
                                $fields["{$property}Urls"] = (!$label)
                                    ? $this->translator->transConditional(
                                        "mautic.integration.common.{$property}Urls",
                                        "mautic.integration.{$name}.{$property}Urls"
                                    )
                                    : $label;
                            }
                        } elseif (isset($details['fields'])) {
                            foreach ($details['fields'] as $property) {
                                $matchedFieldName          = $this->matchFieldName($field, $property);
                                $fields[$matchedFieldName] = (!$label)
                                    ? $this->translator->transConditional(
                                        "mautic.integration.common.{$matchedFieldName}",
                                        "mautic.integration.{$name}.{$matchedFieldName}.label"
                                    )
                                    : $label;
                            }
                        } else {
                            $fields[$matchedFieldName] = (!$label)
                                ? $this->translator->transConditional(
                                    "mautic.integration.common.{$matchedFieldName}",
                                    "mautic.integration.{$name}.{$matchedFieldName}.label"
                                )
                                : $label;
                        }
                        break;
                }
            }
            if ($this->sortFieldsAlphabetically()) {
                uasort($fields, 'strnatcmp');
            }
        }

        return $fields;
    }

    /**
     * @return string[]
     */
    public function getSupportedFeatures()
    {
        return ['push_lead'];
    }

    /**
     * @param Lead  $lead
     * @param array $config
     *
     * @return bool
     */
    public function pushLead(Lead &$lead, array $config = [])
    {
        $this->logger->debug('Pushing to Enhancer '.$this->getName(), $config);
        $this->config = $config;
        try {
            $this->doEnhancement($lead);
        } catch (\Exception $exception) {
            $this->logIntegrationError(
                new ApiErrorException(
                    'There was an issue using enhancer: '.$this->getName(),
                    0,
                    $exception
                ),
                $lead
            );
        }
        $event = new MauticEnhancerEvent($this, $lead, $this->getCampaign());
        $this->dispatcher->dispatch(MauticEnhancerEvents::ENHANCER_COMPLETED, $event);

        // Always return true to prevent campaign actions from being halted, even if an enhancer fails.
        return true;
    }

    /**
     * @param Lead $lead
     *
     * @return mixed
     */
    abstract public function doEnhancement(Lead &$lead);

    /**
     * @return bool|\Doctrine\Common\Proxy\Proxy|\Mautic\CampaignBundle\Entity\Campaign|null|object
     */
    private function getCampaign()
    {
        if (!$this->campaign) {
            $config = $this->config;
            try {
                if (is_int($config['campaignId'])) {
                    // In the future a core fix may provide the correct campaign id.
                    $this->campaign = $this->em->getReference(
                        'Mautic\CampaignBundle\Enitity\Campaign',
                        $config['campaignId']
                    );
                } else {

                    // Otherwise we must obtain it from the unit of work.
                    /** @var \Doctrine\ORM\UnitOfWork $identityMap */
                    $identityMap = $this->em->getUnitOfWork()->getIdentityMap();
                    if (isset($identityMap['Mautic\CampaignBundle\Entity\LeadEventLog'])) {

                        /** @var \Mautic\LeadBundle\Entity\LeadEventLog $leadEventLog */
                        foreach ($identityMap['Mautic\CampaignBundle\Entity\LeadEventLog'] as $leadEventLog) {

                            $properties = $leadEventLog->getEvent()->getProperties();
                            if (
                                $properties['_token'] === $config['_token']
                                && $properties['campaignId'] === $config['campaignId']
                            ) {
                                $this->campaign = $leadEventLog->getCampaign();
                                break;
                            }
                        }
                    }
                }
            } catch (\Exception $e) {
            }
        }

        return $this->campaign;
    }

    /**
     * @param Lead $lead
     */
    public function applyCost($lead)
    {
        $costPerEnhancement = $this->getCostPerEnhancement();
        if ($costPerEnhancement) {
            $attribution = $lead->getFieldValue('attribution');
            // $lead->attribution -= $costPerEnhancement;
            $lead->addUpdatedField(
                'attribution',
                $attribution - $costPerEnhancement,
                $attribution
            );
        }
    }

    /**
     * Return null if there is no cost attributed to the integration.
     */
    public function getCostPerEnhancement()
    {
        return null;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->getIntegrationSettings()->getId();
    }

    /**
     * @param $lead
     */
    public function saveLead($lead)
    {
        $event = new ContactLedgerContextEvent(
            $this->campaign, $this, 'enhanced', $lead
        );
        $this->dispatcher->dispatch(
            'mautic.contactledger.context_create',
            $event
        );
        $this->leadModel->saveEntity($lead);
    }
}
