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

/**
 * Class to compute axis bounds and step spacing.
 */
class PdfBarScale
{
    private float $lowerBound = 0.0;
    private float $tickSpacing = 10.0;
    private float $upperBound = 100.0;

    public function __construct(float $lowerBound, float $upperBound, int $maxTicks = 10)
    {
        $this->calculate($lowerBound, $upperBound, $maxTicks);
    }

    public function getLowerBound(): float
    {
        return $this->lowerBound;
    }

    public function getTickSpacing(): float
    {
        return $this->tickSpacing;
    }

    public function getUpperBound(): float
    {
        return $this->upperBound;
    }

    private function calculate(float $lowerBound, float $upperBound, int $maxTicks): void
    {
        [$lowerBound, $upperBound] = $this->fixBounds($lowerBound, $upperBound);

        $maxTicks = \max(2, $maxTicks);
        $range = $this->calculateValue($upperBound - $lowerBound, false);
        $this->tickSpacing = $this->calculateValue($range / (float) ($maxTicks - 1), true);
        $this->lowerBound = \floor($lowerBound / $this->tickSpacing) * $this->tickSpacing;
        $this->upperBound = \ceil($upperBound / $this->tickSpacing) * $this->tickSpacing;
    }

    private function calculateValue(float $range, bool $round): float
    {
        $exponent = \floor(\log10($range));
        $fraction = $range / 10.0 ** $exponent;
        if ($round) {
            $factor = match (true) {
                $fraction < 1.5 => 1.0,
                $fraction < 3.0 => 2.0,
                $fraction < 7.0 => 5.0,
                default => 10.0,
            };
        } else {
            $factor = match (true) {
                $fraction <= 1.0 => 1.0,
                $fraction <= 2.0 => 2.0,
                $fraction <= 5.0 => 5.0,
                default => 10.0,
            };
        }

        return $factor * 10.0 ** $exponent;
    }

    /**
     * @psalm-return array{0: float, 1: float}
     */
    private function fixBounds(float $lowerBound, float $upperBound): array
    {
        // same?
        if ($lowerBound === $upperBound) {
            $lowerBound *= 0.99;
            $upperBound *= 1.01;
        }
        // lower greater than upper?
        if ($lowerBound > $upperBound) {
            $temp = $upperBound;
            $upperBound = $lowerBound;
            $lowerBound = $temp;
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
}
