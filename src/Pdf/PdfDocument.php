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
use App\Pdf\Enums\PdfImageType;
use App\Pdf\Enums\PdfMove;
use App\Pdf\Enums\PdfRectangleStyle;
use App\Pdf\Enums\PdfTextAlignment;
use App\Traits\MathTrait;
use App\Util\FormatUtils;

/**
 * PDF document with default header and footer, outline and index capabilities.
 *
 * @property int                         $page           The current page number.
 * @property PdfPageSizeType             $DefPageSize    The default page size (width and height) in the user unit.
 * @property PdfPageSizeType             $CurPageSize    The current page size (width and height) in the user unit.
 * @property float                       $lMargin        The left margin.
 * @property float                       $tMargin        The top margin.
 * @property float                       $rMargin        The right margin.
 * @property float                       $bMargin        The bottom margin.
 * @property float                       $cMargin        The cell margin.
 * @property float                       $k              The scale factor (number of points in user unit).
 * @property float                       $x              The current X position in user unit.
 * @property float                       $y              The current Y position in user unit.
 * @property float                       $w              The width of current page in user unit.
 * @property float                       $h              The height of current page in user unit.
 * @property string                      $FontFamily     The current font family.
 * @property string                      $FontStyle      The current font style.
 * @property float                       $FontSizePt     The current font size in points.
 * @property float                       $FontSize       The current font size in user unit.
 * @property PdfFontType                 $CurrentFont    The current font information.
 * @property array<string, PdfFontType>  $fonts          The array of used fonts.
 * @property string                      $DefOrientation The default orientation.
 * @property string                      $CurOrientation The current orientation.
 * @property int                         $CurRotation    The current page rotation in degrees.
 * @property float                       $lasth          The height of last printed cell.
 * @property int                         $n              The current object number.
 * @property array<int, PdfPageInfoType> $PageInfo       The page-related data.
 *
 * @method float  GetX()                                           Gets the current X position in user unit.
 * @method float  GetY()                                           Gets the current Y position in user unit.
 * @method int    PageNo()                                         Gets the current page number.
 * @method float  GetPageWidth()                                   Gets the width of current page in user unit.
 * @method float  GetPageHeight()                                  Gets the height of current page in user unit.
 * @method string _textstring(string $s)                           Convert the given string.
 * @method int    AddLink()                                        Creates a new internal link and returns its identifier.
 * @method void   SetLink(int $link, float $y = 0, int $page = -1) Defines the page and position a link points to.
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
 * @psalm-type PdfBookmarkType = array{
 *     text: string,
 *     level: int,
 *     y: float,
 *     page: int,
 *     link: string|int,
 *     parent?: int,
 *     first?: int,
 *     prev?: int,
 *     next?: int,
 *     last?: int}
 */
#[\AllowDynamicProperties]
class PdfDocument extends \FPDF
{
    use MathTrait;

    /**
     * The footer offset.
     */
    final public const FOOTER_OFFSET = -15.0;

    /**
     * The default line height.
     */
    final public const LINE_HEIGHT = 5.0;

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
     * Indicates if the title is encoded in ISO-8859-1 (false) or UTF-8 (true).
     */
    protected bool $titleUTF8 = false;

    /**
     * The bookmark root object number.
     */
    private int $bookmarkRoot = -1;

    /**
     * The bookmarks.
     *
     * @psalm-var array<int, PdfBookmarkType>
     */
    private array $bookmarks = [];

