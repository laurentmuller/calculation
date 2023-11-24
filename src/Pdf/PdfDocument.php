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
use App\Pdf\Enums\PdfFontName;
use App\Pdf\Enums\PdfFontStyle;
use App\Pdf\Enums\PdfImageType;
use App\Pdf\Enums\PdfMove;
use App\Pdf\Enums\PdfRectangleStyle;
use App\Pdf\Enums\PdfTextAlignment;
use App\Pdf\Traits\PdfBookmarkTrait;
use App\Traits\MathTrait;
use App\Utils\StringUtils;

/**
 * PDF document with default header, footer, outline and index capabilities.
 *
 * @property int                         $page             The current page number.
 * @property PdfPageSizeType             $DefPageSize      The default page size (width and height) in the user unit.
 * @property PdfPageSizeType             $CurPageSize      The current page size (width and height) in the user unit.
 * @property float                       $lMargin          The left margin.
 * @property float                       $tMargin          The top margin.
 * @property float                       $rMargin          The right margin.
 * @property float                       $bMargin          The bottom margin.
 * @property float                       $cMargin          The cell margin.
 * @property float                       $k                The scale factor (number of points in user unit).
 * @property float                       $x                The current X position in user unit.
 * @property float                       $y                The current Y position in user unit.
 * @property float                       $w                The width of current page in user unit.
 * @property float                       $h                The height of current page in user unit.
 * @property string                      $FontFamily       The current font family.
 * @property string                      $FontStyle        The current font style.
 * @property float                       $FontSizePt       The current font size in points.
 * @property float                       $FontSize         The current font size in user unit.
 * @property PdfFontType                 $CurrentFont      The current font information.
 * @property array<string, PdfFontType>  $fonts            The array of used fonts.
 * @property string                      $DefOrientation   The default orientation.
 * @property string                      $CurOrientation   The current orientation.
 * @property int                         $CurRotation      The current page rotation in degrees.
 * @property float                       $lasth            The height of last printed cell.
 * @property int                         $n                The current object number.
 * @property array<int, PdfPageInfoType> $PageInfo         The page-related data.
 * @property float                       $LineWidth        the line width.
 * @property float                       $PageBreakTrigger the threshold used to trigger page breaks
 * @property string                      $PDFVersion       the PDF version
 *
 * @method float  GetX()                                                  Gets the current X position in user unit.
 * @method float  GetY()                                                  Gets the current Y position in user unit.
 * @method int    PageNo()                                                Gets the current page number.
 * @method float  GetPageWidth()                                          Gets the width of current page in user unit.
 * @method float  GetPageHeight()                                         Gets the height of current page in user unit.
 * @method string _textstring(string $s)                                  Convert the given string.
 * @method int    AddLink()                                               Creates a new internal link and returns its identifier.
 * @method void   SetLink(int $link, float $y = 0.0, int $page = -1)      Defines the page and position a link points to.
 * @method void   Line(float $x1, float $y1, float $x2, float $y2)        Draws a line between two points using the current line width.
 * @method void   SetLineWidth(float $width)                              Set the line width.
 * @method void   SetMargins(float $left, float $top, ?float $right=null) Sets the left, top and right margins. By default, they are equal to 1 cm. If the right value is null, the default value is the left one.
 * @method void   SetLeftMargin(float $margin)                            Sets the left margin. The method can be called before creating the first page. If the current abscissa gets out of page, it is brought back to the margin.
 * @method void   SetRightMargin(float $margin)                           Sets the right margin. The method can be called before creating the first page.
 * @method void   SetTopMargin(float $margin)                             Sets the top margin. The method can be called before creating the first page.
 * @method void   SetAutoPageBreak(boolean $auto, float $margin = 0.0)    Enables or disables the automatic page breaking mode. When enabling, the second parameter is the distance from the bottom of the page that defines the triggering limit. By default, the mode is on and the margin is 1.5 cm.
 * @method bool   AcceptPageBreak()                                       Whenever a page break condition is met, the method is called, and the break is issued or not depending on the returned value. The default implementation returns a value according to the mode selected by SetAutoPageBreak().
 *
 * @psalm-type PdfFontType = array{
 *      name: string,
 *      cw: array<string, float>}
 * @psalm-type PdfPageSizeType = array{
 *     0: float,
 *     1: float}
 * @psalm-type PdfPageInfoType = array{
 *      n: int,
 *      rotation: int,
 *      size: PdfPageSizeType}
 */
