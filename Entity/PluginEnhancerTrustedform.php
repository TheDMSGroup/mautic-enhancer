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

use DateTime;
use Doctrine\ORM\Mapping\ClassMetadata;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\CoreBundle\Entity\CommonEntity;
use Mautic\LeadBundle\Entity\Lead as Contact;

/**
 * Class PluginEnhancerTrustedform.
 */
class PluginEnhancerTrustedform extends CommonEntity
{
    const TABLE_NAME = 'plugin_enhancer_trustedform';

    /** @var int */
    protected $id;

    /** @var string Phone number in E164 format before we send it to the API */
    protected $phone;

    /** @var string The SID returned from the Trustedform API */
    protected $sid;

    /** @var int The FederalDNC code provided by the Trustedform API */
    protected $code;

    /** @var bool Set to true by the Trustedform API if the number is in their aggregated Trustedforms. */
    protected $result = false;

    /** @var bool The wireless status provided by the Trustedform API */
    protected $wireless = false;

    /** @var DateTime The date this record was last received/updated from the API */
    protected $dateAdded;

    /** @var string */
    protected $token;

    /** @var string */
    protected $ip;

    /** @var string */
    protected $location;

    /** @var string */
    protected $parentLocation;

    /** @var bool */
    protected $framed;

    /** @var string */
    protected $browser;

    /** @var string */
    protected $operatingSystem;

    /** @var string */
    protected $userAgent;

    /** @var DateTime */
    protected $createdAt;

    /** @var int */
    protected $eventDuration;

    /** @var DateTime */
    protected $expiresAt;

    /** @var string */
    protected $shareUrl;

    /** @var string */
    protected $geo;

    /** @var string */
    protected $claims;

    /** @var Contact */
    protected $contact;

    /** @var int */
    protected $attempts = 0;

    /** @var int */
    protected $status = 0;

    /**
     * @param ClassMetadata $metadata
     */
    public static function loadMetadata(ClassMetadata $metadata)
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable(MAUTIC_TABLE_PREFIX.self::TABLE_NAME);

        $builder->setCustomRepositoryClass(PluginEnhancerTrustedformRepository::class);

        $builder->addId();

        $builder->addDateAdded();

        $builder->addContact(true, null);

        $builder->addNamedField('token', 'string', 'token', true);

        $builder->addNamedField('attempts', 'integer', 'attempts', false);

        $builder->addNamedField('status', 'integer', 'status', false);

        $builder->addNamedField('ip', 'string', 'ip', true);

        $builder->addNamedField('location', 'string', 'location', true);

        $builder->addNamedField('parentLocation', 'string', 'parent_location', true);

        $builder->addNamedField('framed', 'boolean', 'framed', true);

        $builder->addNamedField('browser', 'string', 'browser', true);

        $builder->addNamedField('operatingSystem', 'string', 'operating_system', true);

        $builder->addNamedField('userAgent', 'string', 'user_agent', true);

        $builder->addNamedField('eventDuration', 'integer', 'event_duration', true);

        $builder->addNamedField('createdAt', 'datetime', 'created_at', true);

        $builder->addNamedField('expiresAt', 'datetime', 'expires_at', true);

        $builder->addNamedField('shareUrl', 'text', 'share_url', true);

        $builder->addNamedField('geo', 'text', 'geo', true);

        $builder->addNamedField('claims', 'text', 'claims', true);

        $builder->addIndex(['status', 'attempts'], 'status_attempts');
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return DateTime
     */
    public function getDateAdded()
    {
        return $this->dateAdded;
    }

    /**
     * @param DateTime $dateAdded
     *
     * @return PluginEnhancerTrustedform
     */
    public function setDateAdded(DateTime $dateAdded)
    {
        $this->dateAdded = $dateAdded;

        return $this;
    }

    /**
     * @return string
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * @param $token
     *
     * @return $this
     */
    public function setToken($token)
    {
        $this->token = $token;

        return $this;
    }

