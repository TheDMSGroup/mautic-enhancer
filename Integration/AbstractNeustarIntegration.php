<?php
/**
 * Created by PhpStorm.
 * User: nbush
 * Date: 8/8/18
 * Time: 11:07 AM.
 */

namespace MauticPlugin\MauticEnhancerBundle\Integration;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use Mautic\LeadBundle\Entity\Lead;
use Symfony\Component\Form\Extension\Core\Type\UrlType;

/**
 * Class AbstractNeustarEnhancerIntegration.
 */
abstract class AbstractNeustarIntegration extends AbstractEnhancerIntegration
{
    use NonFreeEnhancerTrait {
        appendToForm as appendNonFree;
    }

    const NEUSTAR_PREFIX = 'Neustar';

    protected static $serviceKeysDict = [
        '1' => [
            'format' => '/^\d{10}$/',
            'desc'   => 'mautic.enhancer.neustar.keys.phone.primary',
            'alias'  => 'lead.phone',
        ],
        '2' => [
            'format' => '/^\d{10}$/',
            'desc'   => 'mautic.enhancer.neustar.keys.phone.secondary',
            //'alias' => '',
        ],
        '572' => [
            'desc'  => 'mautic.enhancer.neustar.keys.email',
            'alias' => 'lead.email',
        ],
        '574' => [
            'format' => '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', //in UTC
            'desc'   => 'mautic.enhancer.neustar.keys.date',
            'alias'  => 'lead.date_added',
        ],
        '875' => [
            'desc' => 'mautic.enhancer.neustar.keys.scoring_xml',
        ],
        '1390'=> [
            'desc'  => 'mautic.enhancer.neustar.keys.address.street',
            'alias' => ['lead.address1', 'lead.address2'],
        ],
        '1391'=> [
            'desc'  => 'mautic.enhancer.neustar.keys.address.city',
            'alias' => 'lead.city',
        ],
        '1392'=> [
            'desc'  => 'mautic.enhancer.neustar.keys.address.state',
            'alias' => 'lead.state',
        ],
        '1393'=> [
            'desc'  => 'mautic.enhancer.neustar.keys.address.zip_code',
            'alias' => 'lead.zipcode',
        ],
        '1395' => [
            'required' => true,
            'format'   => '/^\w+,\w+(,\w)?$/',                       // (,\w)?$/', //Last,First,MiddleIn
            'desc'     => 'mautic.enhancer.neustar.keys.name',
            'alias'    => ['lead.lastname', 'lead.firstname', ['substr', 'extendedField.middlename', 0, 1]],
        ],
        '3251' => [
            'required' => true,
        ],
        '3256' => [
            'required' => true,
            'alias'    => ['setting.mkChannel', 'setting.mkVendorId', 'extendedField.military'],
        ],
    ];

    /**
     * Element ID of implementation
     *
     * @return string
     */
    abstract protected function getNeustarElementId();

    /**
     * Friendly name of implementation
     *
     * @return string
     */
    abstract protected function getNeustarIntegrationName();

    /**
     * Returns a list of keys use to build the query.
     *
     * @return array
     */
    abstract protected function getNeustarServiceKeys();

    /**
     * @param Lead lead
     * @param int
     *
     * @return string
     */
    abstract protected function getServiceIdData(Lead $lead, $serviceId);

    /**
     * @param Lead     $lead
     * @param Response $neustarResponse
     */
    abstract protected function processResponse(Lead $lead, Response $neustarResponse);

    /**
     * @return array
     */
    public function getRequiredKeyFields()
    {
        return [
            'username'   => 'mautic.enhancer.neustar.required_key.username',
            'password'   => 'mautic.enhancer.neustar.required_key.password',
            'serviceId'  => 'mautic.enhancer.neustar.required_key.service_id',
        ];
    }

    /**
     * @param \Symfony\Component\Form\FormBuilderInterface $builder
     * @param array                                        $data
     * @param string                                       $formArea
     */
    public function appendToForm(&$builder, $data, $formArea)
    {
        if ('features' === $formArea) {
            $builder
                ->add(
                    'endpoint',
                    UrlType::class,
                    [
                        'label'      => $this->translator->trans('mautic.enhancer.neustar.query.endpoint.label'),
                        'data'       => isset($data['endpoint']) ? $data['endpoint'] : '',
                        'required'   => true,
                        'label_attr' => ['class' => 'control-label'],
                        'attr'       => [
                            'class'   => 'form-control',
                            'tooltip' => $this->translator->trans('mautic.enhancer.neustar.query.endpoint.tooltip'),
                        ],
                    ]
                );
        }
        $this->appendNonFree($builder, $data, $formArea);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return self::NEUSTAR_PREFIX.$this->getNeustarIntegrationName();
    }

    /**
     * @return string
     */
    public function getDisplayName()
    {
        return ucwords(sprintf('%s %s Scoring', self::NEUSTAR_PREFIX, $this->getNeustarIntegrationName()));
    }

    /**
     * {@inheritdoc}
     */
    public function getAuthenticationType()
    {
        return 'None';
    }

    /**
     * {@inheritdoc}
     */
    public function doEnhancement(Lead &$lead)
    {
        $keys = $this->getKeys();

        $query = [
            'username' => $keys['username'],
            'password' => $keys['password'],
            'svcid'    => $keys['serviceId'],
            'elems'    => $this->getNeustarElementId(),
            'version'  => '1.0',
            'transid'  => '1',
        ];

        foreach ($this->getNeustarServiceKeys() as $serviceKey) {
            $query['key'.$serviceKey] = $this->getServiceIdData($lead, $serviceKey);
        }

        $settings = $this->getIntegrationSettings()->getFeatureSettings();

        $neustarClient   = new Client();
        /** @var Response $neustarResponse */
        $neustarResponse = $neustarClient->request('GET', $settings['endpoint'], ['query' => $query]);

        return $this->processResponse($lead, $neustarResponse);
    }

    /**
     * Convert a DOM Document into a nested array.
     *
     * @param $root
     *
     * @return array|mixed
     */
    protected function domDocumentArray($root)
    {
        $result = [];

        if ($root->hasAttributes()) {
            foreach ($root->attributes as $attribute) {
                $result['@attributes'][$attribute->name] = $attribute->value;
            }
        }

        if ($root->hasChildNodes()) {
            if (1 == $root->childNodes->length) {
                $child = $root->childNodes->item(0);
                if (in_array($child->nodeType, [XML_TEXT_NODE, XML_CDATA_SECTION_NODE]) && !empty($child->nodeValue)) {
                    $result['_value'] = $child->nodeValue;

                    return 1 == count($result)
                        ? $result['_value']
                        : $result;
                }
            }
            $groups = [];
            foreach ($root->childNodes as $child) {
                if (!isset($result[$child->nodeName])) {
                    $result[$child->nodeName] = $this->domDocumentArray($child);
                } else {
                    if (!isset($groups[$child->nodeName])) {
                        $result[$child->nodeName] = [$result[$child->nodeName]];
                        $groups[$child->nodeName] = 1;
                    }
                    $result[$child->nodeName][] = $this->domDocumentArray($child);
                }
            }
        }

        return $result;
    }
}
