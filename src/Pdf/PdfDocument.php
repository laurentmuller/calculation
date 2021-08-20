<?php
/*
 * This file is part of the Calculation package.
 *
 * (c) bibi.nu. <bibi@bibi.nu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace App\Pdf;

use App\Traits\MathTrait;
use FPDF;

/**
 * PDF document with default header and footer.
 *
 * @author Laurent Muller
 */
class PdfDocument extends FPDF implements PdfConstantsInterface
{
    use MathTrait;

    /**
     * The footer offset.
     */
    public const FOOTER_OFFSET = -15;

    /**
     * Displays pages continuously.
     */
    public const LAYOUT_CONTINOUS = 'continuous';

    /**
     * Uses viewer default mode.
     */
    public const LAYOUT_DEFAULT = 'default';

    /**
     * Displays one page at once.
     */
    public const LAYOUT_SINGLE = 'single';

    /**
     * Displays two pages on two columns.
     */
    public const LAYOUT_TWO_PAGES = 'two';

    /**
     * The document orientation as landscape.
     */
    public const ORIENTATION_LANDSCAPE = 'L';

    /**
     * The document orientation as portrait.
     */
    public const ORIENTATION_PORTRAIT = 'P';

    /**
     * Send to the browser and force a file download with the given name parameter.
     */
    public const OUTPUT_DOWNLOAD = 'D';

    /**
     * Save to a local file with the given name parameter (may include a path).
     */
    public const OUTPUT_FILE = 'F';

    /**
     * Send the file inline to the browser (default).
     * The PDF viewer is used if available.
     */
    public const OUTPUT_INLINE = 'I';

    /**
     * Return the document as a string.
     */
    public const OUTPUT_STRING = 'S';

    /**
     * The A3 document size.
     */
    public const SIZE_A3 = 'A3';

    /**
     * The A4 document size.
     */
    public const SIZE_A4 = 'A4';

    /**
     * The A5 document size.
     */
    public const SIZE_A5 = 'A5';

    /**
     * The Legal document size.
     */
    public const SIZE_LEGAL = 'Legal';

    /**
     * The Letter document size.
     */
    public const SIZE_LETTER = 'Letter';

    /**
     * The centimeter document unit.
     */
    public const UNIT_CENTIMETER = 'cm';

    /**
     * The inch document unit.
     */
    public const UNIT_INCH = 'in';

    /**
     * The millimeter document unit.
     */
    public const UNIT_MILLIMETER = 'mm';

    /**
     * The point document unit.
     */
    public const UNIT_POINT = 'pt';

    /**
     * Uses viewer default mode.
     */
    public const ZOOM_DEFAULT = 'default';

    /**
     * Displays the entire page on screen.
     */
    public const ZOOM_FULL_PAGE = 'fullpage';

    /**
     * Uses maximum width of window.
     */
    public const ZOOM_FULL_WIDTH = 'fullwidth';

    /**
     * Uses real size (equivalent to 100% zoom).
     */
    public const ZOOM_REAL = 'real';

    /**
     * The application name.
     *
     * @var string
     */
    protected $applicationName;

    /**
     * The company name.
     *
     * @var string
     */
    protected $company;

    /**
     * The company web site.
     *
     * @var string
     */
    protected $companyUrl;

    /**
     * The header description.
     *
     * @var string
     */
    protected $description;

    /**
     * The owner web site.
     *
     * @var string
     */
    protected $ownerUrl;

    /**
     * The title.
     *
     * @var string
     */
    protected $title;