    /**
     * @return string
     */
    public function getIp()
    {
        return $this->ip;
    }

    /**
     * @param $ip
     *
     * @return $this
     */
    public function setIp($ip)
    {
        $this->ip = $ip;

        return $this;
    }

    /**
     * @return string
     */
    public function getLocation()
    {
        return $this->location;
    }

    /**
     * @param $location
     *
     * @return $this
     */
    public function setLocation($location)
    {
        $this->location = $location;

        return $this;
    }

    /**
     * @return string
     */
    public function getParentLocation()
    {
        return $this->parentLocation;
    }

    /**
     * @param $parentLocation
     *
     * @return $this
     */
    public function setParentLocation($parentLocation)
    {
        $this->parentLocation = $parentLocation;

        return $this;
    }

    /**
     * @return bool
     */
    public function getFramed()
    {
        return (bool) $this->framed;
    }

    /**
     * @param $framed
     *
     * @return $this
     */
    public function setFramed($framed)
    {
        $this->framed = (bool) $framed;

        return $this;
    }

    /**
     * @return string
     */
    public function getBrowser()
    {
        return $this->browser;
    }

    /**
     * @param $browser
     *
     * @return $this
     */
    public function setBrowser($browser)
    {
        $this->browser = $browser;

        return $this;
    }

    /**
     * @return string
     */
    public function getOperatingSystem()
    {
        return $this->operatingSystem;
    }

    /**
     * @param $operatingSystem
     *
     * @return $this
     */
    public function setOperatingSystem($operatingSystem)
    {
        $this->operatingSystem = $operatingSystem;

        return $this;
    }

    /**
     * @return string
     */
    public function getUserAgent()
    {
        return $this->userAgent;
    }

    /**
     * @param $userAgent
     *
     * @return $this
     */
    public function setUserAgent($userAgent)
    {
        $this->userAgent = $userAgent;

        return $this;
    }

    /**
     * @return Contact
     */
    public function getContact()
    {
        return $this->contact;
    }

    /**
     * @param Contact $contact
     *
     * @return $this
     */
    public function setContact($contact)
    {
        $this->contact = $contact;

        return $this;
    }

    /**
     * @return DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @param DateTime $createdAt
     *
     * @return $this
     */
    public function setCreatedAt(DateTime $createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * @return int
     */
    public function getEventDuration()
    {
        return $this->eventDuration;
    }

    /**
     * @param $eventDuration
     *
     * @return $this
     */
    public function setEventDuration($eventDuration)
    {
        $this->eventDuration = $eventDuration;

        return $this;
    }

    /**
     * @return DateTime
     */
    public function getExpiresAt()
    {
        return $this->expiresAt;
    }

    /**
     * @param DateTime $expiresAt
     *
     * @return $this
     */
    public function setExpiresAt(DateTime $expiresAt)
    {
        $this->expiresAt = $expiresAt;

        return $this;
    }

    /**
     * @return string
     */
    public function getShareUrl()
    {
        return $this->shareUrl;
    }

    /**
     * @param $shareUrl
     *
     * @return $this
     */
    public function setShareUrl($shareUrl)
    {
        $this->shareUrl = $shareUrl;

        return $this;
    }

    /**
     * @return string
     */
    public function getGeo()
    {
        return $this->geo;
    }

    /**
     * @param $geo
     *
     * @return $this
     */
    public function setGeo($geo)
    {
        $this->geo = $geo;

        return $this;
    }

    /**
     * @return string
     */
    public function getClaims()
    {
        return $this->claims;
    }

    /**
     * @param $claims
     *
     * @return $this
     */
    public function setClaims($claims)
    {
        $this->claims = $claims;

        return $this;
    }

    /**
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param $status
     *
     * @return $this
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * @return int
     */
    public function getAttempts()
    {
        return $this->attempts;
    }

    /**
     * @param $attempts
     *
     * @return $this
     */
    public function setAttempts($attempts)
    {
        $this->attempts = $attempts;

        return $this;
    }
}
