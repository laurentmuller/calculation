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

use App\Traits\ImageSizeTrait;
use App\Traits\MathTrait;
use App\Utils\FileUtils;
use fpdf\PdfRectangle;
use fpdf\PdfTextAlignment;

/**
 * Specialized cell containing an image.
 */
class PdfImageCell extends PdfCell
{
    use ImageSizeTrait;
    use MathTrait;

    /**
     * The image height.
     */
    private int $height;

    /**
     * The original image height.
     */
    private int $originalHeight;

    /**
     * The original image width.
     */
    private int $originalWidth;

    /**
     * The image width.
     */
    private int $width;

    /**
     * @param string            $path      the full image path
     * @param int               $cols      the cell columns span
     * @param ?PdfStyle         $style     the cell style
     * @param ?PdfTextAlignment $alignment the cell alignment
     * @param string|int        $link      the cell link. A URL or identifier returned by AddLink().
     *
     * @psalm-param positive-int $cols
     */
    public function __construct(private readonly string $path, int $cols = 1, ?PdfStyle $style = null, ?PdfTextAlignment $alignment = null, string|int $link = '')
    {
        if (!FileUtils::exists($path)) {
            throw new \InvalidArgumentException("The image '$path' does not exist.");
        }

        parent::__construct(cols: $cols, style: $style, alignment: $alignment, link: $link);

        $size = $this->getImageSize($path);
        $this->width = $this->originalWidth = $size[0];
        $this->height = $this->originalHeight = $size[1];
    }

    /**
     * Draw this image.
     *
     * @param PdfDocument      $parent    the parent document
     * @param PdfRectangle     $bounds    the target bounds
     * @param PdfTextAlignment $alignment the horizontal alignment
     */
    public function drawImage(PdfDocument $parent, PdfRectangle $bounds, PdfTextAlignment $alignment): void
    {
        // convert size
        $width = $parent->pixels2UserUnit($this->width);
        $height = $parent->pixels2UserUnit($this->height);

        // get default position
        $y = $bounds->y + ($bounds->height - $height) / 2.0;
        $x = match ($alignment) {
            PdfTextAlignment::RIGHT => $bounds->right() - $width,
            PdfTextAlignment::CENTER,
            PdfTextAlignment::JUSTIFIED => $bounds->x + ($bounds->width - $width) / 2.0,
            default => $bounds->x,
        };

        // draw
        $parent->image(
            file: $this->path,
            x: $x,
            y: $y,
            width: $width,
            height: $height,
            link: $this->getLink()
        );
    }

    /**
     * Gets the current image height.
     */
    public function getHeight(): int
    {
        return $this->height;
    }

    /**
     * Gets the original image width and height.
     *
     * @return int[] an array with 2 elements. Index 0 and 1 contains respectively the original width and the original height.
     *
     * @psalm-return array{0: int, 1: int}
     */
    public function getOriginalSize(): array
    {
        return [$this->originalWidth, $this->originalHeight];
    }

    /**
     * Gets the image path.
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * Gets the current image size.
     *
     * @return int[] an array with 2 elements. Index 0 and 1 contains respectively the width and the height.
     *
     * @psalm-return array{0: int, 1: int}
     */
    public function getSize(): array
    {
        return [$this->width, $this->height];
    }

    /**
     * Gets the current image width.
     */
    public function getWidth(): int
    {
        return $this->width;
    }

    /**
     * Resize the image.
     *
     * If both height and width arguments are equal to 0, the new width and height are equals to original size.
     *
     * @param int $height the new height or 0 to take the original width as reference
     * @param int $width  the new width or 0 to take the original height as reference
     */
    public function resize(int $height = 0, int $width = 0): self
    {
        if (0 === $height && 0 === $width) {
            $this->height = $this->originalHeight;
            $this->width = $this->originalWidth;

            return $this;
        }

        $ratio = $this->safeDivide($this->originalWidth, $this->originalHeight, 1);
        if ($height > 0) {
            $width = (int) \round((float) $height * $ratio);
        } elseif ($width > 0) {
            $height = (int) \round((float) $width / $ratio);
        }

        $this->width = $width;
        $this->height = $height;

        return $this;
    }
}
