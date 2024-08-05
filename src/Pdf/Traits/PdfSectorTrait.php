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

use fpdf\PdfRectangleStyle;

/**
 * Trait to draw sector.
 *
 * @psalm-require-extends \fpdf\PdfDocument
 */
trait PdfSectorTrait
{
    private const HALF_PI = \M_PI / 2.0;
    private const TWO_PI = \M_PI * 2.0;

    /**
     * Draw a sector.
     *
     * Do nothing if the radius is not positive or if the start angle is equal to the end angle.
     *
     * @param float             $centerX    the abscissa of the center
     * @param float             $centerY    the ordinate of the center
     * @param float             $radius     the radius
     * @param float             $startAngle the starting angle in degrees
     * @param float             $endAngle   the ending angle in degrees
     * @param PdfRectangleStyle $style      the style of rendering
     * @param bool              $clockwise  indicates whether to go clockwise (true) or counter-clockwise (false)
     * @param float             $origin     the origin of angles (0=right, 90=top, 180=left, 270=for bottom)
     */
    public function sector(
        float $centerX,
        float $centerY,
        float $radius,
        float $startAngle,
        float $endAngle,
        PdfRectangleStyle $style = PdfRectangleStyle::BOTH,
        bool $clockwise = true,
        float $origin = 90
    ): void {
        // validate
        if ($radius <= 0 || $startAngle === $endAngle) {
            return;
        }

        // compute values
        $height = $this->height;
        $scaleFactor = $this->scaleFactor;
        [$startAngle, $endAngle, $deltaAngle] = $this->sectorComputeAngles($startAngle, $endAngle, $clockwise, $origin);
        $arc = $this->sectorComputeArc($deltaAngle, $radius);

        // put center
        $this->outf('%.2F %.2F m', $centerX * $scaleFactor, ($height - $centerY) * $scaleFactor);

        // put the first point
        $x = ($centerX + $radius * \cos($startAngle)) * $scaleFactor;
        $y = ($height - ($centerY - $radius * \sin($startAngle))) * $scaleFactor;
        $this->outf('%.2F %.2F l', $x, $y);

        // draw arc
        if ($deltaAngle >= self::HALF_PI) {
            $endAngle = $startAngle + $deltaAngle / 4.0;
            $arc = 4.0 / 3.0 * (1.0 - \cos($deltaAngle / 8.0)) / \sin($deltaAngle / 8.0) * $radius;
            $this->sectorOutputArc($centerX, $centerY, $radius, $startAngle, $endAngle, $arc);

            $startAngle = $endAngle;
            $endAngle = $startAngle + $deltaAngle / 4.0;
            $this->sectorOutputArc($centerX, $centerY, $radius, $startAngle, $endAngle, $arc);

            $startAngle = $endAngle;
            $endAngle = $startAngle + $deltaAngle / 4.0;
            $this->sectorOutputArc($centerX, $centerY, $radius, $startAngle, $endAngle, $arc);

            $startAngle = $endAngle;
            $endAngle = $startAngle + $deltaAngle / 4.0;
        }
        $this->sectorOutputArc($centerX, $centerY, $radius, $startAngle, $endAngle, $arc);

        // terminate drawing
        $this->sectorTerminate($style);
    }

    /**
     * @return float[]
     */
    private function sectorComputeAngles(float $startAngle, float $endAngle, bool $clockwise, float $origin): array
    {
        $angle = $startAngle - $endAngle;
        if ($clockwise) {
            $deltaAngle = $endAngle;
            $endAngle = $origin - $startAngle;
            $startAngle = $origin - $deltaAngle;
        } else {
            $endAngle += $origin;
            $startAngle += $origin;
        }

        $startAngle = $this->sectorValidate($startAngle);
        $endAngle = $this->sectorValidate($endAngle);
        if ($startAngle > $endAngle) {
            $endAngle += 360.0;
        }

        $endAngle = $endAngle / 360.0 * self::TWO_PI;
        $startAngle = $startAngle / 360.0 * self::TWO_PI;
        $deltaAngle = $endAngle - $startAngle;
        if (0.0 === $deltaAngle && 0.0 !== $angle) {
            $deltaAngle = self::TWO_PI;
        }

        return [$startAngle, $endAngle, $deltaAngle];
    }

    private function sectorComputeArc(float $deltaAngle, float $radius): float
    {
        if (0.0 !== \sin($deltaAngle / 2.0)) {
            return 4.0 / 3.0 * (1.0 - \cos($deltaAngle / 2.0)) / \sin($deltaAngle / 2.0) * $radius;
        }

        return 0.0;
    }

    /**
     * Compute and output arc.
     *
     * @psalm-suppress InvalidOperand
     */
    private function sectorOutputArc(
        float $centerX,
        float $centerY,
        float $radius,
        float $startAngle,
        float $endAngle,
        float $arc
    ): void {
        // compute
        $x1 = $centerX + $radius * \cos($startAngle) + $arc * \cos(self::HALF_PI + $startAngle);
        $y1 = $centerY - $radius * \sin($startAngle) - $arc * \sin(self::HALF_PI + $startAngle);
        $x2 = $centerX + $radius * \cos($endAngle) + $arc * \cos($endAngle - self::HALF_PI);
        $y2 = $centerY - $radius * \sin($endAngle) - $arc * \sin($endAngle - self::HALF_PI);
        $x3 = $centerX + $radius * \cos($endAngle);
        $y3 = $centerY - $radius * \sin($endAngle);

        // output
        $height = $this->height;
        $scaleFactor = $this->scaleFactor;
        $this->outf(
            '%.2F %.2F %.2F %.2F %.2F %.2F c',
            $x1 * $scaleFactor,
            ($height - $y1) * $scaleFactor,
            $x2 * $scaleFactor,
            ($height - $y2) * $scaleFactor,
            $x3 * $scaleFactor,
            ($height - $y3) * $scaleFactor
        );
    }

    private function sectorTerminate(PdfRectangleStyle $style): void
    {
        $this->out($style->value);
    }

    private function sectorValidate(float $angle): float
    {
        $angle = \fmod($angle, 360.0);

        return ($angle < 0.0) ? $angle + 360.0 : $angle;
    }
}
