<?php
/*
 * This file is part of the Calculation package.
 *
 * (c) bibi.nu <bibi@bibi.nu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Represents an application property.
 *
 * @author Laurent Muller
 *
 * @ORM\Table(name="sy_Property")
 * @ORM\Entity(repositoryClass="App\Repository\PropertyRepository")
 * @UniqueEntity(fields="name", message="property.unique_name")
 */
class Property extends AbstractEntity
{
    /**
     * The value used for FALSE or 0 value.
     */
    final public const FALSE_VALUE = 0;

    /**
     * The value used for TRUE value.
     */
    final public const TRUE_VALUE = 1;

    /**
     * The property name (unique).
     *
     * @ORM\Column(type="string", length=50, unique=true)
     * @Assert\Length(max=50)
     * @Assert\NotBlank
     */
    protected ?string $name = null;

    /**
     * The property value.
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Assert\Length(max=255)
     * @Assert\NotBlank
     */
    protected ?string $value = null;

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
     * Gets this property value as an array. Internally the array is decoded from a JSON string.
     */
    public function getArray(): ?array
    {
        if (!empty($this->value)) {
            /** @psalm-var array|null $result */
            $result = \json_decode($this->value, true);
            if (\JSON_ERROR_NONE === \json_last_error()) {
                return (array) $result;
            }
        }

        return null;
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
    public function getDate(): ?\DateTimeInterface
    {
        $timestamp = $this->getInteger();
        if (self::FALSE_VALUE !== $timestamp) {
            $date = \DateTime::createFromFormat('U', (string) $timestamp);
            if ($date instanceof \DateTime) {
                return $date;
            }
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
        return (string) $this->name;
    }

    /**
     * Gets the property value as string.
     */
    public function getString(): ?string
    {
        return $this->value;
    }

    /**
     * Sets the property value as an array. Internally the array is encoded as JSON string.
     */
    public function setArray(?array $value): self
    {
        return $this->setString(empty($value) ? null : (string) \json_encode($value));
    }

    /**
     * Sets the property value as boolean.
     */
    public function setBoolean(bool $value): self
    {
        return $this->setInteger($value ? self::TRUE_VALUE : self::FALSE_VALUE);
    }

    /**
     * Sets the property value as date.
     */
    public function setDate(?\DateTimeInterface $value): self
    {
        return $this->setInteger(null !== $value ? $value->getTimestamp() : self::FALSE_VALUE);
    }

    /**
     * Sets the property value as integer.
     */
    public function setInteger(int $value): self
    {
        return $this->setString((string) $value);
    }

    /**
     * Sets the property name.
     */
    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Sets the property value as string.
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
    public function setValue(mixed $value): self
    {
        if (\is_bool($value)) {
            return $this->setBoolean($value);
        }
        if (\is_int($value)) {
            return $this->setInteger($value);
        }
        if (\is_array($value)) {
            return $this->setArray($value);
        }
        if ($value instanceof \DateTimeInterface) {
            return $this->setDate($value);
        }
        if ($value instanceof AbstractEntity) {
            return $this->setInteger((int) $value->getId());
        }
        if ($value instanceof \BackedEnum) {
            return $this->setString((string) $value->value);
        }

        return $this->setString((string) $value);
    }
}
