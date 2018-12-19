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

use Doctrine\ORM\OptimisticLockException;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadField;
use Mautic\LeadBundle\Event\LeadFieldEvent;
use Mautic\LeadBundle\Helper\FormFieldHelper;
use Mautic\LeadBundle\LeadEvents;
use Mautic\PluginBundle\Exception\ApiErrorException;
use Mautic\PluginBundle\Integration\AbstractIntegration;
use MauticPlugin\MauticEnhancerBundle\Event\ContactLedgerContextEvent;
use MauticPlugin\MauticEnhancerBundle\Event\MauticEnhancerEvent;
use MauticPlugin\MauticEnhancerBundle\MauticEnhancerEvents;
use Symfony\Component\Form\Extension\Core\DataTransformer\NumberToLocalizedStringTransformer;

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

    /** @var bool */
    protected $isPush = false;

    public function buildEnhancerFields()
    {
        $integration = $this->getIntegrationSettings();

        /**
         *  TODO:
         *  eventually I would like to add field_group = 'enhancement'
         *  to all enhancer fields and make this call without false
         *  and change
         *      in_array($alias, $exists) => in_array($alias, array_keys($exists['enhancement'])).
         */
        $exists = array_keys(
            $this->fieldModel->getFieldList(false)
        );

        if ($integration->getIsPublished()) {
            /** @var LeadField[] $newFields */
            $newFields      = [];
            $possibleFields = $this->getEnhancerFieldArray();
            foreach ($possibleFields as $alias => $attributes) {
                if (in_array($alias, $exists)) {
                    // The field already exists
                    continue;
                }

                $newField = new LeadField();
                $newField->setAlias($alias);

                $attributes = array_merge($this->enhancerFieldDefaults(), $attributes);
                if (!isset($attributes['label'])) {
                    $attributes['label'] = implode(' ', array_map('ucfirst', explode('_', $alias)));
                }

                switch ($attributes['type']) {
                    case 'boolean':
                        if (!isset($attibutes['properties'])) {
                            $attributes['properties'] = ['no' => 'No', 'yes' => 'Yes'];
                        }
                        break;
                    case 'number':
                        if (!isset($attibutes['properties'])) {
                            $attributes['properties'] = [
                                'roundmode' => NumberToLocalizedStringTransformer::ROUND_HALF_UP,
                                'scale'     => 0,   // precision changed to scale in symfony 2.7
                                'precision' => 0,   // trusting the validator to do the right thing
                            ];
                        }
                        break;
                    case 'time': //intentional no break
                        $attributes['is_listable'] = false;
                    // no break
                    default:
                        if (!isset($attributes['properties'])) {
                            $attributes['properties'] = [];
                        }
                }
                $attributes['properties'] = \serialize($attributes['properties']);

                $result = FormFieldHelper::validateProperties($attributes['type'], $attributes['properties']);
                if (!$result[0]) {
                    $this->logger->error('Installation Failed: "'.$alias.''.$result[1].'"');
                    $this->em->rollback();

                    return;
                }

                foreach ($attributes as $attribute => $value) {
                    // Now that i know better, I can not do it this way...
                    // A lot of refactoring though and dynamic methods still
                    // required

                    //convert snake case to camel case
                    $method = 'set'.implode('', array_map('ucfirst', explode('_', $attribute)));

                    try {
                        call_user_func(
                            [$newField, $method],
                            $value
                        );
                    } catch (\Exception $e) {
                        $this->logger->error('Failed to set attribute: "'.$e->getMessage().'"');
                    }
                }

                $this->fieldModel->setTimestamps($newField, true);

                $event = new LeadFieldEvent($newField, true);
                $event = $this->dispatcher->dispatch(LeadEvents::FIELD_PRE_SAVE, $event);
                // implicitly call flush once all fields are ready to be added
                // which allows us to rollback the entire integration install
                $this->fieldModel->getRepository()->saveEntity($newField, false);
                $this->dispatcher->dispatch(LeadEvents::FIELD_POST_SAVE, $event);

                $newFields[] = $newField;
            }

            try {
                $this->em->flush();
            } catch (OptimisticLockException $ole) {
                $this->logger->error($this->getDisplayName().' failed to install: '.$ole->getMessage());
                //add flash failure message?
            }
        }
    }

    /**
     * @returns array[]
     */
    abstract protected function getEnhancerFieldArray();

    /**
     * @return array
     */
    private function enhancerFieldDefaults()
    {
        return [
            'is_published' => true,
            'type'         => 'text',
            'group'        => 'enhancement',
            'object'       => $this->getLeadFieldClassName(),
        ];
    }

    /**
     * @return string
     */
    private function getLeadFieldClassName()
    {
        return class_exists('MauticPlugin\MauticExtendedFieldBundle\MauticExtendedFieldBundle')
            ? 'extendedField'
            : 'lead';
    }

    /**
     * @return string
     */
    public function getDisplayName()
    {
        $spacedName = preg_replace('/([a-z])([A-Z])/', '$1 $2', $this->getName());

        return sprintf('%s Data Enhancer', $spacedName);
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

        if (!$this->getIntegrationSettings()->getIsPublished()) {
            return true;
        }

        $this->config = $config;
        $this->isPush = true;

        try {
            if ($this->doEnhancement($lead)) {
                $this->saveLead($lead);
            }
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
     * @return bool
     */
    abstract public function doEnhancement(Lead $lead);

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
                        /** @var \Mautic\CampaignBundle\Entity\LeadEventLog $leadEventLog */
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
}
