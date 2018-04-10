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

class PluginsEnhancerCityStatePostalCode extends CommonEntity
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
    public function getPostalCode()
    {
        return $this->postalCode;
    }

    /**
     * @param string $postalCode
     *
     * @return PluginsEnhancerCityStatePostalCode
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
     * @return PluginsEnhancerCityStatePostalCode
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
     * @return PluginsEnhancerCityStatePostalCode
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
     * @return PluginsEnhancerCityStatePostalCode
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

        $builder->setTable('plugin_enhancer_city_state_postal_code')
            ->setCustomRepositoryClass(
                'MauticPlugin\MauticEnhancerBundle\Entity\PluginsEnhancerCityStatePostalCodeRepository'
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
    }
}
