<?php
/**
 * Created by PhpStorm.
 * User: nbush
 * Date: 4/4/18
 * Time: 11:24 AM
 */

namespace MauticPlugin\MauticEnhancerBundle\Entity;

use Doctrine\ORM\Mapping\ClassMetadata;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\CoreBundle\Entity\CommonEntity;

class PluginsEnhancerCityStateZip extends CommonEntity
{
    /**
     * @param ClassMetadata $metadata
     */
    public static function loadMetadata(ClassMetadata $metadata)
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('enhancer_citystatezip')
            ->setCustomRepositoryClass(
                'MauticPlugin\MauticEnhancerBundle\Entity\PluginsEnhancerCityStateZipRepository'
            );

        $builder->addId();
        $builder->addDateAdded();

        $builder->createField('zipCode', 'string')
            ->columnName('zip_code')
            ->build();

        $builder->createField('city', 'string')
            ->nullable()
            ->build();

        $builder->createField('state', 'string')
            ->nullable()
            ->build();
    }

    /**
     * @return string
     */
    public function getZipCode()
    {
        return $this->zipCode;
    }

    /**
     * @param string $zipCode
     *
     * @return PluginsEnhancerCityStateZip
     */
    public function setZipCode($zipCode)
    {
        $this->zipCode = $zipCode;
        return $this;
    }

    /**
     * @return string
     */
    public function getCity()
    {
        return $this->city;
    }

    /**
     * @param string $city
     *
     * @return PluginsEnhancerCityStateZip
     */
    public function setCity($city)
    {
        $this->city = $city;
        return $this;
    }

    /**
     * @return string
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * @param string $state
     * @return PluginsEnhancerCityStateZip
     */
    public function setState($state)
    {
        $this->state = $state;
        return $this;
    }

    /**
     * @var int
     */
    protected $id;

    /**
     * @var string
     */
    protected $dateAdded;

    /**
     * @var string
     */
    protected $zipCode;

    /**
     * @var string
     */
    protected $city;

    /**
     * @var string
     */
    protected $state;

}