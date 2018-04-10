<?php
/**
 * Created by PhpStorm.
 * User: nbush
 * Date: 4/3/18
 * Time: 4:39 PM.
 */

namespace MauticPlugin\MauticEnhancerBundle\Integration;

use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\LeadBundle\Entity\Lead;
use MauticPlugin\MauticEnhancerBundle\Entity\PluginsEnhancerCityStatePostalCode;
use MauticPlugin\MauticEnhancerBundle\Model\CityStatePostalCodeModel;

class CityStateFromPostalCodeIntegration extends AbstractEnhancerIntegration
{
    /**
     * @var CityStatePostalCodeModel
     */
    protected $cityStatePostalCodeModel;

    /**
     * CityStateFromPostalCodeIntegration constructor.
     * @param CityStatePostalCodeModel $model
     */
    public function __construct(CityStatePostalCodeModel $model)
    {
        $this->cityStatePostalCodeModel = $model;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'CityStateFromPostalCode';
    }

    /**
     * @return string
     */
    public function getDisplayName()
    {
        return 'Fill Missing City, State/Province From Postal Code';
    }

    /**
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    protected function getEnhancerFieldArray()
    {
        try {
            $sql = 'SELECT * FROM plugin_enhancer_city_state_postal_code WHERE 1 LIMIT 1';
            $this->em->getConnection()->exec($sql);
        } catch (TableNotFoundException $e) {
            $this->cityStatePostalCodeModel->createReferenceTable();
        } catch (DBALException $e) {
            //?
        }

        return [];
    }

    /**
     * @return string
     */
    public function getEntityName()
    {
        return PluginsEnhancerCityStatePostalCode::class;
    }

    /**
     * @return \Doctrine\ORM\EntityRepository|\MauticPlugin\MauticEnhancerBundle\Entity\PluginsEnhancerCityStatePostalCodeRepository
     */
    public function getRepository()
    {
        return $this->em->getRepository($this->getEntityName());
    }

    /**
     * @param Lead $lead
     * @return void
     */
    public function doEnhancement(Lead &$lead)
    {
        if (!($lead->getCity() && $lead->getState()) && $lead->getZipcode()) {
            $cityStatePostalCode = $this->getRepository()->findOneBy(['postalCode' => $lead->getZipcode()]);
            if ($cityStatePostalCode) {
                if (!$lead->getCity()) {
                    $lead->addUpdatedField('city', $cityStatePostalCode->getCity());
                }
                if (!$lead->getState()) {
                    $lead->addUpdatedField('stateProvince', $cityStatePostalCode->getStateProvince());
                }
            }
        }
    }

    /**
     * @return string
     */
    public function getAuthenticationType()
    {
        return 'none';
    }

    /**
     * @param \Symfony\Component\Form\FormBuilderInterface $builder
     * @param array $data
     * @param string $formArea
     */
    public function appendToForm(&$builder, $data, $formArea)
    {
        if ('features' === $formArea) {
            $builder->add(
                'autorun_enabled',
                'hidden',
                [
                    'data' => true,
                ]
            );
        }
    }
}
