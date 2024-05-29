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

use App\Pdf\Colors\PdfDrawColor;
use App\Pdf\Events\PdfLabelTextEvent;
use App\Pdf\Interfaces\PdfLabelTextListenerInterface;
use App\Pdf\Traits\PdfDashLineTrait;
use App\Utils\StringUtils;
use fpdf\PdfException;
use fpdf\PdfFontName;
use fpdf\PdfTextAlignment;

/**
 * PDF document to output labels.
 **/
class PdfLabelDocument extends PdfDocument
{
    use PdfDashLineTrait;

    // the font mapping
    private const FONT_CONVERSION = [
        6 => 2.0,
        7 => 2.5,
        8 => 3.0,
        9 => 4.0,
        10 => 5.0,
        11 => 6.0,
        12 => 7.0,
        13 => 8.0,
        14 => 9.0,
        15 => 10.0,
    ];

    // the padding inside labels
    private const PADDING = 3.0;

    // the current column (0 based index)
    private int $currentCol;
    // the current row (0 based index)
    private int $currentRow;
    // the Avery format
    private PdfAveryFormat $format;
    // the draw border around labels
    private bool $labelBorder = false;
    // the label listener
    private ?PdfLabelTextListenerInterface $labelTextListener = null;
    // the line height
    private float $lineHeight;

    /**
     * @param PdfAveryFormat|string $format     an Avery format or one of the predefined format names
     * @param int                   $startIndex the zero-based index of the first label
     *
     * @psalm-param non-negative-int $startIndex
     *
     * @throws PdfException if the format name is unknown
     */
    public function __construct(PdfAveryFormat|string $format, int $startIndex = 0)
    {
        if (\is_string($format)) {
            $format = $this->getAveryFormat($format);
        }
        parent::__construct(size: $format->pageSize);

        $this->format = $format->updateLayout();
        $this->updateFontSize($format->fontSize)
            ->setFont(PdfFontName::ARIAL)
            ->setAutoPageBreak(false)
            ->setMargins(0, 0)
            ->setCellMargin(0);

        $this->currentCol = $startIndex % $this->format->cols;
        $this->currentRow = \intdiv($startIndex % $this->format->size(), $this->format->cols);
    }

    /**
     * Output a label.
     */
    public function addLabel(string $text): static
    {
        if (0 === $this->page) {
            $this->addPage();
        } elseif ($this->currentRow === $this->format->rows) {
            $this->currentRow = 0;
            $this->addPage();
        }
        $this->outputLabelText($text);
        $this->outputLabelBorder();
        if (++$this->currentCol === $this->format->cols) {
            $this->currentCol = 0;
            ++$this->currentRow;
        }

        return $this;
    }

    /**
     * This implementation skip output footer.
     */
    final public function footer(): void
    {
    }

    /**
     * This implementation skip output header.
     */
    final public function header(): void
    {
    }

    /**
     * Sets a value indicating if a dash border is draw around labels.
     */
    public function setLabelBorder(bool $labelBorder): static
    {
        $this->labelBorder = $labelBorder;

        return $this;
    }

    /**
     * Sets the draw label texts listener.
     */
    public function setLabelTextListener(?PdfLabelTextListenerInterface $labelTextListener): static
    {
        $this->labelTextListener = $labelTextListener;

        return $this;
    }

    protected function putCatalog(): void
    {
        parent::putCatalog();
        $this->put('/ViewerPreferences <</PrintScaling /None>>');
    }

    /**
     * Gets the Avery format for the given name.
     *
     * @throws PdfException if the format is invalid
     */
    private function getAveryFormat(string $name): PdfAveryFormat
    {
        $formats = $this->getAveryFormats();
        if (isset($formats[$name])) {
            return $formats[$name];
        }

        $keys = \implode(', ', \array_keys($formats));
        throw PdfException::format('Unknown label format: %s. Allowed formats: %s.', $name, $keys);
    }

    /**
     * @psalm-return array<string, PdfAveryFormat>
     */
    private function getAveryFormats(): array
    {
        /** @psalm-var array<string, PdfAveryFormat>|null $formats */
        static $formats = null;
        if (null === $formats) {
            $formats = PdfAveryFormat::loadFormats();
        }

        return $formats;
    }

    /**
     * Gets the horizontal label offset.
     */
    private function getLabelX(): float
    {
        return $this->format->getOffsetX($this->currentCol) + self::PADDING;
    }

    /**
     * Gets the vertical label offset.
     */
    private function getLabelY(string $text): float
    {
        $y = $this->format->getOffsetY($this->currentRow);
        $height = (float) $this->getLinesCount($text, $this->format->width - 2.0 * self::PADDING) * $this->lineHeight;

        return $y + ($this->format->height - $height) / 2.0;
    }

    /**
     * Output the label's border if applicable.
     */
    private function outputLabelBorder(): void
    {
        if (!$this->labelBorder) {
            return;
        }

        PdfDrawColor::cellBorder()->apply($this);
        $x = $this->format->getOffsetX($this->currentCol);
        $y = $this->format->getOffsetY($this->currentRow);
        $this->dashedRect($x, $y, $this->format->width, $this->format->height);
    }

    /**
     * Output the label's text if applicable.
     */
    private function outputLabelText(string $text): void
    {
        if ('' === $text) {
            return;
        }

        $x = $this->getLabelX();
        $y = $this->getLabelY($text);
        $height = $this->lineHeight;
        $width = $this->format->width - self::PADDING;

        // listener?
        if ($this->labelTextListener instanceof PdfLabelTextListenerInterface) {
            $texts = \explode(StringUtils::NEW_LINE, $text);
            $lines = \count($texts);
            foreach ($texts as $index => $value) {
                $this->setXY($x, $y);
                $event = new PdfLabelTextEvent($this, $value, $index, $lines, $width, $height);
                if (!$this->labelTextListener->drawLabelText($event)) {
                    $this->cell($width, $height, $value);
                }
                $y += $this->lastHeight;
            }

            return;
        }

        // default
        $this->setXY($x, $y);
        $this->multiCell(width: $width, height: $height, text: $text, align: PdfTextAlignment::LEFT);
    }

    /**
     * Update font size and line height.
     *
     * @throws PdfException if the given size in points is not set
     */
    private function updateFontSize(int $pt): static
    {
        if (!isset(self::FONT_CONVERSION[$pt])) {
            $sizes = \implode(', ', \array_keys(self::FONT_CONVERSION));
            throw PdfException::format('Invalid font size: %d. Allowed sizes: [%s]', $pt, $sizes);
        }

        $this->lineHeight = self::FONT_CONVERSION[$pt];
        $this->setFontSizeInPoint($pt);

        return $this;
    }
}
