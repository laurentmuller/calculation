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
use fpdf\PdfOrientation;
use fpdf\PdfPageSize;
use fpdf\PdfSize;
use fpdf\PdfUnit;
use fpdf\PdfZoom;

/**
 * PDF document with default header, footer, bookmarks and page index capabilities.
 */
class PdfDocument extends BaseDocument
{
    use MathTrait;
    use PdfBookmarkTrait;

    /**
     * The footer offset in millimeters.
     */
    final public const FOOTER_OFFSET = 15.0;

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
     * Create a new instance.
     *
     * @param PdfOrientation      $orientation the page orientation
     * @param PdfUnit             $unit        the document unit to use
     * @param PdfPageSize|PdfSize $size        the page size
     */
    public function __construct(
        PdfOrientation $orientation = PdfOrientation::PORTRAIT,
        PdfUnit $unit = PdfUnit::MILLIMETER,
        PdfPageSize|PdfSize $size = PdfPageSize::A4
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

    public function footer(): void
    {
        $this->footer->output();
    }

    /**
     * Gets the current font.
     */
    public function getCurrentFont(): PdfFont
    {
        $name = PdfFontName::tryFromFamily($this->fontFamily) ?? PdfFontName::ARIAL;

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
