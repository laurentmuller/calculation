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

use App\Pdf\PdfBorder;
use fpdf\PdfRectangleStyle;

/**
 * Trait to perform a rotation around a given center.
 *
 * The rotation affects all elements, which are printed after the method call (except clickable areas). Rotation is not
 * kept from page to page. Each page begins with no rotation.
 *
 * @psalm-require-extends \App\Pdf\PdfDocument
 */
trait PdfRotationTrait
{
    private float $angle = 0.0;

    /**
     * Reset the rotation angle to 0.0.
     */
    public function endRotate(): void
    {
        if (!$this->isFloatZero($this->angle)) {
            $this->out('Q');
            $this->angle = 0.0;
        }
    }

    /**
     * Set the rotation angle.
     *
     * @param float      $angle the rotation angle or 0.0 to stop rotation
     * @param float|null $x     the abscissa position or null to use the current abscissa
     * @param float|null $y     the ordinate position or null to use the current ordinate
     */
    public function rotate(float $angle, ?float $x = null, ?float $y = null): void
    {
        $this->endRotate();
        $angle = \fmod($angle, 360.0);
        if ($this->isFloatZero($angle)) {
            return;
        }
        $this->angle = $angle;
        $x ??= $this->getX();
        $y ??= $this->getY();
        $angle *= \M_PI / 180.0;
        $cos = \cos($angle);
        $sin = \sin($angle);
        $cx = $x * $this->scaleFactor;
        $cy = ($this->height - $y) * $this->scaleFactor;
        $this->outf(
            'q %.5F %.5F %.5F %.5F %.2F %.2F cm 1 0 0 1 %.2F %.2F cm',
            $cos,
            $sin,
            -$sin,
            $cos,
            $cx,
            $cy,
            -$cx,
            -$cy
        );
    }

    /**
     * Rotate the given rectangle and end rotation.
     *
     * It can be drawn (border only), filled (with no border) or both. Do nothing if the angle is equal to 0.0.
     *
     * @param float                       $x     the abscissa of upper-left corner
     * @param float                       $y     the ordinate of upper-left corner
     * @param float                       $w     the width
     * @param float                       $h     the height
     * @param float                       $angle the rotation angle
     * @param PdfBorder|PdfRectangleStyle $style the style of rendering. Possible values are:
     *                                           <ul>
     *                                           <li>A PdfBorder instance.</li>
     *                                           <li>A PdfRectangleStyle enumeration.</li>
     *                                           </ul>
     */
    public function rotateRect(
        float $x,
        float $y,
        float $w,
        float $h,
        float $angle,
        PdfBorder|PdfRectangleStyle $style = PdfRectangleStyle::BORDER
    ): void {
        if ($this->isFloatZero($angle)) {
            return;
        }
        $this->rotate($angle, $x, $y);
        $this->rect($x, $y, $w, $h, $style);
        $this->endRotate();
    }

    /**
     * Rotate the given text and end rotation.
     *
     * Do nothing if the text is empty or if the angle is equal to 0.0.
     *
     * @param string     $txt   the text to rotate
     * @param float      $angle the rotation angle
     * @param float|null $x     the abscissa position or null to use the current abscissa
     * @param float|null $y     the ordinate position or null to use the current ordinate
     */
    public function rotateText(string $txt, float $angle, ?float $x = null, ?float $y = null): void
    {
        if ('' === $txt || $this->isFloatZero($angle)) {
            return;
        }
        $x ??= $this->getX();
        $y ??= $this->getY();
        $this->rotate($angle, $x, $y);
        $this->text($x, $y, $txt);
        $this->endRotate();
    }

    protected function endPage(): void
    {
        $this->endRotate();
        parent::endPage();
    }
}