    /**
     * Constructor.
     *
     * @param string $orientation the page orientation. One of the ORIENTATION_XX contants.
     * @param string $unit        the measure unit. One of the UNIT_XX contants.
     * @param mixed  $size        the document size. One of the SIZE_XX contants or an array containing
     *                            the width and height of the document.
     */
    public function __construct(string $orientation = self::ORIENTATION_PORTRAIT, string $unit = self::UNIT_MILLIMETER, $size = self::SIZE_A4)
    {
        parent::__construct($orientation, $unit, $size);
        $this->SetDisplayMode(self::ZOOM_FULL_PAGE, self::LAYOUT_SINGLE);
        $this->AliasNbPages();
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
     * @param float      $w      the cell width. If 0, the cell extends up to the right margin.
     * @param float      $h      the cell height
     * @param string     $txt    the cell text
     * @param int|string $border indicates if borders must be drawn around the cell. The value can be either:
     *                           <ul>
     *                           <li>A number:
     *                           <ul>
     *                           <li><b>0</b> : No border (default value).</li>
     *                           <li><b>1</b> : Frame.</li>
     *                           </ul>
     *                           </li>
     *                           <li>A string containing some or all of the following characters (in any order):
     *                           <ul>
     *                           <li>'<b>L</b>' : Left.</li>
     *                           <li>'<b>T</b>' : Top.</li>
     *                           <li>'<b>R</b>' : Right.</li>
     *                           <li>'<b>B</b>' : Bottom.</li>
     *                           </ul>
     *                           </li>
     *                           </ul>
     * @param int        $ln     indicates where the current position should go after the call.
     *                           Putting 1 is equivalent to putting <code>0</code> and calling <code>Ln()</code> just after. The default value is <code>0</code>.
     *                           Possible values are:
     *                           <ul>
     *                           <li><b>0</b>: To the right</li>
     *                           <li><b>1</b>: To the beginning of the next line</li>
     *                           <li><b>2</b>: Below</li>
     *                           </ul>
     * @param string     $align  the text alignment. The value can be:
     *                           <ul>
     *                           <li>'<b>L</b>' or en empty string: left align (default value).</li>
     *                           <li>'<b>C</b>' : center.</li>
     *                           <li>'<b>R</b>' : right align.</li>
     *                           </ul>
     * @param bool       $fill   indicates if the cell background must be painted (true) or transparent (false). Default value is false.
     * @param string|int $link   an URL or an identifier returned by AddLink()
     */
    public function Cell($w, $h = 0.0, $txt = '', $border = 0, $ln = 0, $align = '', $fill = false, $link = ''): void
    {
        parent::Cell($w, $h, $this->cleanText($txt), $border, $ln, $align, $fill, $link);
    }

    /**
     * {@inheritdoc}
     */
    public function Footer(): void
    {
        // font and color
        $style = PdfStyle::getDefaultStyle()->setFontSize(8);
        $style->apply($this);

        // margins
        $margins = $this->setCellMargin(0);

        // position and cells width
        $this->SetY(self::FOOTER_OFFSET);
        $cellWidth = $this->getPrintableWidth() / 3;

        // pages
        $text = 'Page ' . $this->PageNo() . ' / {nb}';
        $this->Cell($cellWidth, self::LINE_HEIGHT, $text, self::BORDER_TOP, self::MOVE_TO_RIGHT, self::ALIGN_LEFT);

        // program and version and owner link (if any)
        $text = $this->applicationName ?: '';
        $this->Cell($cellWidth, self::LINE_HEIGHT, $text, self::BORDER_TOP, self::MOVE_TO_RIGHT, self::ALIGN_CENTER, false, $this->ownerUrl);

        // date
        $text = \date('d.m.Y - H:i');
        $this->Cell($cellWidth, self::LINE_HEIGHT, $text, self::BORDER_TOP, self::MOVE_TO_RIGHT, self::ALIGN_RIGHT);

        // reset
        $this->setCellMargin($margins);
        $style->reset()->apply($this);
    }

    /**
     * Gets the application name.
     *
     * @return string
     */
    public function getApplicationName(): ?string
    {
        return $this->applicationName;
    }

    /**
     * Gets the left and right cell margins.
     * The default value is 1 mm.
     *
     * @return float the cell margin
     */
    public function getCellMargin(): float
    {
        return $this->cMargin;
    }

    /**
     * Gets the company name.
     *
     * @return string
     */
    public function getCompany(): ?string
    {
        return $this->company;
    }

    /**
     * Gets the company web site.
     *
     * @return string
     */
    public function getCompanyUrl(): ?string
    {
        return $this->companyUrl;
    }

    /**
     * Gets the current font.
     */
    public function getCurrentFont(): PdfFont
    {
        return new PdfFont($this->FontFamily, $this->FontSizePt, $this->FontStyle);
    }

    /**
     * Gets the current orientation.
     *
     * @return string the current orientation. Is one of the of the ORIENTATION_XX
     *                contants.
     */
    public function getCurrentOrientation(): string
    {
        return $this->CurOrientation;
    }

    /**
     * Gets the current page.
     */
    public function getCurrentPage(): int
    {
        return $this->page;
    }

    /**
     * Gets the current page size.
     *
     * @return string the current page size. Is one of the of the SIZE_XX contants.
     */
    public function getCurrentPageSize(): string
    {
        return $this->CurPageSize;
    }

    /**
     * Gets the current rotation.
     *
     * @return int the current rotation
     */
    public function getCurrentRotation(): int
    {
        return $this->CurRotation;
    }

    /**
     * Gets the default orientation.
     *
     * @return int the current orientation. Is one of the of the ORIENTATION_XX
     *             contants.
     */
    public function getDefaultOrientation(): int
    {
        return $this->DefOrientation;
    }

    /**
     * Gets the default page size.
     *
     * @return string the current page size. Is one of the of the SIZE_XX contants.
     */
    public function getDefaultPageSize(): string
    {
        return $this->DefPageSize;
    }

    /**
     * Gets the document description.
     *
     * @return string
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * Gets the current font size in user unit.
     */
    public function getFontSize(): float
    {
        return $this->FontSize;
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
     * @param string $text
     *                      the text to compute
     * @param float  $width
     *                      the desired width. If 0, the width extends up to the right margin.
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
        $cw = &$this->CurrentFont['cw'];

        // reun over text
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
     * Gets the owner web site.
     *
     * @return string
     */
    public function getOwnerUrl(): ?string
    {
        return $this->ownerUrl;
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
     *
     * @return float the printable width
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

        return parent::GetStringWidth($s);
    }

    /**
     * Gets the title.
     *
     * @return string
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
     * @see Fpdf::GetX()
     * @see Fpdf::GetY()
     */
    public function GetXY(): array
    {
        return [$this->GetX(), $this->GetY()];
    }

    /**
     * {@inheritdoc}
     */
    public function Header(): void
    {
        // style
        $style = PdfStyle::getDefaultStyle();
        $style->apply($this);

        // margins
        $margins = $this->setCellMargin(0);

        // get values
        $title = $this->title ?: '';
        $company = $this->company ?: '';
        $description = $this->getDescription() ?: '';

        // title or company?
        if (!empty($title) || !empty($company)) {
            // cells width
            $cellWidth = $this->getPrintableWidth() / 2;

            // title
            $style->setFontBold()
                ->setFontSize(10)
                ->apply($this);
            $this->Cell($cellWidth, self::LINE_HEIGHT, $title, self::BORDER_BOTTOM, self::MOVE_TO_RIGHT, self::ALIGN_LEFT);

            // company and compane web site (if any)
            $style->setFontBold()
                ->setFontSize(8)
                ->apply($this);
            $this->Cell($cellWidth, self::LINE_HEIGHT, $company, self::BORDER_BOTTOM, self::MOVE_TO_RIGHT, self::ALIGN_RIGHT, false, $this->companyUrl);
            $this->Ln();
        }

        // description
        if (!empty($description)) {
            $style->reset()
                ->setFontSize(8)
                ->apply($this);
            $this->Cell($this->getPrintableWidth(), self::LINE_HEIGHT, $description);
            $this->Ln();
        }

        // reset
        $this->setCellMargin($margins);
        $style->reset()->apply($this);
        $this->Ln(3);
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
     * Returns if the current page orientation is Landscape.
     *
     * @return bool true if Landscape
     */
    public function isLandscape(): bool
    {
        return self::ORIENTATION_LANDSCAPE === $this->CurOrientation;
    }

    /**
     * Returns if the current page orientation is Portrait.
     *
     * @return bool true if Portrait
     */
    public function isPortrait(): bool
    {
        return self::ORIENTATION_PORTRAIT === $this->CurOrientation;
    }

    /**
     * Returns if the given height would not cause an overflow (new page).
     *
     * @param float $height
     *                      the desired height
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
     * @param float      $w      the cell width. If 0, the cell extends up to the right margin.
     * @param float      $h      the cell height
     * @param string     $txt    the cell text
     * @param int|string $border indicates if borders must be drawn around the cell. The value can be either:
     *                           <ul>
     *                           <li>A number:
     *                           <ul>
     *                           <li><b>0</b> : No border (default value).</li>
     *                           <li><b>1</b> : Frame.</li>
     *                           </ul>
     *                           </li>
     *                           <li>A string containing some or all of the following characters (in any order):
     *                           <ul>
     *                           <li>'<b>L</b>' : Left.</li>
     *                           <li>'<b>T</b>' : Top.</li>
     *                           <li>'<b>R</b>' : Right.</li>
     *                           <li>'<b>B</b>' : Bottom.</li>
     *                           </ul>
     *                           </li>
     *                           </ul>
     * @param string     $align  the text alignment. The value can be:
     *                           <ul>
     *                           <li>'<b>L</b>' or an empty string: left align.</li>
     *                           <li>'<b>C</b>' : center.</li>
     *                           <li>'<b>R</b>' : right align.</li>
     *                           <li>'<b>J</b>' : justification (default value).</li>
     *                           </ul>
     * @param bool       $fill   indicates if the cell background must be painted (true) or transparent (false). Default value is false.
     */
    public function MultiCell($w, $h, $txt, $border = 0, $align = 'J', $fill = false): void
    {
        parent::MultiCell($w, $h, $this->cleanText($txt), $border, $align, $fill);
    }

    /**
     * Converts the pixels to millimeters with 72 dot per each (DPI).
     *
     * @param float $pixels the pixels to convert
     *
     * @return float the converted value as millimeters
     */
    public function pixels2mm(float $pixels): float
    {
        return $pixels * 25.4 / 72;
    }

    /**
     * Converts the pixels to user unit.
     *
     * @param float $pixels the pixels to convert
     *
     * @return float the converted value as user unit
     */
    public function pixels2UserUnit(float $pixels): float
    {
        return $pixels * 72 / 96 / $this->k;
    }

    /**
     * Outputs a rectangle. It can be drawn (border only), filled (with no border) or both.
     *
     * @param PdfRectangle $bounds the rectangle to output
     * @param string|int   $style  the style of rendering. Possible values are:
     *                             <ul>
     *                             <li>'<b>D</b>' or an empty string (''): Draw. This is the default value.</li>
     *                             <li>'<b>F</b>': Fill</li>
     *                             <li>'<b>DF</b> or '<b>FD</b>': Draw and Fill.</li>
     *                             <li><b>PdfConstantsInterface.BORDER_ALL</b>: Draw.</li>
     *                             <li><b>PdfConstantsInterface.BORDER_NONE</b>: Do nothing.</li>
     *                             </ul>
     */
    public function rectangle(PdfRectangle $bounds, $style = self::RECT_BORDER): self
    {
        if (self::BORDER_NONE !== $style) {
            if (self::BORDER_ALL === $style) {
                $style = self::RECT_BORDER;
            }
            $this->Rect($bounds->x(), $bounds->y(), $bounds->width(), $bounds->height(), (string) $style);
        }

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
     * Sets the application name.
     *
     * @param string $applicationName
     */
    public function setApplicationName(?string $applicationName): self
    {
        $this->applicationName = (string) $applicationName;

        return $this;
    }

    /**
     * Sets the cell margins. The minimum value allowed is 0.
     *
     * @param float $margin
     *                      the margins to set
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
     * Sets the company name.
     *
     * @param string $company
     */
    public function setCompany(?string $company): self
    {
        $this->company = (string) $company;

        return $this;
    }

    /**
     * Sets the company web site.
     *
     * @param string $companyUrl
     */
    public function setCompanyUrl(?string $companyUrl): self
    {
        $this->companyUrl = (string) $companyUrl;

        return $this;
    }

    /**
     * Sets the document description.
     *
     * @param string $description
     */
    public function setDescription(?string $description): self
    {
        $this->description = (string) $description;

        return $this;
    }

    /**
     * Sets the owner web site.
     *
     * @param string $ownerUrl
     */
    public function setOwnerUrl(?string $ownerUrl): self
    {
        $this->ownerUrl = $ownerUrl;

        return $this;
    }

    /**
     * Defines the title of the document.
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
     * An additional parameter allows to reduce or increase the font size; it's useful for initials.
     * A second parameter allows to specify an offset so that text is placed at a superscripted or subscripted position.
     *
     * @param float      $h        the line height
     * @param string     $text     the string to print
     * @param float      $fontSize the size of font in points (9 by default)
     * @param float      $offset   the offset of text in points (positive means superscript, negative subscript; 0 by default)
     * @param string|int $link     an URL or an identifier returned by AddLink()
     */
    public function subWrite(float $h, string $text, float $fontSize = PdfFont::DEFAULT_SIZE, float $offset = 0.0, $link = ''): self
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
     * Prints a character string. The origin is on the left of the first character, on the baseline. This method allows to place a string precisely on the page, but it is usually easier to use Cell(), MultiCell() or Write() which are the standard methods to print text.
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
     * @param string|int $link an URL or an identifier returned by AddLink()
     */
    public function Write($h, $txt, $link = ''): void
    {
        parent::Write($h, $this->cleanText($txt), $link);
    }

    /**
     * Clean the given text.
     *
     * @param string $str the text to convert
     *
     * @return string|null the converted text
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
        } catch (\Exception $e) {
            // ignore
        }

        return $str;
    }
}
