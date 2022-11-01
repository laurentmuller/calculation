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

use App\Pdf\Enums\PdfDocumentLayout;
use App\Pdf\Enums\PdfDocumentOrientation;
use App\Pdf\Enums\PdfDocumentOutput;
use App\Pdf\Enums\PdfDocumentSize;
use App\Pdf\Enums\PdfDocumentUnit;
use App\Pdf\Enums\PdfDocumentZoom;
use App\Pdf\Enums\PdfImageType;
use App\Pdf\Enums\PdfMove;
use App\Pdf\Enums\PdfRectangleStyle;
use App\Pdf\Enums\PdfTextAlignment;
use App\Traits\MathTrait;

/**
 * PDF document with default header and footer.
 *
 * @property int     $page           The current page number.
 * @property float[] $DefPageSize    The default page size.
 * @property float[] $CurPageSize    The current page size.
 * @property float   $lMargin        The left margin.
 * @property float   $tMargin        The top margin.
 * @property float   $rMargin        The right margin.
 * @property float   $bMargin        The bottom margin.
 * @property float   $cMargin        The cell margin.
 * @property float   $k              The scale factor (number of points in user unit).
 * @property float   $x              The current X position in user unit.
 * @property float   $y              The current Y position in user unit.
 * @property float   $w              The width of current page in user unit.
 * @property float   $h              The height of current page in user unit.
 * @property string  $FontFamily     The current font family.
 * @property string  $FontStyle      The current font style.
 * @property float   $FontSizePt     The current font size in points.
 * @property float   $FontSize       The current font size in user unit.
 * @property array   $CurrentFont    The current font information.
 * @property string  $DefOrientation The default orientation.
 * @property string  $CurOrientation The current orientation.
 * @property int     $CurRotation    The current page rotation in degrees.
 * @property float   $lasth          The height of last printed cell.
 *
 * @method float GetX()          The current X position in user unit.
 * @method float GetY()          The current Y position in user unit.
 * @method int   PageNo()        The current page number.
 * @method float GetPageWidth()  The width of current page in user unit.
 * @method float GetPageHeight() The height of current page in user unit.
 */
class PdfDocument extends \FPDF
{
    use MathTrait;

    /**
     * The footer offset.
     */
    final public const FOOTER_OFFSET = -15;

    /**
     * The default line height.
     */
    final public const LINE_HEIGHT = 5;
    /**
     * The new line separator.
     */
    private const NEW_LINE = "\n";

    /**
     * The footer.
     */
    protected PdfFooter $footer;

    /**
     * The header.
     */
    protected PdfHeader $header;

    /**
     * The title.
     */
    protected ?string $title = null;

    /**
     * Constructor.
     *
     * @param PdfDocumentOrientation|string $orientation the page orientation
     * @param PdfDocumentUnit|string        $unit        the measure unit
     * @param PdfDocumentSize|int[]         $size        the document size or the width and height of the document
     */
    public function __construct(PdfDocumentOrientation|string $orientation = PdfDocumentOrientation::PORTRAIT, PdfDocumentUnit|string $unit = PdfDocumentUnit::MILLIMETER, PdfDocumentSize|array $size = PdfDocumentSize::A4)
    {
        if ($orientation instanceof PdfDocumentOrientation) {
            $orientation = $orientation->value;
        }
        if ($unit instanceof PdfDocumentUnit) {
            $unit = $unit->value;
        }
        if ($size instanceof PdfDocumentSize) {
            $size = $size->value;
        }
        parent::__construct($orientation, $unit, $size);

        $this->header = new PdfHeader($this);
        $this->footer = new PdfFooter($this);

        $this->AliasNbPages();
        $this->SetAutoPageBreak(true, $this->bMargin - self::LINE_HEIGHT);
        $this->SetDisplayMode();
    }

