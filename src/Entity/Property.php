<?php
/*
 * This file is part of the Calculation package.
 *
 * Copyright (c) 2019 bibi.nu. All rights reserved.
 *
 * This computer code is protected by copyright law and international
 * treaties. Unauthorised reproduction or distribution of this code, or
 * any portion of it, may result in severe civil and criminal penalties,
 * and will be prosecuted to the maximum extent possible under the law.
 */

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Represents an application property.
 *
 * @ORM\Table(name="sy_Property")
 * @ORM\Entity(repositoryClass="App\Repository\PropertyRepository")
 * @UniqueEntity(fields="name", message="property.unique_name")
 */
class Property extends BaseEntity
{
    /**
     * The value used for FALSE or 0 value.
     */
    public const FALSE_VALUE = 0;

    /**
     * The value used for TRUE value.
     */
    public const TRUE_VALUE = 1;

    /**
     * The property name (unique).
     *
     * @ORM\Column(name="name", type="string", length=50, unique=true)
     * @Assert\NotBlank
     * @Assert\Length(max=50)
     *
     * @var string
     */
    protected $name;

    /**
     * The property value.
     *
     * @ORM\Column(name="value", type="string", length=255, nullable=true)
     * @Assert\Length(max=255)
     *
     * @var string
     */
    protected $value;

    /**
     * Constructor.
     */
    public function __construct()
    {
    }

    /**
     * Creates a property.
     *
     * @param string $name the property name
     *
     * @return Property the newly created property
     */
    public static function create(string $name): self
    {
        $property = new self();

        return $property->setName($name);
    }

    /**
     * Gets this property value as boolean.
     */
    public function getBoolean(): bool
    {
        return self::FALSE_VALUE !== $this->getInteger();
    }

    /**
     * Gets this property value as date.
     */
    public function getDate(): ?\DateTime
    {
        $timestamp = $this->getInteger();
        if (self::FALSE_VALUE !== $timestamp) {
            return \DateTime::createFromFormat('U', (string) $timestamp);
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getDisplay(): string
    {
        return $this->name ?: parent::getDisplay();
    }

    /**
     * Gets this property value as integer.
     */
    public function getInteger(): int
    {
        return (int) ($this->value ?: self::FALSE_VALUE);
    }

    /**
     * Gets the property name.
     *
     * @return string the property name
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Gets the property value as string.
     *
     * @return string the property value
     */
    public function getString(): ?string
    {
        return $this->value;
    }

    /**
     * Sets the property value as boolean.
     *
     * @param bool $value the value to set
     */
    public function setBoolean(bool $value): self
    {
        return $this->setInteger($value ? self::TRUE_VALUE : self::FALSE_VALUE);
    }

    /**
     * Sets the property value as date.
     *
     * @param \DateTimeInterface|null $value the value to set
     */
    public function setDate(?\DateTimeInterface $value): self
    {
        return $this->setInteger($value ? $value->getTimestamp() : self::FALSE_VALUE);
    }

    /**
     * Sets the property value as integer.
     *
     * @param int $value the value to set
     */
    public function setInteger(int $value): self
    {
        return $this->setString((string) $value);
    }

    /**
     * Sets the property name.
     *
     * @param string $name the name to set
     */
    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Sets the property value as string.
     *
     * @param string|null $value the value to set
     */
    public function setString(?string $value): self
    {
        $this->value = $value;

        return $this;
    }

    /**
     * Sets the property value.
     *
     * @param mixed $value the value to set. This function try first to convert the value to an appropriate type (bool, int, etc...).
     */
    public function setValue($value): self
    {
        if (\is_bool($value)) {
            return $this->setBoolean($value);
        }
        if (\is_int($value)) {
            return $this->setInteger($value);
        }
        if ($value instanceof \DateTimeInterface) {
            return $this->setDate($value);
        }
        if ($value instanceof EntityInterface) {
            return $this->setInteger($value->getId());
        }

        return $this->setString((string) $value);
    }
}
