<?php
/**
 * Created by PhpStorm.
 * User: nbush
 * Date: 4/4/18
 * Time: 11:24 AM.
 */

namespace MauticPlugin\MauticEnhancerBundle\Entity;

use Doctrine\ORM\Mapping\ClassMetadata;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\CoreBundle\Entity\CommonEntity;

class PluginEnhancerCityStatePostalCode extends CommonEntity
{
    /* Table name */
    const TABLE_NAME = 'plugin_enhancer_city_state_postal_code';

    /** @var int */
    protected $id;

    /** @var string */
    protected $postalCode;

    /** @var string */
    protected $city;

    /** @var string */
    protected $stateProvince;

    /** @var string */
    protected $country;

    /** @var string */
    protected $county;

    /** @var string */
    protected $latitude;

    /** @var string */
    protected $longitude;

    /**
     * @param ClassMetadata $metadata
     */
    public static function loadMetadata(ClassMetadata $metadata)
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable(MAUTIC_TABLE_PREFIX.self::TABLE_NAME)
            ->setCustomRepositoryClass(
                PluginEnhancerCityStatePostalCodeRepository::class
            );

        $builder->addId();

        $builder->createField('postalCode', 'string')
            ->columnName('postal_code')
            ->build();

        $builder->createField('city', 'string')
            ->nullable()
            ->build();

        $builder->createField('stateProvince', 'string')
            ->columnName('state_province')
            ->nullable()
            ->build();

        $builder->createField('county', 'string')
            ->columnName('county')
            ->nullable()
            ->build();

        $builder->createField('latitude', 'string')
            ->columnName('latitude')
            ->nullable()
            ->build();

        $builder->createField('longitude', 'string')
            ->columnName('longitude')
            ->nullable()
            ->build();

        $builder->createField('country', 'string')
            ->build();

        $builder->addIndex(
            [
                'country',
                'postal_code',
            ],
            'country_postal_code'
        );
    }

    /**
     * @return string
     */
    public function getPostalCode()
    {
        return $this->postalCode;
    }

    /**
     * @param string $postalCode
     *
     * @return PluginEnhancerCityStatePostalCode
     */
    public function setPostalCode($postalCode)
    {
        $this->postalCode = $postalCode;

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
     * @return PluginEnhancerCityStatePostalCode
     */
    public function setCity($city)
    {
        $this->city = $city;

        return $this;
    }

    /**
     * @return string
     */
    public function getStateProvince()
    {
        return $this->stateProvince;
    }

    /**
     * @param string $stateProvince
     *
     * @return PluginEnhancerCityStatePostalCode
     */
    public function setStateProvince($stateProvince)
    {
        $this->stateProvince = $stateProvince;

        return $this;
    }

    /**
     * @return string
     */
    public function getCountry()
    {
        return $this->country;
    }

    /**
     * @param string $country
     *
     * @return PluginEnhancerCityStatePostalCode
     */
    public function setCountry($country)
    {
        $this->country = $country;

        return $this;
    }

    /**
     * @return string
     */
    public function getCounty()
    {
        return $this->county;
    }

    /**
     * @param string $county
     *
     * @return PluginEnhancerCityStatePostalCode
     */
    public function setCounty($county)
    {
        $this->county = $county;

        return $this;
    }

    /**
     * @return string
     */
    public function getLatitude()
    {
        return $this->latitude;
    }

    /**
     * @param string $latitude
     *
     * @return PluginEnhancerCityStatePostalCode
     */
    public function setLatitude($latitude)
    {
        $this->latitude = $latitude;

        return $this;
    }

    /**
     * @return string
     */
    public function getLongitude()
    {
        return $this->longitude;
    }

    /**
     * @param string $longitude
     *
     * @return PluginEnhancerCityStatePostalCode
     */
    public function setLongitude($longitude)
    {
        $this->longitude = $longitude;

        return $this;
    }
}