    /**
     * Constructor.
     *
     * @param PdfDocumentOrientation $orientation the page orientation
     * @param PdfDocumentUnit        $unit        the user unit
     * @param PdfDocumentSize        $size        the document size or an array containing the width and the height (expressed in the user unit)
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
     * Add a bookmark.
     *
     * @param string $text   the bookmark text
     * @param bool   $isUTF8 indicates if the text is encoded in ISO-8859-1 (false) or UTF-8 (true)
     * @param int    $level  the outline level (0 is top level, 1 is just below, and so on)
     * @param float  $y      the ordinate of the outline destination in the current page.
     *                       -1 means the current position. 0 means top of page.
     * @param bool   $link   true to create and add a link at the given ordinate position and page
     *
     * @see PdfDocument::addPageIndex()
     */
    public function addBookmark(string $text, bool $isUTF8 = false, int $level = 0, float $y = -1, bool $link = true): self
    {
        if (!$isUTF8) {
            $text = (string) $this->_UTF8encode($text);
        }
        if ($y < 0) {
            $y = $this->y;
        }
        $page = $this->page;
        $linkId = $link ? $this->CreateLink($y, $page) : '';
        $this->bookmarks[] = [
            'text' => $text,
            'level' => \max(0, $level),
            'y' => ($this->h - $y) * $this->k,
            'page' => $page,
            'link' => $linkId,
        ];

        return $this;
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
     * Add a new page (index page) containing all bookmarks.
     *
     * Each line contain the text on the left, the page number on the right and are separate by dot ('.') characters.
     *
     * <b>Remark:</b> Do nothing if no bookmark is defined.
     *
     * @param ?string   $title        the index title or null to use the default title ('Index')
     * @param ?PdfStyle $titleStyle   the title style or null to use the default style (Font Arial 9pt Bold)
     * @param ?PdfStyle $contentStyle the content style or null to use the default style (Font Arial 9pt Regular)
     * @param bool      $addBookmark  true to add the page index in the list of the bookmarks
     *
     * @see PdfDocument::addBookmark()
     */
    public function addPageIndex(?string $title = null, ?PdfStyle $titleStyle = null, ?PdfStyle $contentStyle = null, bool $addBookmark = false): self
    {
        if ([] === $this->bookmarks) {
            return $this;
        }
        $title ??= 'Index';
        $titleStyle ??= PdfStyle::getBoldCellStyle();
        $contentStyle ??= PdfStyle::getDefaultStyle();
        $this->AddPage();
        $titleStyle->apply($this);
        $this->Cell(txt: $title, ln: PdfMove::NEW_LINE, align: PdfTextAlignment::CENTER);
        if ($addBookmark) {
            $this->addBookmark(text: $title, y: $this->y - $this->lasth);
        }
        $space = 1.25;
        $contentStyle->apply($this);
        $line_height = $this->getFontSize() + 2.0;
        $printable_width = $this->getPrintableWidth();
        foreach ($this->bookmarks as $bookmark) {
            $offset = (float) $bookmark['level'] * 2.0 * $space;
            if ($offset > 0) {
                $this->Cell($offset);
            }
            $page_text = FormatUtils::formatInt($bookmark['page']);
            $page_size = $this->GetStringWidth($page_text) + $space;
            $link = $bookmark['link'];
            $text = (string) \iconv('UTF-8', 'ISO-8859-1', $bookmark['text']);
            $text_size = $this->GetStringWidth($text);
            $available_size = $printable_width - $page_size - $offset - 2.0 * $space;
            while ($text_size >= $available_size) {
                $text = \substr($text, 0, -1);
                $text_size = $this->GetStringWidth($text);
            }
            $this->Cell(
                w: $text_size + $space,
                h: $line_height,
                txt: $text,
                link: $link
            );
            $dots_width = $printable_width - $page_size - $offset - $text_size - 2.0 * $space;
            $dots_count = (int) ($dots_width / $this->GetStringWidth('.'));
            if ($dots_count > 0) {
                $dots_text = \str_repeat('.', $dots_count);
                $this->Cell(
                    w: $dots_width + $space,
                    h: $line_height,
                    txt: $dots_text,
                    align: PdfTextAlignment::RIGHT,
                    link: $link
                );
            }
            $this->Cell(
                w: $page_size,
                h: $line_height,
                txt: $page_text,
                ln: PdfMove::NEW_LINE,
                align: PdfTextAlignment::RIGHT,
                link: $link
            );
        }

        return $this->resetStyle();
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
     *                                        <li>A PdfBorder enumeration.</li>
     *                                        <li>A number:
     *                                        <ul>
     *                                        <li><code>0</code> : No border (default value).</li>
     *                                        <li><code>1</code> : Frame.</li>
     *                                        </ul>
     *                                        </li>
     *                                        <li>A string containing some or all of the following characters (in any order):
     *                                        <ul>
     *                                        <li><code>'L'</code> : Left.</li>
     *                                        <li><code>'T'</code> : Top.</li>
     *                                        <li><code>'R'</code> : Right.</li>
     *                                        <li><code>'B'</code> : Bottom.</li>
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
     *                                        <li><code>'L'</code> or an empty string (''): left align (default value).</li>
     *                                        <li><code>'C'</code> : center.</li>
     *                                        <li><code>'R'</code> : right align.</li>
     *                                        </ul>
     * @param bool                    $fill   indicates if the cell background must be painted (true) or transparent (false)
     * @param string|int              $link   a URL or an identifier returned by AddLink()
     *
     * @see PdfDocument::MultiCell()
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
        parent::Cell($w, $h, $this->cleanText($txt), $border, $ln, $align, $fill, $link);
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
     * Gets the default page size (width and height) in the user unit.
     *
     * @return PdfPageSizeType the current page size
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
     * @param float   $width the desired width. If 0.0, the width extends up to the right margin.
     *
     * @return int the number of lines
     */
    public function getLinesCount(?string $text, float $width = 0): int
    {
        if (null === $text || '' === $text) {
            return 0;
        }
        $text = \rtrim(\str_replace("\r", '', $text));
        $lenText = \strlen($text);
        if (0 === $lenText) {
            return 0;
        }
        if ($width <= 0) {
            $width = $this->w - $this->rMargin - $this->x;
        }
        $maxWidth = ($width - 2.0 * $this->cMargin) * 1000.0 / $this->FontSize;
        $sep = -1;
        $index = 0;
        $lastIndex = 0;
        $currentWidth = 0.0;
        $linesCount = 1;
        $cw = $this->CurrentFont['cw'];
        while ($index < $lenText) {
            $ch = $text[$index];
            // new line?
            if (self::NEW_LINE === $ch) {
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
        return (float) parent::GetStringWidth($this->cleanText($s));
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
        return [$this->x, $this->y];
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
     *                                        <li>A PdfBorder enumeration.</li>
     *                                        <li>A number:
     *                                        <ul>
     *                                        <li><code>0</code> : No border (default value).</li>
     *                                        <li><code>1</code> : Frame.</li>
     *                                        </ul>
     *                                        </li>
     *                                        <li>A string containing some or all of the following characters (in any order):
     *                                        <ul>
     *                                        <li><code>'L'</code> : Left.</li>
     *                                        <li><code>'T'</code> : Top.</li>
     *                                        <li><code>'R'</code> : Right.</li>
     *                                        <li><code>'B'</code> : Bottom.</li>
     *                                        </ul>
     *                                        </li>
     *                                        </ul>
     * @param PdfTextAlignment|string $align  the text alignment. The value can be:
     *                                        <ul>
     *                                        <li>A PdfTextAlignment enumeration.</li>
     *                                        <li><code>'L'</code> or an empty string (''): left align (default value).</li>
     *                                        <li><code>'C'</code> : center.</li>
     *                                        <li><code>'R'</code> : right align.</li>
     *                                        <li><code>'J'</code> : justification (default value).</li>
     *                                        </ul>
     * @param bool                    $fill   indicates if the cell background must be painted (true) or transparent (false)
     *
     * @see PdfDocument::Cell()
     */
    public function MultiCell($w = 0, $h = self::LINE_HEIGHT, $txt = '', $border = PdfBorder::NONE, $align = PdfTextAlignment::JUSTIFIED, $fill = false): void
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
     * @param float|int $pixels the pixels to convert
     *
     * @return float the converted value as millimeters
     */
    public function pixels2mm(float|int $pixels): float
    {
        return (float) $pixels * 25.4 / 72.0;
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
     * Outputs a rectangle. It can be drawn (border only), filled (with no border) or both.
     *
     * @param float                              $x     the abscissa of upper-left corner
     * @param float                              $y     the ordinate of upper-left corner
     * @param float                              $w     the width
     * @param float                              $h     the height
     * @param PdfBorder|PdfRectangleStyle|string $style the style of rendering. Possible values are:
     *                                                  <ul>
     *                                                  <li>A PdfBorder instance.</li>
     *                                                  <li>A PdfRectangleStyle enumeration.</li>
     *                                                  <li><code>'D'</code> or an empty string (''): draw (default value).</li>
     *                                                  <li><code>'F'</code> : fill.</li>
     *                                                  <li><code>'DF'</code> : draw and fill.</li>
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
     * The zoom level can be set: pages can be displayed entirely on screen, occupy the
     * full width of the window, use real size, be scaled by a specific zooming factor
     * or use viewer default (configured in the Preferences menu of Adobe Reader).
     * The page layout can be specified too: single at once, continuous display, two
     * columns or viewer default.
     *
     * @param PdfDocumentZoom|string|int $zoom   the zoom to use
     * @param PdfDocumentLayout|string   $layout the page layout
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
     * Sets the font used to print character strings.
     *
     * @param PdfFontName|string $family the font family. It can be either a font name enumeration, a name defined by AddFont()
     *                                   or one of the standard families (case-insensitive):
     *                                   <ul>
     *                                   <li>A PdfFontName enumeration.</li>
     *                                   <li><code>'Courier'</code>: Fixed-width.</li>
     *                                   <li><code>'Helvetica'</code> or <code>Arial</code>: Synonymous: sans serif.</li>
     *                                   <li><code>'Symbol'</code>: Symbolic.</li>
     *                                   <li><code>'ZapfDingbats'</code>: Symbolic.</li>
     *                                   </ul>
     *                                   It is also possible to pass an empty string (""). In that case, the current family is kept.
     * @param string             $style  the font style. Possible values are (case-insensitive):
     *                                   <ul>
     *                                   <li>An empty string (''): Regular.</li>
     *                                   <li><code>'B'</code>: Bold.</li>
     *                                   <li><code>'I'</code>: Italic.</li>
     *                                   <li><code>'U'</code>: Underline.</li>
     *                                   </ul>
     *                                   or any combination. The default value is regular.
     * @param float              $size   the font size in points or 0 to use the current size. If no size has been
     *                                   specified since the beginning of the document, the value is 9.
     */
    public function SetFont($family, $style = '', $size = 0): void
    {
        if ($family instanceof PdfFontName) {
            $family = $family->value;
        }

        parent::SetFont($family, $style, $size);
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
        $this->titleUTF8 = $isUTF8;
        parent::SetTitle($title, $isUTF8);

        return $this;
    }

    /**
     * This method prints text from the current position in the same way as Write().
     *
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
        $oldFontSize = $this->FontSizePt;
        $this->SetFontSize($fontSize);
        $offset = ((($fontSize - $oldFontSize) / $this->k) * 0.3) + ($offset / $this->k);
        $x = $this->x;
        $y = $this->y;
        $this->SetXY($x, $y - $offset);
        $this->Write($h, $text, $link);
        $x = $this->x;
        $y = $this->y;
        $this->SetXY($x, $y + $offset);
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
     * @psalm-param PdfBookmarkType $bookmark
     */
    protected function _putBookmark(array $bookmark, int $n): void
    {
        $this->_newobj();
        $this->_put(\sprintf('<</Title %s', $this->_textstring($bookmark['text'])));
        foreach (['parent', 'prev', 'next', 'first', 'last'] as $key) {
            if (isset($bookmark[$key])) {
                $this->_put(\sprintf('/%s %d 0 R', \ucfirst($key), $n + (int) $bookmark[$key]));
            }
        }
        $pageN = $this->PageInfo[$bookmark['page']]['n'];
        $this->_put(\sprintf('/Dest [%d 0 R /XYZ 0 %.2F null]', $pageN, $bookmark['y']));
        $this->_put('/Count 0>>');
        $this->_put('endobj');
    }

    protected function _putBookmarks(): void
    {
        /** @psalm-var array<int, int> $lastUsedReferences */
        $lastUsedReferences = [];
        $level = 0;
        $count = \count($this->bookmarks);
        foreach ($this->bookmarks as $index => $bookmark) {
            if ($bookmark['level'] > 0) {
                $parent = $lastUsedReferences[$bookmark['level'] - 1];
                $this->bookmarks[$index]['parent'] = $parent;
                $this->bookmarks[$parent]['last'] = $index;
                if ($bookmark['level'] > $level) {
                    $this->bookmarks[$parent]['first'] = $index;
                }
            } else {
                $this->bookmarks[$index]['parent'] = $count;
            }
            if ($bookmark['level'] <= $level && $index > 0) {
                $prev = $lastUsedReferences[$bookmark['level']];
                $this->bookmarks[$prev]['next'] = $index;
                $this->bookmarks[$index]['prev'] = $prev;
            }
            $lastUsedReferences[$bookmark['level']] = $index;
            $level = $bookmark['level'];
        }
        $n = $this->n + 1;
        foreach ($this->bookmarks as $bookmark) {
            $this->_putBookmark($bookmark, $n);
        }
        $this->_newobj();
        $this->bookmarkRoot = $this->n;
        $this->_put(\sprintf('<</Type /Outlines /First %d 0 R', $n));
        $this->_put(\sprintf('/Last %d 0 R>>', $n + $lastUsedReferences[0]));
        $this->_put('endobj');
    }

    protected function _putcatalog(): void
    {
        parent::_putcatalog();
        if ([] !== $this->bookmarks) {
            $this->_put(\sprintf('/Outlines %d 0 R', $this->bookmarkRoot));
            $this->_put('/PageMode /UseOutlines');
        }
    }

    protected function _putresources(): void
    {
        parent::_putresources();
        if ([] !== $this->bookmarks) {
            $this->_putBookmarks();
        }
    }

    /**
     * Add a first level (0) bookmark  with this title as text.
     *
     * Do nothing if no title is defined.
     *
     * @return bool true if the bookmark is added; false otherwise
     */
    protected function addBookmarkTitle(): bool
    {
        if (null !== $this->title) {
            $this->addBookmark($this->title, $this->titleUTF8);

            return true;
        }

        return false;
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
            if (null !== $str && false !== $result = \iconv('UTF-8', 'ISO-8859-1', $str)) {
                return $result;
            }
        } catch (\Exception) {
        }

        return $str;
    }
}
