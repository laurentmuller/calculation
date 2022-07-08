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

use App\Interfaces\ImageExtensionInterface;
use App\Pdf\Enums\PdfTextAlignment;
use App\Traits\MathTrait;
use App\Util\FileUtils;

/**
 * Specialized cell containing an image.
 */
class PdfImageCell extends PdfCell implements ImageExtensionInterface
{
    use MathTrait;

    /**
     * The image height.
     */
    protected int $height;

    /**
     * The original image height.
     */
    protected int $originalHeight;

    /**
     * The original image width.
     */
    protected int $originalWidth;

    /**
     * The image width.
     */
    protected int $width;

    /**
     * Constructor.
     *
     * @param string            $path      the full image path
     * @param int               $cols      the cell columns span
     * @param ?PdfStyle         $style     the cell style
     * @param ?PdfTextAlignment $alignment the cell alignment
     * @param ?string           $link      the cell link
     */
    public function __construct(protected string $path, int $cols = 1, ?PdfStyle $style = null, ?PdfTextAlignment $alignment = null, ?string $link = null)
    {
        if (!FileUtils::exists($path)) {
            throw new \InvalidArgumentException("The image '$path' does not exist.");
        }

        parent::__construct(null, $cols, $style, $alignment, $link);

        /** @psalm-var array{0: int, 1: int} $size */
        $size = \getimagesize($path);
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
        $y = $bounds->y() + ($bounds->height() - $height) / 2;
        $x = match ($alignment) {
            PdfTextAlignment::RIGHT => $bounds->right() - $width,
            PdfTextAlignment::CENTER,
            PdfTextAlignment::JUSTIFIED => $bounds->x() + ($bounds->width() - $width) / 2,
            default => $bounds->x(),
        };

        // draw
        $parent->Image($this->path, $x, $y, $width, $height, $this->link ?? '');
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
     * @return array{0: int, 1: int} an array with 2 elements. Index 0 and 1 contains respectively the original width and the original height.
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
     * @return array{0: int, 1: int} an array with 2 elements. Index 0 and 1 contains respectively the width and the height.
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
            $width = $height * $ratio;
        } elseif ($width > 0) {
            $height = $width / $ratio;
        }

        $this->width = (int) \round($width);
        $this->height = (int) \round($height);

        return $this;
    }
}
