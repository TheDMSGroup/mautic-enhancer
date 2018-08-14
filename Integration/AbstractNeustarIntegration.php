<?php
/**
 * Created by PhpStorm.
 * User: nbush
 * Date: 8/8/18
 * Time: 11:07 AM.
 */

namespace MauticPlugin\MauticEnhancerBundle\Integration;

use GuzzleHttp\Client;
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

    //const NEUSTAR_ENDPOINT_PROD = 'https://webgwy.targusinfo.com/access/query';

    const NEUSTAR_ENDPOINT_DEV = 'https:/gwydemo.targusinfo.com/access/query';

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

    abstract protected function getNeustarElementId();

    /**
     * @return string
     */
    abstract protected function getNeustarIntegrationName();

    /**
     * Returns a list of keys use to build the scoring query.
     *
     * @return array
     */
    abstract protected function getNeustarServiceKeys();

    /**
     * @return array
     */
    abstract protected function getAvailableResponses();

    /**
     * @param Lead lead
     * @param int
     *
     * @return string
     */
    abstract protected function getServiceData(Lead $lead, $serviceId);

    abstract protected function processResponse($neustarResponse);

    /**
     * @return array
     */
    public function getRequiredKeyFields()
    {
        $stop = 'here';

        return [
            'username'   => 'mautic.enhancer.neustar.required_key.username',
            'password'   => 'mautic.enhancer.neustar.required_key.password',
            'serviceId'  => 'mautic.enhancer.neustar.required_key.service_id',
        ];
    }

    public function getEnhancerFieldArray()
    {
        $fields = [];
        foreach ($this->getAvailableResponses() as $section => $attribues) {
            foreach ($attribues as $attribute => $options) {
                if (!is_array($options)) {
                    $options = [$options];
                }
                foreach ($options as $option) {
                    $name               = [self::NEUSTAR_PREFIX, $this->getElementId(), $section, $option];
                    $fieldName          = strtolower(implode('_', $name));
                    $fields[$fieldName] = [
                        'label' => ucfirst(implode(' ', $name)),
                    ];
                }
            }
        }

        return $fields;
    }

    /**
     * @param \Mautic\PluginBundle\Integration\Form|\Symfony\Component\Form\FormBuilder $builder
     * @param array                                                                     $data
     * @param string                                                                    $formArea
     */
    public function appendToForm(&$builder, $data, $formArea)
    {
        $stop = 'here';

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
            'elems'    => $this->getElementId(),
            'version'  => '1.0',
            'transid'  => '1',
        ];

        foreach ($this->getServiceKeys() as $serviceKey) {
            $query['key'.$serviceKey] = $this->getServiceData($lead, $serviceKey);
        }

        $settings = $this->getIntegrationSettings()->getFeatureSettings();

        $neustarClient   = new Client();
        $neustarResponse = $neustarClient->request('GET', $settings['endpoint'], ['query' => $query]);
        $this->processResponse($neustarResponse);
    }
}
