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

use fpdf\PdfDocument;

/**
 * Represents a vertical (y) chart axis.
 */
readonly class PdfYaxis
{
    /**
     * @param float $lowerBound  the lower axis bound
     * @param float $upperBound  the upper axis bound
     * @param float $tickSpacing the space between ticks
     */
    public function __construct(
        public float $lowerBound = 0.0,
        public float $upperBound = 100.0,
        public float $tickSpacing = 10.0
    ) {
    }

    /**
     * Gets the label's texts and widths.
     *
     * @param PdfDocument              $document  the document to get the label widths
     * @param ?callable(float): string $formatter the callback to format values, if none, values are cast to string
     *
     * @return non-empty-array<array{label: string, width: float}>
     */
    public function getLabels(PdfDocument $document, ?callable $formatter = null): array
    {
        /** @var non-empty-array<array{label: string, width: float}> $result */
        $result = [];

        $formatter ??= fn (float $value): string => (string) $value;
        foreach (\range($this->upperBound, $this->lowerBound, -$this->tickSpacing) as $value) {
            $text = $formatter($value);
            $result[] = [
                'label' => $text,
                'width' => $document->getStringWidth($text),
            ];
        }

        return $result;
    }

    /**
     * Create a new instance. All values are rounded to the nearest multiple of 1, 2, 5 or 10.
     *
     * @param float $lowerBound the desired lower axis bound
     * @param float $upperBound the desired axis bound
     * @param int   $ticks      the desired number of ticks
     *
     * @return self the new instance with rounded values
     */
    public static function instance(float $lowerBound = 0.0, float $upperBound = 100.0, int $ticks = 10): self
    {
        [$lowerBound, $upperBound] = self::roundBounds($lowerBound, $upperBound);
        $range = self::roundValue($upperBound - $lowerBound, false);
        $tickSpacing = self::roundValue($range / (float) (\max(2, $ticks) - 1), true);
        $lowerBound = \floor($lowerBound / $tickSpacing) * $tickSpacing;
        $upperBound = \ceil($upperBound / $tickSpacing) * $tickSpacing;

        return new self($lowerBound, $upperBound, $tickSpacing);
    }

    /**
     * @return array{0: float, 1: float}
     */
    private static function roundBounds(float $lowerBound, float $upperBound): array
    {
        // same?
        if ($lowerBound === $upperBound) {
            $lowerBound *= 0.99;
            $upperBound *= 1.01;
        }
        // lower greater than upper?
        if ($lowerBound > $upperBound) {
            [$lowerBound, $upperBound] = [$upperBound, $lowerBound];
        }
        // fix upper
        if ($upperBound > 0.0) {
            $upperBound += ($upperBound - $lowerBound) * 0.01;
        } elseif ($upperBound < 0.0) {
            $upperBound = \min($upperBound + ($upperBound - $lowerBound) * 0.01, 0.0);
        } else {
            $upperBound = 0.0;
        }
        // fix lower
        if ($lowerBound > 0.0) {
            $lowerBound = \max($lowerBound - ($upperBound - $lowerBound) * 0.01, 0.0);
        } elseif ($lowerBound < 0.0) {
            $lowerBound -= ($upperBound - $lowerBound) * 0.01;
        } else {
            $lowerBound = 0.0;
        }
        // both 0?
        if (0.0 === $lowerBound && 0.0 === $upperBound) {
            $upperBound = 1.0;
        }

        return [$lowerBound, $upperBound];
    }

    private static function roundValue(float $value, bool $roundDown): float
    {
        $exponent = \floor(\log10($value));
        $fraction = $value / 10.0 ** $exponent;
        $value = 10.0 ** $exponent;

        if ($roundDown) {
            return match (true) {
                $fraction < 1.5 => $value,
                $fraction < 3.0 => 2.0 * $value,
                $fraction < 7.0 => 5.0 * $value,
                default => 10.0 * $value,
            };
        }

        return match (true) {
            $fraction <= 1.0 => $value,
            $fraction <= 2.0 => 2.0 * $value,
            $fraction <= 5.0 => 5.0 * $value,
            default => 10.0 * $value,
        };
    }
}
