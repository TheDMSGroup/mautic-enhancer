<?php
/**
 * Created by PhpStorm.
 * User: nbush
 * Date: 4/4/18
 * Time: 11:24 AM
 */

namespace MauticPlugin\MauticEnhancerBundle\Entity;


class ZipCityState
{
    /**
     * @return string
     */
    public function getZipCode()
    {
        return $this->zipCode;
    }

    /**
     * @param string $zipCode
     * @return ZipCityState
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
     * @return ZipCityState
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
     * @return ZipCityState
     */
    public function setState($state)
    {
        $this->state = $state;
        return $this;
    }

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