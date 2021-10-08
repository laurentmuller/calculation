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

/**
 * Extends the document to output a Table of content (TOC).
 *
 * @author Laurent Muller
 */
class PdfTocDocument extends PdfDocument
{
    /**
     * The current page.
     */
    private int $currentPage = 1;

    /**
     * The numbering pages TOC state.
     */
    private bool $numbering = false;

    /**
     * The numbering footer pages TOC state.
     */
    private bool $numberingFooter = false;

    /**
     * The TOC entries.
     */
    private array $tocEntries = [];

    /**
     * {@inheritdoc}
     *
     * @param string $orientation
     * @param string $format
     * @param int    $rotation
     */
    public function AddPage($orientation = '', $format = '', $rotation = 0): void
    {
        parent::AddPage($orientation, $format, $rotation);
        if ($this->numbering) {
            ++$this->currentPage;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function Footer(): void
    {
        if (!$this->numberingFooter) {
            return;
        }
        parent::Footer();
        if (!$this->numbering) {
            $this->numberingFooter = false;
        }
    }

    /**
     * Adds a TOC entry.
     *
     * @param string $text  the entry text
     * @param int    $level the entry level
     */
    public function tocAddEntry(string $text, int $level = 0): self
    {
        $this->tocEntries[] = ['text' => $text, 'level' => $level, 'page' => $this->currentPage];

        return $this;
    }

    /**
     * Ouput the TOC to the given page.
     *
     * @param int    $location      the page location of the TOC
     * @param string $title         the title of the TOC
     * @param float  $entryFontSize the font size to use for the TOC entries or 0 to use the current size
     * @param float  $titleFontSize the font size to use for the TOC title or 0 to use the current size
     * @param string $fontName      the font name to use for the TOC entries and title or empty('') to use the current font name
     */
    public function tocOutput(int $location = 1, string $title = 'Table of Contents', float $entryFontSize = 0, float $titleFontSize = 0, string $fontName = ''): self
    {
        // create TOC at end
        $this->tocStop();
        $this->AddPage();
        $startPage = $this->page;

        // add TOC label
        $this->tocOuputTitle($title, $titleFontSize, $fontName);

        // add TOC entries
        foreach ($this->tocEntries as $entry) {
            $this->tocOuputEntry($entry, $entryFontSize, $fontName);
        }

        // move TOC content
        $this->tocUpdatePages($location, $startPage);

        return $this;
    }

    /**
     * Start numbering TOC pages.
     */
    public function tocStart(): self
    {
        $this->numbering = true;
        $this->numberingFooter = true;

        return $this;
    }

    /**
     * Stop numbering TOC pages.
     */
    public function tocStop(): self
    {
        $this->numbering = false;

        return $this;
    }

    /**
     * Output a TOC entry.
     *
     * @param float  $fontSize the font size to use for the TOC entry or 0 to use the current size
     * @param string $fontName the font name to use for the TOC entry or empty('') to use the current font name
     */
    private function tocOuputEntry(array $entry, float $fontSize, string $fontName): void
    {
        $level = $entry['level'];
        $text = $entry['text'];
        $page = $entry['page'];

        // offset
        if ($level > 0) {
            $this->Cell($level * 8);
        }

        // text
        $weight = 0 === $level ? 'B' : '';
        $this->SetFont($fontName, $weight, $fontSize);
        $str_size = $this->GetStringWidth($text);
        $this->Cell($str_size + 2, $this->FontSize + 2, $text);

        // filling dots
        $this->SetFont($fontName, '', $fontSize);
        $page_cell_size = $this->GetStringWidth((string) $page) + 2;
        $width = $this->getPrintableWidth() - $page_cell_size - ($level * 8) - ($str_size + 2);
        $multplier = $width / $this->GetStringWidth('.');
        $dots = \str_repeat('.', (int) $multplier);
        $this->Cell($width, $this->FontSize + 2, $dots, 0, 0, 'R');

        // page number
        $this->Cell($page_cell_size, $this->FontSize + 2, (string) $page, 0, 1, 'R');
    }

    /**
     * Output the TOC title.
     *
     * @param string $title    the title of the TOC
     * @param float  $fontSize the font size to use for the TOC title or 0 to use the current size
     * @param string $fontName the font name to use for the TOC title and entries or empty('') to use the current font name
     */
    private function tocOuputTitle(string $title, float $fontSize, string $fontName): void
    {
        if ('' !== $title) {
            $this->SetFont($fontName, 'B', $fontSize);
            $this->Cell(0, $this->FontSize, $title, 0, 1, 'C');
            $this->Ln(10);
        }
    }

    /**
     * Insert the TOC content in the given location.
     *
     * @param int $location  the page location of the TOC
     * @param int $startPage the index of the start page
     */
    private function tocUpdatePages(int $location, int $startPage): void
    {
        // grab pages and move to the selected location
        $lastPages = [];
        $currentPage = $this->page;
        $tocPage = $currentPage - $startPage + 1;

        // store toc pages
        for ($i = $startPage; $i <= $currentPage; ++$i) {
            $lastPages[] = $this->pages[$i];
        }

        // move pages
        for ($i = $startPage - 1; $i >= $location - 1; --$i) {
            $this->pages[$i + $tocPage] = $this->pages[$i];
        }

        // put TOC pages at insert point
        for ($i = 0; $i < $tocPage; ++$i) {
            $this->pages[$location + $i] = $lastPages[$i];
        }

        // move to end
        $this->page = $currentPage;
    }
}
