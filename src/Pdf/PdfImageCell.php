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

use App\Model\ImageSize;
use App\Traits\ImageSizeTrait;
use App\Traits\MathTrait;
use fpdf\Enums\PdfTextAlignment;
use fpdf\PdfException;

/**
 * A specialized cell containing an image and an optional text.
 */
class PdfImageCell extends AbstractPdfImageCell
{
    use ImageSizeTrait;
    use MathTrait;

    /** The original image size. */
    private ImageSize $originalSize;

    /** The image size. */
    private ImageSize $size;

    /**
     * @param string            $path      the image path
     * @param ?string           $text      the cell text
     * @param positive-int      $cols      the cell columns span
     * @param ?PdfStyle         $style     the cell style
     * @param ?PdfTextAlignment $alignment the cell alignment
     * @param string|int|null   $link      the optional cell link
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
        parent::__construct($text, $cols, $style, $alignment, $link);
        if (!\file_exists($path)) {
            throw PdfException::format("The image '%s' does not exist.", $path);
        }
        $this->originalSize = $this->getImageSize($path);
        $this->size = clone $this->originalSize;
    }

    /**
     * Gets the original image size.
     */
    public function getOriginalSize(): ImageSize
    {
        return $this->originalSize;
    }

    #[\Override]
    public function getPath(): string
    {
        return $this->path;
    }

    #[\Override]
    public function getSize(): ImageSize
    {
        return $this->size;
    }

    /**
     * Resize the image.
     *
     * @see ImageSize::resize()
     */
    public function resize(int $size): self
    {
        $this->size = $this->originalSize->resize($size);

        return $this;
    }
}
