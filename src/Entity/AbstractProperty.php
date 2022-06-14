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
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Represent an abstract property.
 */
#[ORM\MappedSuperclass]
abstract class AbstractProperty extends AbstractEntity
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
     * The property name.
     */
    #[Assert\NotBlank]
    #[Assert\Length(max: 50)]
    #[ORM\Column(length: 50)]
    private ?string $name;

    /**
     * The property value.
     */
    #[Assert\NotBlank]
    #[Assert\Length(max: self::MAX_STRING_LENGTH)]
    #[ORM\Column(nullable: true)]
    private ?string $value = null;

    /**
     * Constructor.
     *
     * @param string|null $name the optional name
     */
    final public function __construct(?string $name = null)
    {
        $this->name = $name;
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
    public function setArray(?array $value): static
    {
        return $this->setString(empty($value) ? null : (string) \json_encode($value));
    }

    /**
     * Sets the property value as boolean.
     */
    public function setBoolean(bool $value): static
    {
        return $this->setInteger($value ? self::TRUE_VALUE : self::FALSE_VALUE);
    }

    /**
     * Sets the property value as date.
     */
    public function setDate(?\DateTimeInterface $value): static
    {
        return $this->setInteger(null !== $value ? $value->getTimestamp() : self::FALSE_VALUE);
    }

    /**
     * Sets the property value as integer.
     */
    public function setInteger(int $value): static
    {
        return $this->setString((string) $value);
    }

    /**
     * Sets the property name.
     */
    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Sets the property value as string.
     */
    public function setString(?string $value): static
    {
        $this->value = $value;

        return $this;
    }

    /**
     * Sets the property value.
     *
     * @param mixed $value the value to set. This function try first to convert the value to an appropriate type (bool, int, etc...).
     */
    public function setValue(mixed $value): static
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
