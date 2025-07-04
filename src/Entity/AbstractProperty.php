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

use App\Interfaces\EntityInterface;
use App\Utils\StringUtils;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Clock\DatePoint;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Represent an abstract property.
 */
#[ORM\MappedSuperclass]
abstract class AbstractProperty extends AbstractEntity
{
    /**
     * The value used for FALSE value.
     */
    final public const FALSE_VALUE = 0;

    /**
     * The value used for TRUE value.
     */
    final public const TRUE_VALUE = 1;

    /**
     * The property value.
     */
    #[Assert\NotBlank]
    #[Assert\Length(max: self::MAX_STRING_LENGTH)]
    #[ORM\Column(nullable: true)]
    protected ?string $value = null;

    /**
     * @param ?string $name the property name
     */
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(max: 50)]
        #[ORM\Column(length: 50)]
        protected ?string $name = null
    ) {
    }

    /**
     * Gets this property value as an array. Internally, the array is decoded from a JSON string.
     */
    public function getArray(): ?array
    {
        if (StringUtils::isString($this->value)) {
            try {
                return StringUtils::decodeJson($this->value);
            } catch (\InvalidArgumentException) {
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
    public function getDate(): ?DatePoint
    {
        $timestamp = $this->getInteger();
        if (self::FALSE_VALUE !== $timestamp) {
            return DatePoint::createFromTimestamp($timestamp);
        }

        return null;
    }

    #[\Override]
    public function getDisplay(): string
    {
        return $this->name ?? parent::getDisplay();
    }

    /**
     * Gets this property value as float.
     */
    public function getFloat(): float
    {
        return (float) ($this->value ?? 0.0);
    }

    /**
     * Gets this property value as integer.
     */
    public function getInteger(): int
    {
        return (int) ($this->value ?? self::FALSE_VALUE);
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
    public function getValue(): ?string
    {
        return $this->value;
    }

    /**
     * Sets the property value as an array. Internally, the array is encoded to a JSON string.
     */
    public function setArray(?array $value): static
    {
        return $this->setString(null === $value || [] === $value ? null : StringUtils::encodeJson($value));
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
    public function setDate(?DatePoint $value): static
    {
        return $this->setInteger($value instanceof DatePoint ? $value->getTimestamp() : self::FALSE_VALUE);
    }

    /**
     * Sets the property value as float.
     */
    public function setFloat(float $value): static
    {
        return $this->setString((string) $value);
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
     * This function tries first to convert the value to an appropriate type (bool, int, etc...).
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
        if ($value instanceof DatePoint) {
            return $this->setDate($value);
        }
        if ($value instanceof EntityInterface) {
            return $this->setInteger((int) $value->getId());
        }
        if ($value instanceof \BackedEnum) {
            return $this->setString((string) $value->value);
        }

        return $this->setString((string) $value);
    }
}
