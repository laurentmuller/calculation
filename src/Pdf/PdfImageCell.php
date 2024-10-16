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
use fpdf\Enums\PdfTextAlignment;
use fpdf\PdfException;

/**
 * A specialized cell containing an image and an optional text.
 */
class PdfImageCell extends AbstractPdfImageCell
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
     * @param string            $path      the image path
     * @param ?string           $text      the cell text
     * @param int               $cols      the cell columns span
     * @param ?PdfStyle         $style     the cell style
     * @param ?PdfTextAlignment $alignment the cell alignment
     * @param string|int|null   $link      the optional cell link.
     *                                     A URL or an identifier returned by the <code>addLink()</code> function.
     *
     * @psalm-param positive-int $cols
     *
     * @throws PdfException if the given image path does not exist
     */
    public function __construct(
        private readonly string $path,
        ?string $text = null,
        int $cols = 1,
        ?PdfStyle $style = null,
        ?PdfTextAlignment $alignment = null,
        string|int|null $link = null
    ) {
        if (!FileUtils::exists($path)) {
            throw PdfException::format("The image '%s' does not exist.", $path);
        }
        parent::__construct($text, $cols, $style, $alignment, $link);
        $size = $this->getImageSize($path);
        $this->width = $this->originalWidth = $size[0];
        $this->height = $this->originalHeight = $size[1];
    }

    public function getHeight(): int
    {
        return $this->height;
    }

    /**
     * Gets the original image width and height.
     *
     * @return int[] an array with two elements. Index 0 and 1 contain respectively the original width and the
     *               original height.
     *
     * @psalm-return array{0: int, 1: int}
     */
    public function getOriginalSize(): array
    {
        return [$this->originalWidth, $this->originalHeight];
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getWidth(): int
    {
        return $this->width;
    }

    /**
     * Resize the image.
     *
     * If both height and width arguments are equal to 0, the new width and height are equals to the original size.
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
