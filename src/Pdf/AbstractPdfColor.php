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

namespace App\Pdf;

use App\Pdf\Interfaces\PdfDocumentUpdaterInterface;
use App\Traits\MathTrait;

/**
 * Define a RGB color.
 */
abstract class AbstractPdfColor implements PdfDocumentUpdaterInterface
{
    use MathTrait;

    /**
     * The maximum value allowed for a component (inclusive).
     */
    final protected const MAX_VALUE = 255;

    /**
     * The minimum value allowed for a component (inclusive).
     */
    final protected const MIN_VALUE = 0;

    /**
     * The blue component.
     *
     * @psalm-var int<0, 255>
     */
    private int $blue = 0;

    /**
     * The green component.
     *
     * @psalm-var int<0, 255>
     */
    private int $green = 0;

    /**
     * The red component.
     *
     * @psalm-var int<0, 255>
     */
    private int $red = 0;

    /**
     * Constructor.
     *
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
    final public function __construct(int $red = 0, int $green = 0, int $blue = 0)
    {
        $this->setRGB($red, $green, $blue);
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
     * @param int[]|string $rgb an array containing the red, green and blue values or a hexadecimal string
     *
     * @return static|null the color or null if the RGB value can not be parsed
     *
     * @see AbstractPdfColor::parse()
     */
    public static function create(array|string $rgb): ?static
    {
        if (\is_string($rgb)) {
            $rgb = self::parse($rgb);
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
     * Gets the red, the green and the blue values.
     *
     * @psalm-return array{0: int<0, 255>, 1: int<0, 255>, 2: int<0, 255>}
     */
    public function getRGB(): array
    {
        return [$this->red, $this->green, $this->blue];
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
     * Gets the header fill color. Value is RGB(245, 245, 245).
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
     * Converts the value to the RGB array.
     *
     * The value must be a hexadecimal string like <code>'#FF8040'</code> or <code>'FFF'</code>.
     *
     * @param ?string $value a hexadecimal string
     *
     * @return ?array the RGB array (<code>red, green, blue</code>) or <code>null</code> if the value can not be converted
     *
     * @psalm-return array{0: int<0, 255>, 1: int<0, 255>, 2: int<0, 255>}|null
     */
    public static function parse(?string $value): ?array
    {
        if (null === $value || '' === $value) {
            return null;
        }

        $parsed = (string) \preg_replace('/[^0-9A-Fa-f]/', '', $value);
        switch (\strlen($parsed)) {
            case 6:
                $color = \hexdec($parsed);
                /** @psalm-var int<0, 255> $r */
                $r = 0xFF & ($color >> 0x10);
                /** @psalm-var int<0, 255> $g */
                $g = 0xFF & ($color >> 0x8);
                /** @psalm-var int<0, 255> $b */
                $b = 0xFF & $color;

                return [$r, $g, $b];

            case 3:
                /** @psalm-var int<0, 255> $r */
                $r = \hexdec(\str_repeat(\substr($parsed, 0, 1), 2));
                /** @psalm-var int<0, 255> $g */
                $g = \hexdec(\str_repeat(\substr($parsed, 1, 1), 2));
                /** @psalm-var int<0, 255> $b */
                $b = \hexdec(\str_repeat(\substr($parsed, 2, 1), 2));

                return [$r, $g, $b];

            default:
                return null;
        }
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
     * Sets the blue component.
     *
     * @param int $blue the value to set. Must be between 0 and 255 inclusive.
     *
     * @psalm-param int<0, 255> $blue
     */
    public function setBlue(int $blue): static
    {
        $this->blue = self::checkColor($blue);

        return $this;
    }

    /**
     * Sets the green component.
     *
     * @param int $green the value to set. Must be between 0 and 255 inclusive.
     *
     * @psalm-param int<0, 255> $green
     */
    public function setGreen(int $green): static
    {
        $this->green = self::checkColor($green);

        return $this;
    }

    /**
     * Sets the red component.
     *
     * @param int $red the value to set. Must be between 0 and 255 inclusive.
     *
     * @psalm-param int<0, 255> $red
     */
    public function setRed(int $red): static
    {
        $this->red = self::checkColor($red);

        return $this;
    }

    /**
     * Sets the red, the green and the blue values.
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
    public function setRGB(int $red, int $green, int $blue): static
    {
        return $this->setRed($red)
            ->setGreen($green)
            ->setBlue($blue);
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

    /**
     * Checks if the given value is between 0 and 255 (inclusive).
     *
     * @param int $value the value to verify
     *
     * @return int the validate value
     *
     * @psalm-return int<0, 255>
     */
    private function checkColor(int $value): int
    {
        /** @psalm-var int<0, 255> $value */
        $value = $this->validateIntRange($value, self::MIN_VALUE, self::MAX_VALUE);

        return $value;
    }
}
