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
    final public function __construct(public readonly int $red = 0, public readonly int $green = 0, public readonly int $blue = 0)
    {
    }

    /**
     * Gets the black color.
     *
     * Value is RGB(0, 0, 0).
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
     * Value is RGB(255, 0, 0).
     *
     * @return static
     */
    public static function blue(): self
    {
        return new static(self::MIN_VALUE, self::MIN_VALUE, self::MAX_VALUE);
    }

    /**
     * Gets the border cell color.
     *
     * Value is RGB(221, 221, 221).
     *
     * @return static
     */
    public static function cellBorder(): self
    {
        return new static(221, 221, 221);
    }

    /**
     * Creates a new instance.
     *
     * @param int[]|string|null $rgb an array containing the red, green and blue values or a hexadecimal string like
     *                               <code>'#FF8040'</code> or <code>'FFF'</code>
     *
     * @return static|null the color or null if the RGB value can not be parsed
     */
    public static function create(array|string|null $rgb): ?static
    {
        if (null === $rgb || '' === $rgb) {
            return null;
        }

        if (\is_string($rgb)) {
            return self::parse($rgb);
        }

        /** @psalm-var array{0: int<0, 255>, 1: int<0, 255>, 2: int<0, 255>}|false $rgb */
        if (\is_array($rgb) && 3 === \count($rgb)) {
            return new static($rgb[0], $rgb[1], $rgb[2]);
        }

        return null;
    }

    /**
     * Gets the dark-gray color.
     *
     * Value is RGB(169, 169, 169).
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
     * Value is RGB(0, 128, 0).
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
     * Value is RGB(128, 0, 0).
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
     * Gets the blue component.
     *
     * @psalm-return int<0, 255>
     */
    public function getBlue(): int
    {
        return $this->blue;
    }

    /**
     * Gets the green component.
     *
     * @psalm-return int<0, 255>
     */
    public function getGreen(): int
    {
        return $this->green;
    }

    /**
     * Gets the red component.
     *
     * @psalm-return int<0, 255>
     */
    public function getRed(): int
    {
        return $this->red;
    }

    /**
     * Gets the green color.
     *
     * Value is RGB(0, 255, 0).
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
     * Value is RGB(245, 245, 245).
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
     * Value is RGB(0, 0, 255).
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
     * Value is RGB(255, 0, 0).
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
     * Value is RGB(255, 255, 255).
     *
     * @return static
     */
    public static function white(): self
    {
        return new static(self::MAX_VALUE, self::MAX_VALUE, self::MAX_VALUE);
    }

    private static function parse(string $value): ?static
    {
        $value = (string) \preg_replace('/[^0-9A-Fa-f]/', '', $value);
        switch (\strlen($value)) {
            case 6:
                $color = \hexdec($value);
                /** @psalm-var int<0, 255> $r */
                $r = 0xFF & ($color >> 0x10);
                /** @psalm-var int<0, 255> $g */
                $g = 0xFF & ($color >> 0x8);
                /** @psalm-var int<0, 255> $b */
                $b = 0xFF & $color;

                return new static($r, $g, $b);
            case 3:
                /** @psalm-var int<0, 255> $r */
                $r = \hexdec(\str_repeat(\substr($value, 0, 1), 2));
                /** @psalm-var int<0, 255> $g */
                $g = \hexdec(\str_repeat(\substr($value, 1, 1), 2));
                /** @psalm-var int<0, 255> $b */
                $b = \hexdec(\str_repeat(\substr($value, 2, 1), 2));

                return new static($r, $g, $b);
            default:
                return null;
        }
    }
}
