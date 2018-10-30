<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Digital Media Solutions, LLC
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticEnhancerBundle\Entity;

use Doctrine\ORM\Mapping\ClassMetadata;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\CoreBundle\Entity\CommonEntity;

/**
 * Class PluginEnhancerBlacklist.
 */
class PluginEnhancerBlacklist extends CommonEntity
{
    const TABLE_NAME = 'plugin_enhancer_blacklist';

    /** @var int */
    protected $id;

    /** @var string Phone number in E164 format before we send it to the API */
    protected $phone;

    /** @var string The SID returned from the Blacklist API */
    protected $sid;

    /** @var integer The FederalDNC code provided by the Blacklist API */
    protected $code;

    /** @var boolean Set to true/false by the Blacklist API based on weather or not the number is in their aggregated blacklists. */
    protected $blacklisted;

    /** @var boolean The wireless status provided by the Blacklist API */
    protected $wireless;

    /** @var \DateTime The date this record was last received/updated from the API */
    protected $dateAdded;

    /**
     * @param ClassMetadata $metadata
     */
    public static function loadMetadata(ClassMetadata $metadata)
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable(MAUTIC_TABLE_PREFIX.self::TABLE_NAME);

        $builder->setCustomRepositoryClass(PluginEnhancerBlacklistRepository::class);

        $builder->addId();

        $builder->addNamedField('phone', 'string', 'phone', false);

        $builder->addNamedField('sid', 'string', 'sid', true);

        $builder->addNamedField('code', 'integer', 'code', true);

        $builder->addField('blacklisted', 'boolean');

        $builder->addField('wireless', 'boolean');

        $builder->addDateAdded();

        $builder->addIndex(['phone'], 'phone');
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return \DateTime
     */
    public function getDateAdded()
    {
        return $this->dateAdded;
    }

    /**
     * @param \DateTime $dateAdded
     *
     * @return PluginEnhancerBlacklist
     */
    public function setDateAdded(\DateTime $dateAdded)
    {
        $this->dateAdded = $dateAdded;

        return $this;
    }

    /**
     * @return string
     */
    public function getPhone()
    {
        return $this->phone;
    }

    /**
     * @param $phone
     *
     * @return $this
     */
    public function setPhone($phone)
    {
        $this->phone = $phone;

        return $this;
    }

    /**
     * @return string
     */
    public function getSid()
    {
        return $this->sid;

    }

    /**
     * @param $sid
     *
     * @return $this
     */
    public function setSid($sid)
    {
        $this->sid = $sid;

        return $this;
    }

    /**
     * @return int
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @param $code
     *
     * @return $this
     */
    public function setCode($code)
    {
        $this->code = (int) $code;

        return $this;
    }

    /**
     * @return bool
     */
    public function getBlacklisted()
    {
        return $this->blacklisted;

    }

    /**
     * @param $blacklisted
     *
     * @return $this
     */
    public function setBlacklisted($blacklisted)
    {
        $this->blacklisted = (bool) $blacklisted;

        return $this;
    }

    /**
     * @return bool
     */
    public function getWireless()
    {
        return $this->wireless;

    }

    /**
     * @param $wireless
     *
     * @return $this
     */
    public function setWireless($wireless)
    {
        $this->wireless = (bool) $wireless;

        return $this;
    }
}