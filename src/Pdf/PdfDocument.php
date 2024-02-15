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

use App\Pdf\Traits\PdfBookmarkTrait;
use App\Traits\MathTrait;
use App\Utils\StringUtils;
use fpdf\PdfDocument as BaseDocument;
use fpdf\PdfFontName;
use fpdf\PdfLayout;
use fpdf\PdfMove;
use fpdf\PdfOrientation;
use fpdf\PdfPageSize;
use fpdf\PdfRectangleStyle;
use fpdf\PdfTextAlignment;
use fpdf\PdfUnit;
use fpdf\PdfZoom;

/**
 * PDF document with default header, footer, bookmarks and page index capabilities.
 *
 * @phpstan-import-type PageSizeType from BaseDocument
 */
class PdfDocument extends BaseDocument
{
    use MathTrait;
    use PdfBookmarkTrait;

    /**
     * The footer offset in mm.
     */
    final public const FOOTER_OFFSET = 15.0;

    /**
     * The default line height in mm.
     */
    final public const LINE_HEIGHT = 5.0;

    /**
     * The encoding source.
     */
    private const ENCODING_FROM = [
        'ASCII',
        'UTF-8',
        'CP1252',
        'ISO-8859-1',
    ];

    /**
     * The encoding target.
     */
    private const ENCODING_TO = 'CP1252';

    /**
     * The footer.
     */
    private readonly PdfFooter $footer;

    /**
     * The header.
     */
    private readonly PdfHeader $header;

    /**
     * @param PdfOrientation      $orientation the page orientation
     * @param PdfUnit             $unit        the user unit
     * @param PdfPageSize|float[] $size        the document size
     *
     * @phpstan-param PdfPageSize|PageSizeType $size
     */
    public function __construct(
        PdfOrientation $orientation = PdfOrientation::PORTRAIT,
        PdfUnit $unit = PdfUnit::MILLIMETER,
        PdfPageSize|array $size = PdfPageSize::A4
    ) {
        parent::__construct($orientation, $unit, $size);

        $this->header = new PdfHeader($this);
        $this->footer = new PdfFooter($this);

        $this->setDisplayMode(PdfZoom::FULL_PAGE, PdfLayout::SINGLE);
        $this->setAutoPageBreak(true, $this->bottomMargin - self::LINE_HEIGHT);
    }

    /**
     * Apply the given font.
     *
     * @return PdfFont the previous font
     */
    public function applyFont(PdfFont $font): PdfFont
    {
        $oldFont = $this->getCurrentFont();
        $font->apply($this);

        return $oldFont;
    }

    /**
     * @phpstan-param PdfBorder|string|bool $border
     */
    public function cell(
        float $width = 0.0,
        float $height = self::LINE_HEIGHT,
        string $text = '',
        PdfBorder|string|bool $border = false,
        PdfMove $move = PdfMove::RIGHT,
        PdfTextAlignment $align = PdfTextAlignment::LEFT,
        bool $fill = false,
        string|int $link = ''
    ): self {
        if ($border instanceof PdfBorder) {
            $border = $border->getCellStyle();
        }
        parent::cell($width, $height, $text, $border, $move, $align, $fill, $link);

        return $this;
    }

    public function footer(): void
    {
        $this->footer->output();
    }

    /**
     * Gets the current font.
     */
    public function getCurrentFont(): PdfFont
    {
        $name = PdfFontName::tryFromIgnoreCase($this->fontFamily) ?? PdfFontName::ARIAL;

        return new PdfFont($name, $this->fontSizeInPoint, $this->fontStyle);
    }

    /**
     * Gets the footer.
     */
    public function getFooter(): PdfFooter
    {
        return $this->footer;
    }

    /**
     * Gets the header.
     */
    public function getHeader(): PdfHeader
    {
        return $this->header;
    }

    public function header(): void
    {
        $this->header->output();
    }

    /**
     * @phpstan-param PdfBorder|string|bool $border
     */
    public function multiCell(
        float $width = 0.0,
        float $height = self::LINE_HEIGHT,
        string $text = '',
        PdfBorder|string|bool $border = false,
        PdfTextAlignment $align = PdfTextAlignment::JUSTIFIED,
        bool $fill = false
    ): self {
        if ($border instanceof PdfBorder) {
            $border = $border->getCellStyle();
        }
        parent::multiCell($width, $height, $text, $border, $align, $fill);

        return $this;
    }

    public function rect(
        float $x,
        float $y,
        float $width,
        float $height,
        PdfBorder|PdfRectangleStyle $style = PdfRectangleStyle::BORDER
    ): self {
        if ($style instanceof PdfBorder) {
            $style = $style->getRectangleStyle();
            if (!$style instanceof PdfRectangleStyle) {
                return $this;
            }
        }

        parent::rect($x, $y, $width, $height, $style);

        return $this;
    }

    /**
     * Outputs a rectangle.
     *
     * It can be drawn (border only), filled (with no border) or both.
     *
     * @param PdfRectangle                $bounds the rectangle to output
     * @param PdfBorder|PdfRectangleStyle $border the style of rendering
     */
    public function rectangle(
        PdfRectangle $bounds,
        PdfBorder|PdfRectangleStyle $border = PdfRectangleStyle::BORDER
    ): self {
        return $this->rect($bounds->x(), $bounds->y(), $bounds->width(), $bounds->height(), $border);
    }

    /**
     * Reset this current style to default.
     */
    public function resetStyle(): static
    {
        PdfStyle::default()->apply($this);

        return $this;
    }

    /**
     * Set the document description.
     *
     * @see PdfHeader::setDescription()
     */
    public function setDescription(?string $description): static
    {
        $this->header->setDescription($description);

        return $this;
    }

    protected function cleanText(string $str): string
    {
        $str = parent::cleanText($str);
        if (!StringUtils::isString($str)) {
            return $str;
        }

        try {
            return \mb_convert_encoding($str, self::ENCODING_TO, self::ENCODING_FROM);
        } catch (\Exception) {
            return $str;
        }
    }
}
