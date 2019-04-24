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
use Exception;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadField;
use Mautic\LeadBundle\Event\LeadFieldEvent;
use Mautic\LeadBundle\Helper\FormFieldHelper;
use Mautic\LeadBundle\LeadEvents;
use Mautic\PluginBundle\Event\PluginIntegrationRequestEvent;
use Mautic\PluginBundle\Exception\ApiErrorException;
use Mautic\PluginBundle\Integration\AbstractIntegration;
use Mautic\PluginBundle\PluginEvents;
use MauticPlugin\MauticEnhancerBundle\Event\ContactLedgerContextEvent;
use MauticPlugin\MauticEnhancerBundle\Event\MauticEnhancerEvent;
use MauticPlugin\MauticEnhancerBundle\MauticEnhancerEvents;
use Symfony\Component\Form\Extension\Core\DataTransformer\NumberToLocalizedStringTransformer;

/**
 * Class AbstractEnhancerIntegration.
 *
 * @method string getAuthenticationType()
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

    /**
     * Make a basic call using cURL to get the data.
     * TODO migrate to Guzzle instead.
     *
     * @param        $url
     * @param array  $parameters
     * @param string $method
     * @param array  $settings
     *
     * @return mixed|string
     */
    public function makeRequest($url, $parameters = [], $method = 'GET', $settings = [])
    {
        // If not authorizing the session itself, check isAuthorized which will refresh tokens if applicable
        if (empty($settings['authorize_session'])) {
            $this->isAuthorized();
        }

        $method   = strtoupper($method);
        $authType = (empty($settings['auth_type'])) ? $this->getAuthenticationType() : $settings['auth_type'];

        list($parameters, $headers) = $this->prepareRequest($url, $parameters, $method, $settings, $authType);

        if (empty($settings['ignore_event_dispatch'])) {
            $event = $this->dispatcher->dispatch(
                PluginEvents::PLUGIN_ON_INTEGRATION_REQUEST,
                new PluginIntegrationRequestEvent($this, $url, $parameters, $headers, $method, $settings, $authType)
            );

            $headers    = $event->getHeaders();
            $parameters = $event->getParameters();
        }

        if (!isset($settings['query'])) {
            $settings['query'] = [];
        }

        if (isset($parameters['append_to_query'])) {
            $settings['query'] = array_merge(
                $settings['query'],
                $parameters['append_to_query']
            );

            unset($parameters['append_to_query']);
        }

        if (isset($parameters['post_append_to_query'])) {
            $postAppend = $parameters['post_append_to_query'];
            unset($parameters['post_append_to_query']);
        }

        if (!$this->isConfigured()) {
            return [
                'error' => [
                    'message' => $this->translator->trans(
                        'mautic.integration.missingkeys'
                    ),
                ],
            ];
        }

        if ('GET' == $method && !empty($parameters)) {
            $parameters = array_merge($settings['query'], $parameters);
            $query      = http_build_query($parameters);
            $url .= (false === strpos($url, '?')) ? '?'.$query : '&'.$query;
        } elseif (!empty($settings['query'])) {
            $query = http_build_query($settings['query']);
            $url .= (false === strpos($url, '?')) ? '?'.$query : '&'.$query;
        }

        if (isset($postAppend)) {
            $url .= $postAppend;
        }

        // Check for custom content-type header
        if (!empty($settings['content_type'])) {
            $settings['encoding_headers_set'] = true;
            $headers[]                        = "Content-Type: {$settings['content_type']}";
        }

        if ('GET' !== $method) {
            if (!empty($parameters)) {
                if ('oauth1a' == $authType) {
                    $parameters = http_build_query($parameters);
                }
                if (!empty($settings['encode_parameters'])) {
                    if ('json' == $settings['encode_parameters']) {
                        //encode the arguments as JSON
                        $parameters = json_encode($parameters);
                        if (empty($settings['encoding_headers_set'])) {
                            $headers[] = 'Content-Type: application/json';
                        }
                    }
                }
            } elseif (isset($settings['post_data'])) {
                $parameters = $settings['post_data'];
            }
        }

        $options = [
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_HEADER         => 1,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_FOLLOWLOCATION => 0,
            CURLOPT_REFERER        => $this->getRefererUrl(),
            CURLOPT_USERAGENT      => $this->getUserAgent(),
        ];

        if (isset($settings['curl_options']) && is_array($settings['curl_options'])) {
            $options = $settings['curl_options'] + $options;
        }

        if (isset($settings['ssl_verifypeer'])) {
            $options[CURLOPT_SSL_VERIFYPEER] = $settings['ssl_verifypeer'];
        }

        $connector = HttpFactory::getHttp(
            [
                'transport.curl' => $options,
            ]
        );

        $parseHeaders = (isset($settings['headers'])) ? array_merge($headers, $settings['headers']) : $headers;
        // HTTP library requires that headers are in key => value pairs
        $headers = [];
        if (is_array($parseHeaders)) {
            foreach ($parseHeaders as $key => $value) {
                if (false !== strpos($value, ':')) {
                    list($key, $value) = explode(':', $value);
                    $key               = trim($key);
                    $value             = trim($value);
                }

                $headers[$key] = $value;
            }
        }

        try {
            $timeout = (isset($settings['request_timeout'])) ? (int) $settings['request_timeout'] : 10;
            switch ($method) {
                case 'GET':
                    $result = $connector->get($url, $headers, $timeout);
                    break;
                case 'POST':
                case 'PUT':
                case 'PATCH':
                    $connectorMethod = strtolower($method);
                    $result          = $connector->$connectorMethod($url, $parameters, $headers, $timeout);
                    break;
                case 'DELETE':
                    $result = $connector->delete($url, $headers, $timeout);
                    break;
            }
        } catch (Exception $exception) {
            $this->handleEnchancerException($this->settings->getName(), $exception);

            return ['error' => ['message' => $exception->getMessage(), 'code' => $exception->getCode()]];
        }
        if (empty($settings['ignore_event_dispatch'])) {
            $event->setResponse($result);
            $this->dispatcher->dispatch(
                PluginEvents::PLUGIN_ON_INTEGRATION_RESPONSE,
                $event
            );
        }
        if (!empty($settings['return_raw'])) {
            return $result;
        } else {
            $response = $this->parseCallbackResponse($result->body, !empty($settings['authorize_session']));

            return $response;
        }
    }

    /**
     * @param string    $className
     * @param Exception $exception
     */
    public function handleEnchancerException($className, $exception)
    {
        if (function_exists('newrelic_notice_error')) {
            call_user_func(
                'newrelic_notice_error',
                'Enhancer Connection Error: '.$className,
                $exception
            );
        }
    }
}
