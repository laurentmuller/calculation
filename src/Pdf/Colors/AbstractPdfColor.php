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
 * Define a RGB color.
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
     * @param int $red   the red component
     * @param int $green the green component
     * @param int $blue  the blue component
     *
     * @psalm-param int<0, 255> $red
     * @psalm-param int<0, 255> $green
     * @psalm-param int<0, 255> $blue
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
     * @param int[]|string|null $rgb an array containing the red, green and blue values, an integer value or a
     *                               hexadecimal string like <code>'FF8040'</code> or <code>'FFF'</code>
     *
     * @return static|null the color or null if the RGB value cannot be parsed
     *
     * @psalm-param int<0, 255>[]|int|string|null $rgb
     */
    public static function create(array|int|string|null $rgb): ?static
    {
        if (null === $rgb || '' === $rgb) {
            return null;
        }

        if (\is_array($rgb)) {
            return 3 === \count($rgb) ? new static($rgb[0], $rgb[1], $rgb[2]) : null;
        }

        if (\is_int($rgb)) {
            return self::createFromInt($rgb);
        }

        $rgb = (string) \preg_replace('/[^0-9A-F]/i', '', $rgb);

        return match (\strlen($rgb)) {
            6 => self::createFrom6Chars($rgb),
            3 => self::createFrom3Chars($rgb),
            default => null,
        };
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
     * Gets the link color (blue).
     *
     * The value is RGB(0, 0, 255).
     *
     * @return static
     */
    public static function link(): self
    {
        return static::blue();
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

    private static function createFrom3Chars(string $rgb): static
    {
        /** @psalm-var int<0, 255> $r */
        $r = \hexdec(\str_repeat(\substr($rgb, 0, 1), 2));
        /** @psalm-var int<0, 255> $g */
        $g = \hexdec(\str_repeat(\substr($rgb, 1, 1), 2));
        /** @psalm-var int<0, 255> $b */
        $b = \hexdec(\str_repeat(\substr($rgb, 2, 1), 2));

        return new static($r, $g, $b);
    }

    private static function createFrom6Chars(string $rgb): static
    {
        return self::createFromInt((int) \hexdec($rgb));
    }

    private static function createFromInt(int $rgb): static
    {
        /** @psalm-var int<0, 255> $r */
        $r = 0xFF & ($rgb >> 0x10);
        /** @psalm-var int<0, 255> $g */
        $g = 0xFF & ($rgb >> 0x8);
        /** @psalm-var int<0, 255> $b */
        $b = 0xFF & $rgb;

        return new static($r, $g, $b);
    }
}
