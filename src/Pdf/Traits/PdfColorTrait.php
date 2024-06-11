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

namespace App\Pdf\Traits;

use App\Pdf\Colors\PdfDrawColor;
use App\Pdf\Colors\PdfFillColor;
use App\Pdf\Colors\PdfTextColor;
use App\Pdf\Interfaces\PdfColorInterface;
use App\Pdf\PdfDocument;

/**
 * Trait for class implementing the color interface.
 *
 * @psalm-require-implements PdfColorInterface
 */
trait PdfColorTrait
{
    public function applyDrawColor(PdfDocument $doc): void
    {
        $this->getDrawColor()->apply($doc);
    }

    public function applyFillColor(PdfDocument $doc): void
    {
        $this->getFillColor()->apply($doc);
    }

    public function applyTextColor(PdfDocument $doc): void
    {
        $this->getTextColor()->apply($doc);
    }

    public function asInt(): int
    {
        return $this->hex2Dec($this->getPhpOfficeColor());
    }

    /**
     * @return array{0: int, 1: int, 2: int}
     *
     * @psalm-return array{0: int<0, 255>, 1: int<0, 255>, 2: int<0, 255>}
     */
    public function asRGB(): array
    {
        $hex = $this->getPhpOfficeColor();

        /** @psalm-var array{0: int<0, 255>, 1: int<0, 255>, 2: int<0, 255>} */
        return [
            $this->hex2Dec(\substr($hex, 0, 2)),
            $this->hex2Dec(\substr($hex, 2, 2)),
            $this->hex2Dec(\substr($hex, 4, 2)),
        ];
    }

    public function getDrawColor(): PdfDrawColor
    {
        $color = PdfDrawColor::create($this->value);
        if (!$color instanceof PdfDrawColor) {
            throw new \InvalidArgumentException('Unable to create draw color.');
        }

        return $color;
    }

    public function getFillColor(): PdfFillColor
    {
        $color = PdfFillColor::create($this->value);
        if (!$color instanceof PdfFillColor) {
            throw new \InvalidArgumentException('Unable to create fill color.');
        }

        return $color;
    }

    public function getPhpOfficeColor(): string
    {
        return \substr($this->value, 1);
    }

    public function getTextColor(): PdfTextColor
    {
        $color = PdfTextColor::create($this->value);
        if (!$color instanceof PdfTextColor) {
            throw new \InvalidArgumentException('Unable to create text color.');
        }

        return $color;
    }

    private function hex2Dec(string $hex): int
    {
        return (int) \hexdec($hex);
    }
}
