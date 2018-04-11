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

    /**
     * @var int
     */
    protected $id;

    /**
     * @var string
     */
    protected $postalCode;

    /**
     * @var string
     */
    protected $city;

    /**
     * @var string
     */
    protected $stateProvince;

    /**
     * @var string
     */
    protected $country;

    /**
     * @return string
     */
    public static function getSQLTableName()
    {
        return 'plugin_enhancer_city_state_postal_code';
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
     * @param ClassMetadata $metadata
     */
    public static function loadMetadata(ClassMetadata $metadata)
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable(self::getSQLTableName())
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

        $builder->createField('country', 'string')
            ->build();

        $builder->addIndex(['postal_code'], 'idx_postal_code')
            ->addIndex(['country', 'postal_code'], 'idx_country');
    }
}