    /**
     * Adds a new page to the document.
     *
     * @param PdfDocumentOrientation|string $orientation the page orientation or an empty string ("") to use the current orientation
     * @param PdfDocumentSize|int[]|string  $size        the page size or an empty string ("") to use the current size
     * @param int                           $rotation    the angle by which to rotate the page or 0 to use the current orientation.
     *                                                   It must be a multiple of 90; positive values mean clockwise rotation.
     */
    public function AddPage($orientation = '', $size = '', $rotation = 0): void
    {
        if ($orientation instanceof PdfDocumentOrientation) {
            $orientation = $orientation->value;
        }
        if ($size instanceof PdfDocumentSize) {
            $size = $size->value;
        }
        parent::AddPage($orientation, $size, $rotation);
    }

    /**
     * Apply the given font.
     *
     * @param PdfFont $font the font to apply
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
     * Prints a cell (rectangular area) with optional borders, background color and character string.
     *
     * @param float                   $w      the cell width. If 0, the cell extends up to the right margin.
     * @param float                   $h      the cell height
     * @param string                  $txt    the cell text
     * @param PdfBorder|int|string    $border indicates if borders must be drawn around the cell. The value can be either:
     *                                        <ul>
     *                                        <li>A Pdf border object.</li>
     *                                        <li>A number:
     *                                        <ul>
     *                                        <li><b>0</b> : No border (default value).</li>
     *                                        <li><b>1</b> : Frame.</li>
     *                                        </ul>
     *                                        </li>
     *                                        <li>A string containing some or all of the following characters (in any order):
     *                                        <ul>
     *                                        <li>'<b>L</b>' : Left.</li>
     *                                        <li>'<b>T</b>' : Top.</li>
     *                                        <li>'<b>R</b>' : Right.</li>
     *                                        <li>'<b>B</b>' : Bottom.</li>
     *                                        </ul>
     *                                        </li>
     *                                        </ul>
     * @param PdfMove|int             $ln     indicates where the current position should go after the call.
     *                                        Putting 1 is equivalent to putting <code>0</code> and calling <code>Ln()</code> just after. The default value is <code>0</code>.
     *                                        Possible values are:
     *                                        <ul>
     *                                        <li><b>0</b>: To the right</li>
     *                                        <li><b>1</b>: To the beginning of the next line</li>
     *                                        <li><b>2</b>: Below</li>
     *                                        </ul>
     * @param string|PdfTextAlignment $align  the text alignment. The value can be:
     *                                        <ul>
     *                                        <li>'<b>L</b>' or en empty string (''): left align (default value).</li>
     *                                        <li>'<b>C</b>' : center.</li>
     *                                        <li>'<b>R</b>' : right align.</li>
     *                                        </ul>
     * @param bool                    $fill   indicates if the cell background must be painted (true) or transparent (false). Default value is false.
     * @param string|int              $link   a URL or an identifier returned by AddLink()
     */
    public function Cell($w, $h = 0.0, $txt = '', $border = 0, $ln = PdfMove::RIGHT, $align = '', $fill = false, $link = ''): void
    {
        if ($ln instanceof PdfMove) {
            $ln = $ln->value;
        }
        if ($border instanceof PdfBorder) {
            $border = $border->getValue();
        }
        if (PdfTextAlignment::JUSTIFIED === $align) {
            $align = PdfTextAlignment::LEFT->value;
        } elseif ($align instanceof PdfTextAlignment) {
            $align = $align->value;
        }
        parent::Cell($w, $h, $this->cleanText($txt), $border, $ln, $align, $fill, $link);
    }

    public function Footer(): void
    {
        $this->footer->output();
    }

    /**
     * Gets the cell margin. The default value is 1 mm.
     */
    public function getCellMargin(): float
    {
        return $this->cMargin;
    }

    /**
     * Gets the current font.
     */
    public function getCurrentFont(): PdfFont
    {
        return new PdfFont($this->FontFamily, $this->FontSizePt, $this->FontStyle);
    }

