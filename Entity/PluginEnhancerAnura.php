<?php
/**
 * Created by PhpStorm.
 * User: nbush
 * Date: 10/18/18
 * Time: 3:22 PM
 */

namespace MauticPlugin\MauticEnhancerBundle\Entity;


use Doctrine\ORM\Mapping\ClassMetadata;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\CoreBundle\Entity\CommonEntity;

/**
 * Class PluginEnhancerAnura
 */
class PluginEnhancerAnura extends CommonEntity
{
    const TABLE_NAME = 'plugin_enhancer_anura';

    /**
     * @var int
     */
    protected $id;

    /**
     * @var \DateTime
     */
    protected $dateAdded;

    /**
     * @var string
     */
    protected $ipAddress;

    /**
     * @var string
     */
    protected $userAgent;

    /**
     * @var bool
     */
    protected $isSuspect;

    /**
     * @param ClassMetadata $metadata
     */
    public static function loadMetadata(ClassMetadata $metadata)
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable(MAUTIC_TABLE_PREFIX.self::TABLE_NAME)
            ->setCustomRepositoryClass(
                PluginEnhancerAnuraRepository::class
            );

        $builder->addId()

            ->addDateAdded()

            ->addIpAddress()

            ->addField(
                'userAgent',
                'string'
            )

            ->addField(
                'isSuspect',
                'bool'
            );
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
     * @return PluginEnhancerAnura
     */
    public function setDateAdded(\DateTime $dateAdded)
    {
        $this->dateAdded = $dateAdded;

        return $this;
    }

    /**
     * @return string
     */
    public function getIpAddress()
    {
        return $this->ipAddress;
    }

    /**
     * @param string $ipAddress
     *
     * @return PluginEnhancerAnura
     */
    public function setIpAddress($ipAddress)
    {
        $this->ipAddress = $ipAddress;

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
     * @param string $userAgent
     *
     * @return PluginEnhancerAnura
     */
    public function setUserAgent($userAgent)
    {
        $this->userAgent = $userAgent;

        return $this;
    }

    /**
     * @return bool
     */
    public function isSuspect()
    {
        return $this->isSuspect;
    }

    /**
     * @param bool $isSuspect
     *
     * @return PluginEnhancerAnura
     */
    public function setIsSuspect($isSuspect)
    {
        $this->isSuspect = $isSuspect;

        return $this;
    }
}
