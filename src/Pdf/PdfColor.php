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

namespace App\Pdf;

use App\Traits\MathTrait;
use App\Utils\Utils;

/**
 * Define a RGB color.
 *
 * @author Laurent Muller
 */
abstract class PdfColor implements IPdfDocumentUpdater
{
    use MathTrait;

    /**
     * The maximum value allowed for a component (inclusive).
     */
    protected const MAX_VALUE = 255;

    /**
     * The minimum value allowed a component (inclusive).
     */
    protected const MIN_VALUE = 0;

    /**
     * The blue component.
     *
     * @var int
     */
    protected $blue;

    /**
     * The green component.
     *
     * @var int
     */
    protected $green;

    /**
     * The red component.
     *
     * @var int
     */
    protected $red;

    /**
     * Constructor.
     *
     * All values must be bewtween 0 to 255 inclusive.
     *
     * @param int $red   the red component
     * @param int $green the green component
     * @param int $blue  the blue component
     */
    final public function __construct(int $red = 0, int $green = 0, int $blue = 0)
    {
        $this->setRGB($red, $green, $blue);
    }

    public function __toString(): string
    {
        $name = Utils::getShortName($this);

        return \sprintf('%s(%d, %d, %d)', $name, $this->red, $this->green, $this->blue);
    }

    /**
     * Gets the black color.
     *
     * Value is RGB(0, 0, 0).
     *
     * @return static the black color
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
     * @return self the blue color
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
     * @return self The border cell color
     */
    public static function cellBorder(): self
    {
        return new static(221, 221, 221);
    }

    /**
     * Creates a new instance.
     *
     * @param array|string $rgb an array containing the red, green and blue values or a hexadecimal string
     *
     * @return self|null the color or null if the RGB value can not be parsed
     *
     * @see PdfColor::parse()
     */
    public static function create($rgb): ?self
    {
        if (\is_string($rgb)) {
            $rgb = self::parse($rgb);
        }

        if (\is_array($rgb) && 3 === \count($rgb)) {
            return new static($rgb[0], $rgb[1], $rgb[2]);
        }

        return null;
    }

    /**
     * Gets the dark-green color.
     *
     * Value is RGB(0, 128, 0).
     *
     * @return self the dark-green color
     */
    public static function darkGreen(): self
    {
        return new static(self::MIN_VALUE, 128, self::MIN_VALUE);
    }

    /**
     * Gets the blue component.
     */
    public function getBlue(): int
    {
        return $this->blue;
    }

    /**
     * Gets the green component.
     */
    public function getGreen(): int
    {
        return $this->green;
    }

    /**
     * Gets the red component.
     */
    public function getRed(): int
    {
        return $this->red;
    }

    /**
     * Gets the red, the green and the blue values.
     *
     * @return int[]
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
     * @return self the green color
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
     * @return self the header color
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
     * @return self the link color
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
     * @param string $value a hexadecimal string
     *
     * @return array|bool the RGB array (<code>red, green, blue</code>) or <code>false</code> if the value can not be converted
     */
    public static function parse(?string $value)
    {
        // string?
        if (!Utils::isString($value)) {
            return false;
        }

        // gets a proper hex string
        $value = \preg_replace('/[^0-9A-Fa-f]/', '', $value);

        // parse depending of length
        switch (\strlen($value)) {
            case 6: // FF8040
                $color = \hexdec($value);
                $r = 0xFF & ($color >> 0x10);
                $g = 0xFF & ($color >> 0x8);
                $b = 0xFF & $color;

                return [$r, $g, $b];

            case 3: // FAC -> FFAACC
                $r = \hexdec(\str_repeat(\substr($value, 0, 1), 2));
                $g = \hexdec(\str_repeat(\substr($value, 1, 1), 2));
                $b = \hexdec(\str_repeat(\substr($value, 2, 1), 2));

                return [$r, $g, $b];

            default:
                return false;
        }
    }

    /**
     * Gets the red color.
     *
     * Value is RGB(255, 0, 0).
     *
     * @return self the red color
     */
    public static function red(): self
    {
        return new static(self::MAX_VALUE, self::MIN_VALUE, self::MIN_VALUE);
    }

    /**
     * Sets the blue component.
     *
     * @param int $blue the value to set. Must be bewtween 0 to 255 inclusive.
     *
     * @return self this instance
     */
    public function setBlue(int $blue): self
    {
        $this->blue = self::checkColor($blue);

        return $this;
    }

    /**
     * Sets the green component.
     *
     * @param int $green the value to set. Must be bewtween 0 to 255 inclusive.
     *
     * @return self this instance
     */
    public function setGreen(int $green): self
    {
        $this->green = self::checkColor($green);

        return $this;
    }

    /**
     * Sets the red component.
     *
     * @param int $red the value to set. Must be bewtween 0 to 255 inclusive.
     *
     * @return self this instance
     */
    public function setRed(int $red): self
    {
        $this->red = self::checkColor($red);

        return $this;
    }

    /**
     * Sets the red, the green and the blue values.
     * All values must be bewtween 0 to 255 inclusive.
     *
     * @param int $red   the red component
     * @param int $green the green component
     * @param int $blue  the blue component
     *
     * @return self this instance
     */
    public function setRGB(int $red, int $green, int $blue): self
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
     * @return self the white color
     */
    public static function white(): self
    {
        return new static(self::MAX_VALUE, self::MAX_VALUE, self::MAX_VALUE);
    }

    /**
     * Checks if the given value is between 0 to 255 (inclusive).
     *
     * @param int $value the value to verify
     *
     * @return int the validate value
     */
    private function checkColor(int $value): int
    {
        return $this->validateIntRange($value, self::MIN_VALUE, self::MAX_VALUE);
    }
}
