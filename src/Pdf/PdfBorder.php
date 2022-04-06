<?php
/*
 * This file is part of the Calculation package.
 *
 * (c) bibi.nu. <bibi@bibi.nu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace App\Pdf;

/**
 * Define a cell border.
 *
 * @author Laurent Muller
 */
class PdfBorder
{
    /**
     * Draw a border on all four sides.
     */
    final public const ALL = 1;

    /**
     * Draw the border around the rectangle.
     */
    final public const BORDER = 'D';

    /**
     * Draw the border and fill the rectangle.
     */
    final public const BOTH = 'FD';

    /**
     * Draw a border on the bottom side.
     */
    final public const BOTTOM = 'B';

    /**
     * Fill the rectangle.
     */
    final public const FILL = 'F';

    /**
     * Inherited border.
     */
    final public const INHERITED = -1;

    /**
     * Draw a border on the left side.
     */
    final public const LEFT = 'L';

    /**
     * No border is draw.
     */
    final public const NONE = 0;

    /**
     * Draw a border on the right side.
     */
    final public const RIGHT = 'R';

    /**
     * Draw a border on the top side.
     */
    final public const TOP = 'T';

    /**
     * The value.
     */
    private string|int $value = self::ALL;

    /**
     * Constructor.
     */
    public function __construct(string|int $value = self::ALL)
    {
        $this->setValue($value);
    }

    /**
     * Creates a new instance with all border set.
     */
    public static function all(): self
    {
        return new self(self::ALL);
    }

    /**
     * Creates a new instance with border set.
     */
    public static function border(): self
    {
        return new self(self::BORDER);
    }

    /**
     * Creates a new instance with both (border and fill) set.
     */
    public static function both(): self
    {
        return new self(self::BOTH);
    }

    /**
     * Creates a new instance with bottom border set.
     */
    public static function bottom(): self
    {
        return new self((self::BOTTOM));
    }

    /**
     * Creates a new instance with fill set.
     */
    public static function fill(): self
    {
        return new self(self::FILL);
    }

    /**
     * Gets the style used to draw a rectangle.
     *
     * @see PdfBorder::isRectangleStyle()
     * @see PdfDocument::rectangle()
     */
    public function getRectangleStyle(): string
    {
        return match ($this->value) {
            self::ALL => self::BORDER,
            self::BOTH,
            self::FILL,
            self::BORDER => $this->value,
            default => ''
        };
    }

    /**
     * Gets the value.
     */
    public function getValue(): int|string
    {
        return $this->value;
    }

    /**
     * Creates a new instance with inherited border set.
     */
    public static function inherited(): self
    {
        return new self(self::INHERITED);
    }

    /**
     * Returns if all is set.
     */
    public function isAll(): bool
    {
        return $this->isSet(self::ALL);
    }

    /**
     * Returns if border is set.
     */
    public function isBorder(): bool
    {
        return $this->isSet(self::BORDER);
    }

    /**
     * Returns if both (border and fill) is set.
     */
    public function isBoth(): bool
    {
        return $this->isSet(self::BOTH);
    }

    /**
     * Returns if bottom border is set.
     */
    public function isBottom(): bool
    {
        return $this->isSet(self::BOTTOM);
    }

    /**
     * Returns if fill is set.
     */
    public function isFill(): bool
    {
        return $this->isSet(self::FILL);
    }

    /**
     * Returns if inherited is set.
     */
    public function isInherited(): bool
    {
        return $this->isSet(self::INHERITED);
    }

    /**
     * Returns if left is set.
     */
    public function isLeft(): bool
    {
        return $this->isSet(self::LEFT);
    }

    /**
     * Returns if none is set.
     */
    public function isNone(): bool
    {
        return $this->isSet(self::NONE);
    }

    /**
     * Returns a value indicating if this value is valid to draw a rectangle.
     *
     * @see PdfBorder::getRectangleStyle()
     * @see PdfDocument::rectangle()
     */
    public function isRectangleStyle(): bool
    {
        return match ($this->value) {
            self::ALL,
            self::BOTH,
            self::FILL,
            self::BORDER => true,
            default => false,
        };
    }

    /**
     * Returns if right is set.
     */
    public function isRight(): bool
    {
        return $this->isSet(self::RIGHT);
    }

    /**
     * Returns if the given value is set.
     */
    public function isSet(string|int $value): bool
    {
        if (\is_string($value)) {
            return \str_contains((string) $this->value, $value);
        }

        return $this->value === $value;
    }

    /**
     * Returns if top is set.
     */
    public function isTop(): bool
    {
        return $this->isSet(self::TOP);
    }

    /**
     * Creates a new instance with none border set.
     */
    public static function none(): self
    {
        return new self(self::NONE);
    }

    /**
     * Sets the value.
     */
    public function setValue(int|string $value): self
    {
        if (empty($value)) {
            $this->value = self::NONE;
        } elseif (self::ALL === $value || self::INHERITED === $value || self::BOTH === $value || self::FILL === $value || self::BORDER === $value) {
            $this->value = $value;
        } else {
            $value = \strtoupper((string) $value);
            $result = (string) \preg_replace('/[^LRTB]/', '', $value);
            $result = \count_chars($result, 3);
            $this->value = empty($result) ? self::NONE : $result; // @phpstan-ignore-line
        }

        return $this;
    }

    /**
     * Creates a new instance with top border set.
     */
    public static function top(): self
    {
        return new self((self::TOP));
    }
}
