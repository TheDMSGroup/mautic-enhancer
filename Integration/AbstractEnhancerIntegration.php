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

            foreach ($creating as $alias => $properties) {
                if (in_array($alias, $created)) {
                    //do not build an existing column
                    continue;
                }

                $new_field = $this->fieldModel->getEntity();
                $new_field->setAlias($alias);
                $new_field->setOrder(++$count);

                foreach ($properties as $property => $value) {
                    $method = 'set'.implode('', array_map('ucfirst', explode('_', $property)));

                    try {
                        $new_field->$method($value);
                    } catch (\Exception $e) {
                        error_log('Failed with "'.$e->getMessage().'"');
                    }
                }

                $this->fieldModel->saveEntity($new_field);
                $created[] = $alias;
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
            $s         = $this->getName();
            $available = $this->getAvailableLeadFields($settings);
            if (empty($available) || !is_array($available)) {
                return [];
            }

            foreach ($available as $field => $details) {
                $label = (!empty($details['label'])) ? $details['label'] : false;
                $fn    = $this->matchFieldName($field);
                // @todo - Maybe we should define $f, $p, $s?
                switch ($details['type']) {
                    case 'string':
                    case 'boolean':
                        $fields[$fn] = (!$label)
                            ? $this->translator->transConditional(
                                "mautic.integration.common.{$fn}",
                                "mautic.integration.{$s}.{$fn}.label"
                            )
                            : $label;
                        break;
                    case 'object':
                        if (isset($details['fields'])) {
                            foreach ($details['fields'] as $f) {
                                $fn          = $this->matchFieldName($field, $f);
                                $fields[$fn] = (!$label)
                                    ? $this->translator->transConditional(
                                        "mautic.integration.common.{$fn}",
                                        "mautic.integration.{$s}.{$fn}.label"
                                    )
                                    : $label;
                            }
                        } else {
                            $fields[$field] = (!$label)
                                ? $this->translator->transConditional(
                                    "mautic.integration.common.{$fn}",
                                    "mautic.integration.{$s}.{$fn}.label"
                                )
                                : $label;
                        }
                        break;
                    case 'array_object':
                        if ('urls' == $field || 'url' == $field) {
                            foreach ($details['fields'] as $f) {
                                $fields["{$p}Urls"] = (!$label)
                                    ? $this->translator->transConditional(
                                        "mautic.integration.common.{$f}Urls",
                                        "mautic.integration.{$s}.{$f}Urls"
                                    )
                                    : $label;
                            }
                        } elseif (isset($details['fields'])) {
                            foreach ($details['fields'] as $f) {
                                $fn          = $this->matchFieldName($field, $f);
                                $fields[$fn] = (!$label)
                                    ? $this->translator->transConditional(
                                        "mautic.integration.common.{$fn}",
                                        "mautic.integration.{$s}.{$fn}.label"
                                    )
                                    : $label;
                            }
                        } else {
                            $fields[$fn] = (!$label)
                                ? $this->translator->transConditional(
                                    "mautic.integration.common.{$fn}",
                                    "mautic.integration.{$s}.{$fn}.label"
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
     */
    public function pushLead(Lead &$lead, array $config = [])
    {
        $this->logger->debug('Pushing to Enhancer '.$this->getName(), $config);
        $this->config = $config;
        $this->doEnhancement($lead);
        $event = new MauticEnhancerEvent($this, $lead, $this->getCampaign());
        $this->dispatcher->dispatch(MauticEnhancerEvents::ENHANCER_COMPLETED, $event);
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
        if (null !== $costPerEnhancement) {
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
            'mauticplugin.contactledger.context_create',
            $event
        );
        $this->leadModel->saveEntity($lead);
    }
}
