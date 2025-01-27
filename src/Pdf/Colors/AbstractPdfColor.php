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

namespace App\Pdf\Colors;

use App\Pdf\Interfaces\PdfDocumentUpdaterInterface;

/**
 * Define an RGB color.
 */
abstract class AbstractPdfColor implements PdfDocumentUpdaterInterface
{
    /**
     * The maximum value allowed for a component (inclusive).
     */
    final protected const MAX_VALUE = 255;

    /**
     * The minimum value allowed for a component (inclusive).
     */
    final protected const MIN_VALUE = 0;

    /**
     * All values must be between 0 and 255 inclusive.
     *
     * @param int<0, 255> $red   the red component
     * @param int<0, 255> $green the green component
     * @param int<0, 255> $blue  the blue component
     */
    final public function __construct(public readonly int $red, public readonly int $green, public readonly int $blue)
    {
    }

    /**
     * Gets the hexadecimal representation of these values.
     *
     * @param string $prefix the optional prefix to prepend
     *
     * @return string the hexadecimal value as six lower case characters (like <code>'ff8040'</code>)
     */
    public function asHex(string $prefix = ''): string
    {
        return $prefix . \substr('000000' . \dechex($this->asInt()), -6);
    }

    /**
     * Gets the integer representation of these values.
     */
    public function asInt(): int
    {
        return (($this->red & 0xFF) << 0x10) | (($this->green & 0xFF) << 0x8) | ($this->blue & 0xFF);
    }

    /**
     * Gets the black color.
     *
     * The value is RGB(0, 0, 0).
     *
     * @return static
     */
    public static function black(): self
    {
        return new static(self::MIN_VALUE, self::MIN_VALUE, self::MIN_VALUE);
    }

    /**
     * Gets the blue color.
     *
     * The value is RGB(255, 0, 0).
     *
     * NB: This color is also used for links.
     *
     * @return static
     */
    public static function blue(): self
    {
        return new static(self::MIN_VALUE, self::MIN_VALUE, self::MAX_VALUE);
    }

    /**
     * Gets the cell border color.
     *
     * The value is RGB(221, 221, 221).
     *
     * @return static
     */
    public static function cellBorder(): self
    {
        return new static(221, 221, 221);
    }

    /**
     * Creates a new instance from the given value.
     *
     * @param int|string|null $value an integer value or a hexadecimal string
     *                               like <code>'FF8040'</code> or <code>'FFF'</code>
     *
     * @return static|null the color or null if the RGB value cannot be parsed
     *
     * @psalm-param int|string|null $value
     */
    public static function create(int|string|null $value): ?static
    {
        if (null === $value || '' === $value) {
            return null;
        }

        if (\is_int($value)) {
            return self::createFromInt($value);
        }

        return self::createFromString($value);
    }

    /**
     * Gets the dark-gray color.
     *
     * The value is RGB(169, 169, 169).
     *
     * @return static
     */
    public static function darkGray(): self
    {
        return new static(169, 169, 169);
    }

    /**
     * Gets the dark-green color.
     *
     * The value is RGB(0, 128, 0).
     *
     * @return static
     */
    public static function darkGreen(): self
    {
        return new static(self::MIN_VALUE, 128, self::MIN_VALUE);
    }

    /**
     * Gets the dark-red color.
     *
     * The value is RGB(128, 0, 0).
     *
     * @return static
     *
     * @psalm-api
     */
    public static function darkRed(): self
    {
        return new static(128, self::MIN_VALUE, self::MIN_VALUE);
    }

    /**
     * Gets the default color.
     */
    abstract public static function default(): self;

    /**
     * Gets the green color.
     *
     * The value is RGB(0, 255, 0).
     *
     * @return static
     */
    public static function green(): self
    {
        return new static(self::MIN_VALUE, self::MAX_VALUE, self::MIN_VALUE);
    }

    /**
     * Gets the header fill color.
     *
     * The value is RGB(245, 245, 245).
     *
     * @return static
     */
    public static function header(): self
    {
        return new static(245, 245, 245);
    }

    /**
     * Gets the red color.
     *
     * The value is RGB(255, 0, 0).
     *
     * @return static
     */
    public static function red(): self
    {
        return new static(self::MAX_VALUE, self::MIN_VALUE, self::MIN_VALUE);
    }

    /**
     * Gets the white color.
     *
     * The value is RGB(255, 255, 255).
     *
     * @return static
     */
    public static function white(): self
    {
        return new static(self::MAX_VALUE, self::MAX_VALUE, self::MAX_VALUE);
    }

    private static function createFrom3Chars(string $value): static
    {
        $red = self::hexdec(\str_repeat(\substr($value, 0, 1), 2));
        $green = self::hexdec(\str_repeat(\substr($value, 1, 1), 2));
        $blue = self::hexdec(\str_repeat(\substr($value, 2, 1), 2));

        return new static($red, $green, $blue);
    }

    private static function createFrom6Chars(string $value): static
    {
        $red = self::hexdec(\substr($value, 0, 2));
        $green = self::hexdec(\substr($value, 2, 2));
        $blue = self::hexdec(\substr($value, 4, 2));

        return new static($red, $green, $blue);
    }

    private static function createFromInt(int $value): static
    {
        /** @psalm-var int<0, 255> $red */
        $red = 0xFF & ($value >> 0x10);
        /** @psalm-var int<0, 255> $green */
        $green = 0xFF & ($value >> 0x8);
        /** @psalm-var int<0, 255> $blue */
        $blue = 0xFF & $value;

        return new static($red, $green, $blue);
    }

    private static function createFromString(string $value): ?static
    {
        $value = (string) \preg_replace('/[^0-9A-F]/i', '', $value);

        return match (\strlen($value)) {
            3 => self::createFrom3Chars($value),
            6 => self::createFrom6Chars($value),
            default => null,
        };
    }

    /**
     * @psalm-return int<0, 255>
     */
    private static function hexdec(string $value): int
    {
        /** @psalm-var int<0, 255> */
        return (int) \hexdec($value);
    }
}
