<?php
/**
 * Created by PhpStorm.
 * User: nbush
 * Date: 4/3/18
 * Time: 4:39 PM.
 */

namespace MauticPlugin\MauticEnhancerBundle\Integration;

use Doctrine\DBAL\Schema\Table;
use Doctrine\ORM\Tools\SchemaTool;
use Mautic\CoreBundle\Doctrine\Helper\TableSchemaHelper;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\LeadBundle\Entity\Lead;
use MauticPlugin\MauticEnhancerBundle\Entity\PluginsEnhancerCityStateZip;

class CityStateFromZipIntegration extends AbstractEnhancerIntegration
{
    /**
     * @var CoreParametersHelper
     */
    protected $parametersHelper;


    public function getName()
    {
        return 'CityStateFromZip';
    }

    public function getDisplayName()
    {
        return 'Fill Missing City, State From Zipcode';
    }

    protected function getEnhancerFieldArray()
    {
        //make sure the entity table is built
        //doing this here because this is essentially the integrations install method
        $schemaManager = $this->em->getConnection()->getSchemaManager();

        $metadata = $this->em->getClassMetadata($this->getEntityName());
        $table = $metadata->tableGeneratorDefinition();
        $schemaManager->createTable($table);

        return [];
    }

    /**
     * @return string
     */
    public function getEntityName()
    {
        return PluginsEnhancerCityStateZip::class;
    }

    /**
     * @return \Doctrine\ORM\EntityRepository|\MauticPlugin\MauticEnhancerBundle\Entity\PluginsEnhancerCityStateZipRepository
     */
    public function getRepository()
    {
        return $this->em->getRepository($this->getEntityName());
    }


    public function doEnhancement(Lead &$lead)
    {
        if (!($lead->getCity() && $lead->getState()) && $lead->getZipcode()) {
            $cityStateZip = $this->getRepository()->findOneBy(['zip_code' => $lead->getZipcode()]);
            if ($cityStateZip) {
                if (!$lead->getCity()) {
                    $lead->addUpdatedField('city', $cityStateZip->getCity());
                }
                if (!$lead->getState()) {
                    $lead->addUpdatedField('state', $cityStateZip->getState());
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
