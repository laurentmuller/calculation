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
use fpdf\PdfDocument;

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

    /**
     * @return array{0: int<0, 255>, 1: int<0, 255>, 2: int<0, 255>}
     */
    public function asRGB(): array
    {
        return [
            $this->hexdec($this->value, 1),
            $this->hexdec($this->value, 3),
            $this->hexdec($this->value, 5),
        ];
    }

    public function getDrawColor(): PdfDrawColor
    {
        /** @phpstan-var PdfDrawColor */
        return PdfDrawColor::create($this->value);
    }

    public function getFillColor(): PdfFillColor
    {
        /** @phpstan-var PdfFillColor */
        return PdfFillColor::create($this->value);
    }

    public function getPhpOfficeColor(): string
    {
        return \substr($this->value, 1);
    }

    public function getTextColor(): PdfTextColor
    {
        /** @phpstan-var PdfTextColor */
        return PdfTextColor::create($this->value);
    }

    /**
     * @return int<0, 255>
     */
    private function hexdec(string $value, int $offset): int
    {
        /** @var int<0, 255> */
        return (int) \hexdec(\substr($value, $offset, 2));
    }
}
