<?php

namespace MauticPlugin\MauticEnhancerBundle\Entity;

use Doctrine\ORM\Mapping\ClassMetadata;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\CoreBundle\Entity\CommonEntity;

class PluginEnhancerGenderName extends CommonEntity
{
    /* Table name */
    const TABLE_NAME = 'plugin_enhancer_gender_names';

    /** @var int */
    protected $id;

    /** @var string */
    protected $name;

    /** @var string */
    protected $gender;

    /** @var float */
    protected $probability;

    /** @var int */
    protected $count;

    /**
     * @param ClassMetadata $metadata
     */
    public static function loadMetadata(ClassMetadata $metadata)
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable(MAUTIC_TABLE_PREFIX.self::TABLE_NAME)
            ->setCustomRepositoryClass(PluginEnhancerGenderNameRepository::class);

        $builder->addId();

        $builder->createField('name', 'string')
            ->build();

        $builder->createField('gender', 'string')
            ->length(1)
            ->build();

        $builder->createField('probability', 'decimal')
            ->precision(7)
            ->scale(4)
            ->build();

        $builder->createField('count', 'integer')
            ->build();

        $builder->addUniqueConstraint(['name'], 'key_name');
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     *
     * @return $this
     */
    public function setName(string $name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getGender(): string
    {
        return $this->gender;
    }

    /**
     * @param string $gender
     *
     * @return $this
     */
    public function setGender(string $gender)
    {
        $this->gender = $gender;

        return $this;
    }

    /**
     * @return float
     */
    public function getProbability(): float
    {
        return $this->probability;
    }

    /**
     * @param float $probability
     *
     * @return $this
     */
    public function setProbability(float $probability)
    {
        $this->probability = $probability;

        return $this;
    }

    /**
     * @return int
     */
    public function getCount(): int
    {
        return $this->count;
    }

    /**
     * @param int $count
     *
     * @return $this
     */
    public function setCount(int $count)
    {
        $this->count = $count;

        return $this;
    }
}
