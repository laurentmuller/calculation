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
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Clock\DatePoint;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Represent an abstract property.
 */
#[ORM\MappedSuperclass]
abstract class AbstractProperty extends AbstractEntity
{
    /** The value used for FALSE value. */
    private const int FALSE_VALUE = 0;

    /** The value used for TRUE value. */
    private const int TRUE_VALUE = 1;

    /** The property value. */
    #[Assert\NotBlank]
    #[Assert\Length(max: self::MAX_STRING_LENGTH)]
    #[ORM\Column(nullable: true)]
    public ?string $value = null;

    /**
     * @param string $name the property name
     */
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(max: 50)]
        #[ORM\Column(length: 50)]
        public string $name = ''
    ) {
    }

    /**
     * Gets this property value as boolean.
     */
    public function asBoolean(): bool
    {
        return self::FALSE_VALUE !== $this->asInteger();
    }

    /**
     * Gets this property value as a date point.
     */
    public function asDatePoint(): ?DatePoint
    {
        $timestamp = $this->asInteger();
        if (self::FALSE_VALUE !== $timestamp) {
            return DatePoint::createFromTimestamp($timestamp);
        }

        return null;
    }

    /**
     * Gets this property value as float.
     */
    public function asFloat(): float
    {
        return (float) ($this->value ?? 0.0);
    }

    /**
     * Gets this property value as integer.
     */
    public function asInteger(): int
    {
        return (int) ($this->value ?? self::FALSE_VALUE);
    }

    /**
     * Gets this property value as an IntBackedEnum.
     *
     * @template T of \BackedEnum
     *
     * @param class-string<T> $type
     *
     * @return T|null
     */
    public function asIntEnum(string $type): ?\BackedEnum
    {
        return $type::tryFrom($this->asInteger());
    }

    /**
     * Gets this property value as a StringBackedEnum.
     *
     * @template T of \BackedEnum
     *
     * @param class-string<T> $type
     *
     * @return T|null
     */
    public function asStringEnum(string $type): ?\BackedEnum
    {
        return $type::tryFrom((string) $this->value);
    }

    #[\Override]
    public function getDisplay(): string
    {
        return '' !== $this->name ? $this->name : parent::getDisplay();
    }

    /**
     * Sets the property value as a backed enum.
     */
    public function withBackedEnum(\BackedEnum $value): static
    {
        return $this->withString((string) $value->value);
    }

    /**
     * Sets the property value as boolean.
     */
    public function withBoolean(bool $value): static
    {
        return $this->withInteger($value ? self::TRUE_VALUE : self::FALSE_VALUE);
    }

    /**
     * Sets the property value as date.
     */
    public function withDate(?DatePoint $value): static
    {
        return $this->withInteger($value instanceof DatePoint ? $value->getTimestamp() : self::FALSE_VALUE);
    }

    /**
     * Sets the property value as float.
     */
    public function withFloat(float $value): static
    {
        return $this->withString((string) $value);
    }

    /**
     * Sets the property value as integer.
     */
    public function withInteger(int $value): static
    {
        return $this->withString((string) $value);
    }

    /**
     * Sets the property name.
     */
    public function withName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Sets the property value as string.
     */
    public function withString(?string $value): static
    {
        $this->value = $value;

        return $this;
    }

    /**
     * Sets the property value.
     *
     * This function tries first to convert the value to an appropriate type (bool, int, etc...).
     */
    public function withValue(mixed $value): static
    {
        if ($value instanceof \BackedEnum) {
            return $this->withBackedEnum($value);
        }
        if (\is_bool($value)) {
            return $this->withBoolean($value);
        }
        if ($value instanceof DatePoint) {
            return $this->withDate($value);
        }
        if (\is_float($value)) {
            return $this->withFloat($value);
        }
        if (\is_int($value)) {
            return $this->withInteger($value);
        }
        if ($value instanceof EntityInterface) {
            return $this->withInteger((int) $value->id);
        }

        return $this->withString((string) $value);
    }
}
