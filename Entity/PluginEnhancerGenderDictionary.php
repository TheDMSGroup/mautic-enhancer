<?php

namespace MauticPlugin\MauticEnhancerBundle\Entity;

use Mautic\CoreBundle\Entity\CommonEntity;
use Doctrine\ORM\Mapping\ClassMetadata;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;

class PluginEnhancerGenderDictionary extends CommonEntity
{
    /* Table name */
    const TABLE_NAME = 'plugin_enhancer_gender_dictionary';

    /** @var int */
    protected $id;

    /** @var string */
    protected $name;

    /** @var string */
    protected $gender;

    /** @var float */
    protected $probability;

    public static function loadMetadata(ClassMetadata $metadata)
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable(self::TABLE_NAME)
            ->setCustomRepositoryClass('MauticPlugin\MauticEnhancerBundle\Entity\MauticPluginGenderDictionaryRepository');

        $builder->addIdColumns();


        $builder->createField('name', 'string')
            ->build();

        $builder->createField('gender', 'string')
            ->length(1)
            ->build();

        $builder->createField('probability', 'decimal')
            ->precision(7)
            ->scale(4)
            ->build();

        $builder->addIndex(['name'], 'idx_name');
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
     */
    public function setName(string $name): void
    {
        $this->name = $name;
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
     */
    public function setGender(string $gender): void
    {
        $this->gender = $gender;
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
     */
    public function setProbability(float $probability): void
    {
        $this->probability = $probability;
    }
}