#[\AllowDynamicProperties]
class PdfDocument extends \FPDF
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
     * The title.
     */
    private ?string $title = null;

    /**
     * @param PdfDocumentOrientation $orientation the page orientation
     * @param PdfDocumentUnit        $unit        the user unit
     * @param PdfDocumentSize        $size        the document size
     */
    public function __construct(
        PdfDocumentOrientation $orientation = PdfDocumentOrientation::PORTRAIT,
        PdfDocumentUnit $unit = PdfDocumentUnit::MILLIMETER,
        PdfDocumentSize $size = PdfDocumentSize::A4
    ) {
        parent::__construct($orientation->value, $unit->value, $size->value);

        $this->header = new PdfHeader($this);
        $this->footer = new PdfFooter($this);

        $this->AliasNbPages();
        $this->SetDisplayMode();
        $this->SetAutoPageBreak(true, $this->bMargin - self::LINE_HEIGHT);
    }

    /**
     * Adds a new page to the document.
     *
     * @param PdfDocumentOrientation|string $orientation the page orientation or an empty string ("") to use the current orientation
     * @param PdfDocumentSize|int[]|string  $size        the page size or an empty string ("") to use the current size
     * @param int                           $rotation    the angle by which to rotate the page or 0 to use the current orientation.
     *                                                   It must be a multiple of 90; positive values mean clockwise rotation.
     *
     * @psalm-param PdfDocumentOrientation|'P'|'L'|'' $orientation
     * @psalm-param PdfDocumentSize|PdfPageSizeType|'' $size
     * @psalm-param 0|90|180|270 $rotation
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
     *                                        <li>A PdfBorder enumeration.</li>
     *                                        <li>A number:
     *                                        <ul>
     *                                        <li><code>0</code>: No border (default value).</li>
     *                                        <li><code>1</code>: Frame.</li>
     *                                        </ul>
     *                                        </li>
     *                                        <li>A string containing some or all of the following characters (in any order):
     *                                        <ul>
     *                                        <li><code>'L'</code>: Left border.</li>
     *                                        <li><code>'T'</code>: Top border.</li>
     *                                        <li><code>'R'</code>: Right border.</li>
     *                                        <li><code>'B'</code>: Bottom border.</li>
     *                                        </ul>
     *                                        </li>
     *                                        </ul>
     * @param PdfMove|int             $ln     indicates where the current position should go after the call.
     *                                        Putting 1 is equivalent to putting <code>0</code> and calling <code>Ln()</code> just after.
     *                                        Possible values are:
     *                                        <ul>
     *                                        <li>A PdfMove enumeration.</li>
     *                                        <li><code>0</code>: Move to the right (default value)</li>
     *                                        <li><code>1</code>: Move to the beginning of the next line</li>
     *                                        <li><code>2</code>: Move below</li>
     *                                        </ul>
     * @param PdfTextAlignment|string $align  the text alignment. The value can be:
     *                                        <ul>
     *                                        <li>A PdfTextAlignment enumeration.</li>
     *                                        <li><code>'L'</code> or an empty string (""): left align (default value).</li>
     *                                        <li><code>'C'</code>: Center.</li>
     *                                        <li><code>'R'</code>: Right align.</li>
     *                                        </ul>
     * @param bool                    $fill   indicates if the cell background must be painted (true) or transparent (false)
     * @param string|int              $link   a URL or an identifier returned by AddLink()
     *
     * @see PdfDocument::MultiCell()
     *
     * @psalm-param PdfMove|int<0,2> $ln
     * @psalm-param PdfTextAlignment|'J'|'L'|'C'|'R' $align
     */
    public function Cell($w = 0, $h = self::LINE_HEIGHT, $txt = '', $border = PdfBorder::NONE, $ln = PdfMove::RIGHT, $align = PdfTextAlignment::LEFT, $fill = false, $link = ''): void
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
        parent::Cell($w, $h, $this->_cleanText($txt), $border, $ln, $align, $fill, $link);
    }

    /**
     * Creates a new internal link for the given position and page and returns its identifier.
     *
     * This is a combination of the <code>AddLink()</code> and the <code>SetLink()</code> functions.
     *
     * @param float $y    the ordinate of the target position; -1 means the current position. 0 means top of page.
     * @param int   $page the target page; -1 indicates the current page
     *
     * @return int the link identifier
     *
     * @see PdfDocument::AddLink()
     * @see PdfDocument::SetLink()
     */
    public function CreateLink(float $y = -1, int $page = -1): int
    {
        $id = $this->addLinK();
        $this->SetLink($id, $y, $page);

        return $id;
    }

    /**
     * This method is automatically called in case of a fatal error; it simply throws an exception with the provided message.
     *
     * @param string $msg
     *
     * @throws PdfException
     */
    public function Error($msg): never
    {
        throw new PdfException($msg);
    }

    /**
     * This method is used to render the page footer.
     *
     * It is automatically called by AddPage() and Close() and should not be called directly by the application.
     * The implementation in PdfDocument call the output method of the PdfFooter.
     *
     * @see PdfFooter
     * @see PdfDocument::Header()
     */
    public function Footer(): void
    {
        $this->footer->output();
    }

    /**
     * Gets the cell margin.
     *
     * The default value is 1 mm.
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
        $name = PdfFontName::tryFrom($this->FontFamily) ?? PdfFontName::getDefault();
        $style = PdfFontStyle::fromStyle($this->FontStyle);

        return new PdfFont($name, $this->FontSizePt, $style);
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
     *
     * Computes the number of lines a MultiCell of the given width will take.
     *
     * @param ?string $text       the text to compute
     * @param float   $width      the desired width. If 0, the width extends up to the right margin.
     * @param ?float  $cellMargin the desired cell margin or null to use current value
     *
     * @return int the number of lines
     */
    public function getLinesCount(?string $text, float $width = 0, float $cellMargin = null): int
    {
        if (null === $text || '' === $text) {
            return 0;
        }
        $text = \rtrim(\str_replace("\r", '', $text));
        $len = \strlen($text);
        if (0 === $len) {
            return 0;
        }
        if ($width <= 0) {
            $width = $this->getRemainingWidth();
        }

        $sep = -1;
        $index = 0;
        $lastIndex = 0;
        $linesCount = 1;
        $currentWidth = 0.0;
        $cw = $this->CurrentFont['cw'];
        $cellMargin ??= $this->cMargin;
        $maxWidth = ($width - 2.0 * $cellMargin) * 1000.0 / $this->FontSize;

        while ($index < $len) {
            $ch = $text[$index];
            // new line?
            if (StringUtils::NEW_LINE === $ch) {
                ++$index;
                $sep = -1;
                $lastIndex = $index;
                $currentWidth = 0.0;
                ++$linesCount;
                continue;
            }
            if (' ' === $ch) {
                $sep = $index;
            }
            $currentWidth += $cw[$ch];
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
                $currentWidth = 0.0;
                ++$linesCount;
            } else {
                ++$index;
            }
        }

        return $linesCount;
    }

    /**
     * Gets the printable width.
     *
     * @return float the document width minus the left and right margins
     */
    public function getPrintableWidth(): float
    {
        return $this->w - $this->lMargin - $this->rMargin;
    }

    /**
     * Gets the remaining printable width.
     *
     * @return float the value from the current abscissa (x) to the right margin
     */
    public function getRemainingWidth(): float
    {
        return $this->w - $this->rMargin - $this->x;
    }

    /**
     * Gets the right margin.
     */
    public function getRightMargin(): float
    {
        return $this->rMargin;
    }

    /**
     * Returns the length of a string in user unit. A font must be selected.
     *
     * @param string $s the string whose length is to be computed
     */
    public function GetStringWidth($s): float
    {
        return (float) parent::GetStringWidth($this->_cleanText($s));
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
     * @return float[] the X and Y position
     *
     * @psalm-return array{0: float, 1: float}
     *
     * @see PdfDocument::GetX()
     * @see PdfDocument::GetY()
     */
    public function GetXY(): array
    {
        return [$this->x, $this->y];
    }

    /**
     * This method is used to render the page header.
     *
     * It is automatically called by AddPage() and should not be called directly by the application.
     * The implementation in PdfDocument  call the output method of the PdfHeader.
     *
     * @see PdfHeader
     * @see PdfDocument::Footer()
     */
    public function Header(): void
    {
        $this->header->output();
    }

    /**
     * Draws a horizontal line with current draw color and optionally the given line width.
     *
     * @param float    $beforeSpace the verticale space before the line
     * @param float    $afterSpace  the verticale space after the line
     * @param ?PdfLine $line        the optional line width to apply
     */
    public function horizontalLine(float $beforeSpace = 1.0, float $afterSpace = 1.0, PdfLine $line = null): self
    {
        $x = $this->x;
        $y = $this->y + $beforeSpace;
        $w = $this->getPrintableWidth();
        $oldLineWidth = $this->LineWidth;
        $line?->apply($this);
        $this->Line($x, $y, $x + $w, $y);
        $this->x = $x;
        $this->y = $y + $afterSpace;
        $this->SetLineWidth($oldLineWidth);

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
     * @param float               $w    the width of the image in the page. There are three cases:
     *                                  <ul>
     *                                  <li>If the value is positive, it represents the width in user unit.</li>
     *                                  <li>If the value is negative, the absolute value represents the horizontal resolution in dpi.</li>
     *                                  <li>If the value is not specified or equal to zero, it is automatically calculated.</li>
     *                                  </ul>
     * @param float               $h    the height of the image in the page. There are three cases:
     *                                  <ul>
     *                                  <li>If the value is positive, it represents the width in user unit.</li>
     *                                  <li>If the value is negative, the absolute value represents the horizontal resolution in dpi.</li>
     *                                  <li>If the value is not specified or equal to zero, it is automatically calculated.</li>
     *                                  </ul>
     * @param PdfImageType|string $type the image format. Possible values are (case-insensitive): A PdfImageType enumeration or 'JPG', 'JPEG', 'PNG' and 'GIF'.
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
     * @param float  $height the desired height
     * @param ?float $y      the ordinate position or null to use the current position
     *
     * @return bool true if printable within the current page; false if a new page is
     *              needed
     *
     * @see PdfDocument::AddPage()
     */
    public function isPrintable(float $height, float $y = null): bool
    {
        return (($y ?? $this->y) + $height) <= $this->PageBreakTrigger;
    }

    /**
     * Sets the ordinate for the given delta value and optionally moves the current abscissa back to the left margin.
     *
     * @param float $delta  the delta value of the ordinate to move to
     * @param bool  $resetX whether to reset the abscissa
     */
    public function moveY(float $delta, bool $resetX = true): self
    {
        if (!$this->isFloatZero($delta)) {
            $this->SetY($this->GetY() + $delta, $resetX);
        }

        return $this;
    }

    /**
     * This method allows printing text with line breaks.
     *
     * They can be automatic (as soon as the text reaches the right border of the cell) or explicit
     * (via the \n character). As many cells as necessary are output, one below the other. Text can be aligned,
     * centered or justified. The cell block can be framed and the background painted.
     *
     * @param float                   $w      the cell width. If 0, the cell extends up to the right margin.
     * @param float                   $h      the cell height
     * @param string                  $txt    the cell text
     * @param PdfBorder|int|string    $border indicates if borders must be drawn around the cell. The value can be either:
     *                                        <ul>
     *                                        <li>A PdfBorder enumeration.</li>
     *                                        <li>A number:
     *                                        <ul>
     *                                        <li><code>0</code>: No border (default value).</li>
     *                                        <li><code>1</code>: Frame.</li>
     *                                        </ul>
     *                                        </li>
     *                                        <li>A string containing some or all of the following characters (in any order):
     *                                        <ul>
     *                                        <li><code>'L'</code>: Left border.</li>
     *                                        <li><code>'T'</code>: Top border.</li>
     *                                        <li><code>'R'</code>: Right border.</li>
     *                                        <li><code>'B'</code>: Bottom border.</li>
     *                                        </ul>
     *                                        </li>
     *                                        </ul>
     * @param PdfTextAlignment|string $align  the text alignment. The value can be:
     *                                        <ul>
     *                                        <li>A PdfTextAlignment enumeration.</li>
     *                                        <li><code>'L'</code> or an empty string (""): left align (default value).</li>
     *                                        <li><code>'C'</code>: center.</li>
     *                                        <li><code>'R'</code>: right align.</li>
     *                                        <li><code>'J'</code>: justification (default value).</li>
     *                                        </ul>
     * @param bool                    $fill   indicates if the cell background must be painted (true) or transparent (false)
     *
     * @see PdfDocument::Cell()
     *
     * @psalm-param PdfTextAlignment|'J'|'L'|'C'|'R' $align
     */
    public function MultiCell($w = 0, $h = self::LINE_HEIGHT, $txt = '', $border = PdfBorder::NONE, $align = PdfTextAlignment::JUSTIFIED, $fill = false): void
    {
        if ($border instanceof PdfBorder) {
            $border = $border->getValue();
        }
        if ($align instanceof PdfTextAlignment) {
            $align = $align->value;
        }
        parent::MultiCell($w, $h, $this->_cleanText($txt), $border, $align, $fill);
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
     *
     * @psalm-param PdfDocumentOutput|'D'|'F'|'I'|'S'|'' $dest
     *
     * @noinspection PhpCastIsUnnecessaryInspection
     */
    public function Output($dest = '', $name = '', $isUTF8 = false): string
    {
        if ($dest instanceof PdfDocumentOutput) {
            $dest = $dest->value;
        }

        return (string) parent::Output($dest, $name, $isUTF8);
    }

    /**
     * Converts the given pixels to millimeters using the given dot per each (DPI).
     *
     * @param float|int $pixels the pixels to convert
     * @param float     $dpi    the dot per inch
     *
     * @return float the converted value as millimeters
     *
     * @psalm-api
     */
    public function pixels2mm(float|int $pixels, float $dpi = 72): float
    {
        return $this->safeDivide((float) $pixels * 25.4, $dpi, $pixels);
    }

    /**
     * Converts the given pixels to user unit using 72 dot per each (DPI).
     *
     * @param float|int $pixels the pixels to convert
     *
     * @return float the converted value as user unit
     */
    public function pixels2UserUnit(float|int $pixels): float
    {
        return (float) $pixels * 72.0 / 96.0 / $this->k;
    }

    /**
     * Converts the given points to user unit.
     */
    public function points2UserUnit(float|int $points): float
    {
        return (float) $points / $this->k;
    }

    /**
     * Outputs a rectangle.
     *
     * It can be drawn (border only), filled (with no border) or both.
     *
     * @param float                              $x     the abscissa of upper-left corner
     * @param float                              $y     the ordinate of upper-left corner
     * @param float                              $w     the width
     * @param float                              $h     the height
     * @param PdfBorder|PdfRectangleStyle|string $style the style of rendering. Possible values are:
     *                                                  <ul>
     *                                                  <li>A PdfBorder instance.</li>
     *                                                  <li>A PdfRectangleStyle enumeration.</li>
     *                                                  <li><code>'D'</code> or an empty string (""): Draw (default value).</li>
     *                                                  <li><code>'F'</code>: Fill.</li>
     *                                                  <li><code>'DF'</code>: Draw and fill.</li>
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

        parent::Rect($x, $y, $w, $h, \strtoupper($style));
    }

    /**
     * Outputs a rectangle.
     *
     * It can be drawn (border only), filled (with no border) or both.
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
        PdfStyle::default()->apply($this);

        return $this;
    }

    /**
     * Sets the cell margin.
     */
    public function setCellMargin(float $margin): self
    {
        $this->cMargin = \max(0.0, $margin);

        return $this;
    }

    /**
     * Defines the document description.
     *
     * @see PdfHeader::setDescription()
     */
    public function setDescription(?string $description): self
    {
        $this->header->setDescription($description);

        return $this;
    }

    /**
     * Defines the way the document is to be displayed by the viewer.
     *
     * @param PdfDocumentZoom|string|int $zoom   the zoom to use It can be one of the following values:
     *                                           <ul>
     *                                           <li>A PdfDocumentZoom enumeration.</li>
     *                                           <li><code>'fullpage'</code>: Displays the entire page on screen.</li>
     *                                           <li><code>'fullwidth'</code>: Uses maximum width of window.</li>
     *                                           <li><code>'real'</code>: Uses real size (equivalent to 100% zoom).</li>
     *                                           <li><code>'default'</code>: Uses viewer default mode.</li>
     *                                           </ul>
     *                                           or a number indicating the zooming factor to use.
     * @param PdfDocumentLayout|string   $layout the page layout. Possible values are:
     *                                           <ul>
     *                                           <li>A PdfDocumentLayout enumeration.</li>
     *                                           <li><code>'single'</code>: Displays one page at once.</li>
     *                                           <li><code>'continuous'</code>: Displays pages continuously.</li>
     *                                           <li><code>'two'</code>: Displays two pages on two columns.</li>
     *                                           <li><code>'default'</code>: Uses viewer default mode.</li>
     *                                           </ul>
     *
     * @psalm-param PdfDocumentZoom|'fullpage'|'fullwidth'|'real'|'default'|int $zoom
     * @psalm-param PdfDocumentLayout|'single'|'continuous'|'two'|'default' $layout
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
     * Defines the color used for all drawing operations (lines, rectangles and cell borders).
     *
     * It can be expressed in RGB components or gray scale. The method can be called before the first page is created and the value is retained from page to page.
     *
     * @param int  $r If $g and $b are given, red component; if not, indicates the gray level. Value between 0 and 255.
     * @param ?int $g the green component (value between 0 and 255)
     * @param ?int $b the blue component (value between 0 and 255)
     *
     * @psalm-param int<0, 255> $r
     * @psalm-param int<0, 255>|null $g
     * @psalm-param int<0, 255>|null $b
     */
    public function SetDrawColor($r, $g = null, $b = null): void
    {
        parent::SetDrawColor($r, $g, $b);
    }

    /**
     * Defines the color used for all filling operations (filled rectangles and cell backgrounds).
     *
     * It can be expressed in RGB components or gray scale. The method can be called before the first page is created and the value is retained from page to page.
     *
     * @param int  $r If $g and $b are given, red component; if not, indicates the gray level. Value between 0 and 255.
     * @param ?int $g the green component (value between 0 and 255)
     * @param ?int $b the blue component (value between 0 and 255)
     *
     * @psalm-param int<0, 255> $r
     * @psalm-param int<0, 255>|null $g
     * @psalm-param int<0, 255>|null $b
     */
    public function SetFillColor($r, $g = null, $b = null): void
    {
        parent::SetFillColor($r, $g, $b);
    }

    /**
     * Sets the font used to print character strings.
     *
     * @param PdfFontName|string  $family the font family. It can be either a font name enumeration, a name defined by AddFont()
     *                                    or one of the standard families (case-insensitive):
     *                                    <ul>
     *                                    <li>A PdfFontName enumeration.</li>
     *                                    <li><code>'Courier'</code>: Fixed-width.</li>
     *                                    <li><code>'Helvetica'</code> or <code>Arial</code>: Synonymous: sans serif.</li>
     *                                    <li><code>'Symbol'</code>: Symbolic.</li>
     *                                    <li><code>'ZapfDingbats'</code>: Symbolic.</li>
     *                                    </ul>
     *                                    It is also possible to pass an empty string (""). In that case, the current family is kept.
     * @param PdfFontStyle|string $style  the font style. It can be either a font style enumeration or one of the given values (case-insensitive):
     *                                    <ul>
     *                                    <li>An empty string (""): Regular.</li>
     *                                    <li><code>'B'</code>: Bold.</li>
     *                                    <li><code>'I'</code>: Italic.</li>
     *                                    <li><code>'U'</code>: Underline.</li>
     *                                    </ul>
     *                                    or any combination. The default value is regular.
     * @param float               $size   the font size in points or 0 to use the current size. If no size has been
     *                                    specified since the beginning of the document, the value is 9.0.
     */
    public function SetFont($family, $style = '', $size = 0): void
    {
        if ($family instanceof PdfFontName) {
            $family = $family->value;
        }
        if ($style instanceof PdfFontStyle) {
            $style = $style->value;
        }
        parent::SetFont($family, $style, $size);
    }

    /**
     * Defines the color used for text.
     *
     * It can be expressed in RGB components or gray scale. The method can be called before the first page is created and the value is retained from page to page.
     *
     * @param int  $r If $g et $b are given, red component; if not, indicates the gray level. Value between 0 and 255.
     * @param ?int $g the green component (value between 0 and 255)
     * @param ?int $b the blue component (value between 0 and 255)
     *
     * @psalm-param int<0, 255> $r
     * @psalm-param int<0, 255>|null $g
     * @psalm-param int<0, 255>|null $b
     */
    public function SetTextColor($r, $g = null, $b = null): void
    {
        parent::SetTextColor($r, $g, $b);
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
     * Prints a character string.
     *
     * The origin is on the left of the first character, on the baseline. This method
     * allows to place a string precisely on the page, but it is usually easier to
     * use Cell(), MultiCell() or Write() which are the standard methods to print text.
     *
     * @param float  $x   the abscissa of the origin
     * @param float  $y   the ordinate of the origin
     * @param string $txt the string to print
     */
    public function Text($x, $y, $txt): void
    {
        parent::Text($x, $y, $this->_cleanText($txt));
    }

    /**
     * Ensure that this version is equal to or greater than the given version.
     *
     * @param string $version the minimum version to set
     */
    public function updateVersion(string $version): void
    {
        if (\version_compare($this->PDFVersion, $version, '<')) {
            $this->PDFVersion = $version;
        }
    }

    /**
     * Set the cell margins to the given value, call the given user function and reset margins to previous value.
     */
    public function useCellMargin(callable $callable, float $margin = 0.0): self
    {
        $previousMargin = $this->getCellMargin();
        $this->setCellMargin($margin);
        \call_user_func($callable);
        $this->setCellMargin($previousMargin);

        return $this;
    }

    /**
     * This method prints text from the current position.
     *
     * When the right margin is reached (or the \n character is met) a line break
     * occurs and text continues from the left margin. Upon method exit, the
     * current position is left just at the end of the text.
     *
     * It is possible to put a link on the text.
     *
     * @param float      $h    the line height
     * @param string     $txt  the string to print
     * @param string|int $link a URL or an identifier returned by AddLink()
     */
    public function Write($h, $txt, $link = ''): void
    {
        parent::Write($h, $this->_cleanText($txt), $link);
    }

    /**
     * Put end object.
     */
    protected function _endobj(): void
    {
        $this->_put('endobj');
    }

    /**
     * Output the formatted string.
     *
     * @param string $format a string produced according to the formatting string format
     */
    protected function _outParams(string $format, float|int|string ...$values): void
    {
        $this->_out(\sprintf($format, ...$values));
    }

    /**
     * Put the formatted string.
     *
     * @param string $format a string produced according to the formatting string format
     */
    protected function _putParams(string $format, float|int|string ...$values): void
    {
        $this->_put(\sprintf($format, ...$values));
    }

    /**
     * Converts the given string from UTF-8, if applicable; to ISO-8859-1.
     *
     * @psalm-return ($str is null ? null : string)
     */
    private function _cleanText(?string $str): ?string
    {
        if (null === $str || '' === $str) {
            return $str;
        }

        try {
            return \mb_convert_encoding($str, self::ENCODING_TO, self::ENCODING_FROM);
        } catch (\Exception) {
            return $str;
        }
    }
}