    /**
     * Gets the current page.
     */
    public function getCurrentPage(): int
    {
        return $this->page;
    }

    /**
     * Gets the default page size.
     *
     * @return float[] the current page size
     */
    public function getDefaultPageSize(): array
    {
        return $this->DefPageSize;
    }

    /**
     * Gets the current font size in user unit.
     */
    public function getFontSize(): float
    {
        return $this->FontSize;
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

    /**
     * Gets the height of last printed cell.
     *
     * @return float the height or 0 if no printed cell
     */
    public function getLastHeight(): float
    {
        return $this->lasth;
    }

    /**
     * Gets the left margin.
     */
    public function getLeftMargin(): float
    {
        return $this->lMargin;
    }

    /**
     * Gets the number of lines to use for the given text and width.
     * Computes the number of lines a MultiCell of the given width will take.
     *
     * @param ?string $text  the text to compute
     * @param float   $width the desired width. If 0, the width extends up to the right margin.
     *
     * @return int the number of lines
     */
    public function getLinesCount(?string $text, float $width): int
    {
        // check width
        if ($width <= 0) {
            $width = $this->w - $this->rMargin - $this->x;
        }
        $maxWidth = ($width - 2 * $this->cMargin) * 1000 / $this->FontSize;

        // clean text
        $text = \str_replace("\r", '', (string) $text);
        $lenText = \strlen($text);
        while ($lenText > 0 && self::NEW_LINE === $text[$lenText - 1]) {
            --$lenText;
        }

        $sep = -1;
        $index = 0;
        $lastIndex = 0;
        $currentWidth = 0;
        $linesCount = 1;

        /** @psalm-var array<string, float> $cw */
        $cw = &$this->CurrentFont['cw'];

        // run over text
        while ($index < $lenText) {
            $ch = $text[$index];

            // new line?
            if (self::NEW_LINE === $ch) {
                ++$index;
                $sep = -1;
                $lastIndex = $index;
                $currentWidth = 0;
                ++$linesCount;
                continue;
            }

            // separator?
            if (' ' === $ch) {
                $sep = $index;
            }

            // compute width
            $currentWidth += $cw[$ch];

            // exceed allowed width?
            if ($currentWidth > $maxWidth) {
                if (-1 === $sep) {
                    if ($index === $lastIndex) {
                        ++$index;
                    }
                } else {
                    $index = $sep + 1;
                }
                $sep = -1;
                $lastIndex = $index;
                $currentWidth = 0;
                ++$linesCount;
            } else {
                ++$index;
            }
        }

        return $linesCount;
    }

    /**
     * Gets printable height.
     *
     * @return float the printable height
     */
    public function getPrintableHeight(): float
    {
        return $this->h - $this->tMargin - $this->bMargin + self::FOOTER_OFFSET;
    }

    /**
     * Gets printable width.
     */
    public function getPrintableWidth(): float
    {
        return $this->w - $this->lMargin - $this->rMargin;
    }

    /**
     * Gets the right margin.
     */
    public function getRightMargin(): float
    {
        return $this->rMargin;
    }

    /**
     * Gets the scale factor (number of points in user unit).
     */
    public function getScaleFactor(): float
    {
        return $this->k;
    }

    /**
     * Returns the length of a string in user unit. A font must be selected.
     *
     * @param string $s the string whose length is to be computed
     *
     * @return float the string width
     */
    public function GetStringWidth($s): float
    {
        $s = $this->cleanText($s);

        return (float) parent::GetStringWidth($s);
    }

    /**
     * Gets the document title.
     */
    public function getTitle(): ?string
    {
        return $this->title;
    }

    /**
     * Gets the current X and Y position.
     *
     * @return array{0: float, 1: float} the X and Y position
     *
     * @see PdfDocument::GetX()
     * @see PdfDocument::GetY()
     */
    public function GetXY(): array
    {
        return [$this->GetX(), $this->GetY()];
    }

    public function Header(): void
    {
        $this->header->output();
    }

    /**
     * Draws a horizontal line with current draw color and line width.
     *
     * @param float $beforeSpace the verticale space before the line
     * @param float $afterSpace  the verticale space after the line
     */
    public function horizontalLine(float $beforeSpace = 1.0, float $afterSpace = 1.0): self
    {
        $x = $this->x;
        $y = $this->y + $beforeSpace;
        $w = $this->getPrintableWidth();

        $this->Line($x, $y, $x + $w, $y);

        $this->x = $x;
        $this->y = $y + $afterSpace;

        return $this;
    }

    /**
     * Puts an image.
     *
     * @param string              $file the path or the URL of the image
     * @param ?float              $x    the abscissa of the upper-left corner. If not specified or equal to null, the current abscissa is used.
     * @param ?float              $y    the ordinate of the upper-left corner. If not specified or equal to null, the current ordinate is used;
     *                                  moreover, a page break is triggered first if necessary (in case automatic page breaking is enabled) and,
     *                                  after the call, the current ordinate is moved to the bottom of the image.
     * @param float               $w    the width of the image in the page
     * @param float               $h    the height of the image in the page
     * @param PdfImageType|string $type the image format. Possible values are (case insensitive): JPG, JPEG, PNG and GIF.
     *                                  If not specified, the type is inferred from the file extension.
     * @param string|int          $link the URL or an identifier returned by AddLink()
     */
    public function Image($file, $x = null, $y = null, $w = 0, $h = 0, $type = '', $link = ''): void
    {
        if ($type instanceof PdfImageType) {
            $type = $type->value;
        }
        parent::Image($file, $x, $y, $w, $h, $type, $link);
    }

    /**
     * Returns if the given height would not cause an overflow (new page).
     *
     * @param float $height the desired height
     *
     * @return bool true if printable within the current page; false if a new page is
     *              needed
     */
    public function isPrintable(float $height): bool
    {
        return ($this->y + $height) <= $this->PageBreakTrigger;
    }

    /**
     * This method allows printing text with line breaks. They can be automatic (as soon as the text reaches the right border of the cell) or explicit (via the \n character). As many cells as necessary are output, one below the other. Text can be aligned, centered or justified. The cell block can be framed and the background painted.
     *
     * @param float                   $w      the cell width. If 0, the cell extends up to the right margin.
     * @param float                   $h      the cell height
     * @param string                  $txt    the cell text
     * @param PdfBorder|int|string    $border indicates if borders must be drawn around the cell. The value can be either:
     *                                        <ul>
     *                                        <li>A Pdf border object.</li>
     *                                        <li>A number:
     *                                        <ul>
     *                                        <li><b>0</b> : No border (default value).</li>
     *                                        <li><b>1</b> : Frame.</li>
     *                                        </ul>
     *                                        </li>
     *                                        <li>A string containing some or all of the following characters (in any order):
     *                                        <ul>
     *                                        <li>'<b>L</b>' : Left.</li>
     *                                        <li>'<b>T</b>' : Top.</li>
     *                                        <li>'<b>R</b>' : Right.</li>
     *                                        <li>'<b>B</b>' : Bottom.</li>
     *                                        </ul>
     *                                        </li>
     *                                        </ul>
     * @param PdfTextAlignment|string $align  the text alignment. The value can be:
     *                                        <ul>
     *                                        <li>'<b>L</b>' or an empty string: left align.</li>
     *                                        <li>'<b>C</b>' : center.</li>
     *                                        <li>'<b>R</b>' : right align.</li>
     *                                        <li>'<b>J</b>' : justification (default value).</li>
     *                                        </ul>
     * @param bool                    $fill   indicates if the cell background must be painted (true) or transparent (false). Default value is false.
     */
    public function MultiCell($w, $h, $txt, $border = 0, $align = 'J', $fill = false): void
    {
        if ($border instanceof PdfBorder) {
            $border = $border->getValue();
        }
        if ($align instanceof PdfTextAlignment) {
            $align = $align->value;
        }
        parent::MultiCell($w, $h, $this->cleanText($txt), $border, $align, $fill);
    }

    /**
     * Send the document to a given destination.
     *
     * @param PdfDocumentOutput|string $dest   the destination where to send the document or an empty
     *                                         string ("") to send the file inline to the browser
     * @param string                   $name   the name of the file. It is ignored in case of string destination.
     * @param bool                     $isUTF8 indicates if name is encoded in ISO-8859-1 (false) or UTF-8 (true)
     *
     * @return string the content if the output is string
     */
    public function Output($dest = '', $name = '', $isUTF8 = false): string
    {
        if ($dest instanceof PdfDocumentOutput) {
            $dest = $dest->value;
        }

        return (string) parent::Output($dest, $name, $isUTF8);
    }

    /**
     * Converts the given pixels to millimeters using 72 dot per each (DPI).
     *
     * @param float $pixels the pixels to convert
     *
     * @return float the converted value as millimeters
     */
    public function pixels2mm(float $pixels): float
    {
        return $pixels * 25.4 / 72.0;
    }

    /**
     * Converts the given pixels to user unit using 72 dot per each (DPI).
     *
     * @param float $pixels the pixels to convert
     *
     * @return float the converted value as user unit
     */
    public function pixels2UserUnit(float $pixels): float
    {
        return $pixels * 72.0 / 96.0 / $this->k;
    }

    /**
     * Outputs a rectangle. It can be drawn (border only), filled (with no border) or both.
     *
     * @param float                              $x     the abscissa of upper-left corner
     * @param float                              $y     the ordinate of upper-left corner
     * @param float                              $w     the width
     * @param float                              $h     the height
     * @param PdfBorder|PdfRectangleStyle|string $style the style of rendering. Possible values are:
     *                                                  <ul>
     *                                                  <li>'<b>D</b>' or empty string: draw. This is the default value.</li>
     *                                                  <li>'<b>F</b>' : fill.</li>
     *                                                  <li>'<b>DF</b>' : draw and fill.</li>
     *                                                  </ul>
     */
    public function Rect($x, $y, $w, $h, $style = ''): void
    {
        if ($style instanceof PdfBorder) {
            if (!$style->isRectangleStyle()) {
                return;
            }
            $style = $style->getRectangleStyle();
        }

        if ($style instanceof PdfRectangleStyle) {
            if (!$style->isApplicable()) {
                return;
            }
            $style = $style->value;
        }

        parent::Rect($x, $y, $w, $h, $style);
    }

    /**
     * Outputs a rectangle. It can be drawn (border only), filled (with no border) or both.
     *
     * @param PdfRectangle                $bounds the rectangle to output
     * @param PdfBorder|PdfRectangleStyle $border the style of rendering
     *
     * @see PdfBorder::isRectangleStyle()
     * @see PdfBorder::getRectangleStyle()
     * @see PdfDocument::Rect()
     */
    public function rectangle(PdfRectangle $bounds, PdfBorder|PdfRectangleStyle $border): self
    {
        $this->Rect($bounds->x(), $bounds->y(), $bounds->width(), $bounds->height(), $border);

        return $this;
    }

    /**
     * Set this current style to default.
     */
    public function resetStyle(): self
    {
        // reset
        PdfStyle::getDefaultStyle()->apply($this);

        return $this;
    }

    /**
     * Sets the cell margins. The minimum value allowed is 0.
     *
     * @param float $margin the margins to set
     *
     * @return float the old margins
     */
    public function setCellMargin(float $margin): float
    {
        $oldMargins = $this->cMargin;
        $this->cMargin = \max(0, $margin);

        return $oldMargins;
    }

    /**
     * @param PdfDocumentZoom|string   $zoom
     * @param PdfDocumentLayout|string $layout
     */
    public function SetDisplayMode($zoom = PdfDocumentZoom::FULL_PAGE, $layout = PdfDocumentLayout::SINGLE): void
    {
        if ($zoom instanceof PdfDocumentZoom) {
            $zoom = $zoom->value;
        }
        if ($layout instanceof PdfDocumentLayout) {
            $layout = $layout->value;
        }
        parent::SetDisplayMode($zoom, $layout);
    }

    /**
     * Sets the footer.
     */
    public function setFooter(PdfFooter $footer): self
    {
        $this->footer = $footer;

        return $this;
    }

    /**
     * Sets the header.
     */
    public function setHeader(PdfHeader $header): self
    {
        $this->header = $header;

        return $this;
    }

    /**
     * Defines the document title.
     *
     * @param string $title  the title
     * @param bool   $isUTF8 indicates if the string is encoded in ISO-8859-1 (false) or UTF-8 (true)
     */
    public function SetTitle($title, $isUTF8 = false): self
    {
        $this->title = $title;
        parent::SetTitle($title, $isUTF8);

        return $this;
    }

    /**
     * This method prints text from the current position in the same way as Write().
     * An additional parameter allows reducing or increase the font size; it's useful for initials.
     * A second parameter allows to specify an offset so that text is placed at a superscripted or subscribed position.
     *
     * @param float      $h        the line height
     * @param string     $text     the string to print
     * @param float      $fontSize the size of font in points (9 by default)
     * @param float      $offset   the offset of text in points (positive means superscript, negative subscript; 0 by default)
     * @param int|string $link     a URL or an identifier returned by AddLink()
     */
    public function subWrite(float $h, string $text, float $fontSize = PdfFont::DEFAULT_SIZE, float $offset = 0.0, int|string $link = ''): self
    {
        // resize font
        $oldFontSize = $this->FontSizePt;
        $this->SetFontSize($fontSize);

        // reposition y
        $offset = ((($fontSize - $oldFontSize) / $this->k) * 0.3) + ($offset / $this->k);
        $x = $this->x;
        $y = $this->y;
        $this->SetXY($x, $y - $offset);

        // output text
        $this->Write($h, $text, $link);

        // restore position
        $x = $this->x;
        $y = $this->y;
        $this->SetXY($x, $y + $offset);

        // restore font size
        $this->SetFontSize($oldFontSize);

        return $this;
    }

    /**
     * Prints a character string. The origin is on the left of the first character, on the baseline.This method allows to place a string precisely on the page, but it is usually easier to use Cell(), MultiCell() or Write() which are the standard methods to print text.
     *
     * @param float  $x   the abscissa of the origin
     * @param float  $y   the ordinate of the origin
     * @param string $txt the string to print
     */
    public function Text($x, $y, $txt): void
    {
        parent::Text($x, $y, $this->cleanText($txt));
    }

    /**
     * This method prints text from the current position. When the right margin is reached (or the \n character is met) a line break occurs and text continues from the left margin. Upon method exit, the current position is left just at the end of the text.
     * It is possible to put a link on the text.
     *
     * @param float      $h    the line height
     * @param string     $txt  the string to print
     * @param string|int $link a URL or an identifier returned by AddLink()
     */
    public function Write($h, $txt, $link = ''): void
    {
        parent::Write($h, $this->cleanText($txt), $link);
    }

    /**
     * Clean the given text.
     *
     * @param ?string $str the text to convert
     *
     * @return ?string the converted text
     */
    protected function cleanText(?string $str): ?string
    {
        try {
            if (null !== $str && false !== \mb_detect_encoding($str, 'UTF-8', true)) {
                $result = \iconv('UTF-8', 'windows-1252', $str);
                if (false !== $result) {
                    return $result;
                }
            }
        } catch (\Exception) {
        }

        return $str;
    }
}
