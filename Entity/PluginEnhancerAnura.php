<?php
/**
 * Created by PhpStorm.
 * User: nbush
 * Date: 10/18/18
 * Time: 3:22 PM.
 */

namespace MauticPlugin\MauticEnhancerBundle\Entity;

use Doctrine\ORM\Mapping\ClassMetadata;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\CoreBundle\Entity\CommonEntity;

/**
 * Class PluginEnhancerAnura.
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
     * @var string
     */
    protected $result;

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

        $builder
            ->addId()

            ->addDateAdded()

            ->addNamedField(
                'ipAddress',
                'string',
                'ip_address'
            )

            ->addNamedField(
                'userAgent',
                'string',
                'user_agent'
            )

            ->addNamedField(
                'result',
                'string',
                'result'
            );
        $builder->addUniqueConstraint(['ip_address', 'user_agent'], 'ip_user_agent');
        $builder->addIndex(['date_added'], 'idx_date_added');
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
     * @return string
     */
    public function getResult()
    {
        return $this->result;
    }

    /**
     * @param string $result
     *
     * @return PluginEnhancerAnura
     */
    public function setResult($result)
    {
        $this->result = $result;

        return $this;
    }
}
