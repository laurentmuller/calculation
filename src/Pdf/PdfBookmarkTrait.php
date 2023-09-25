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

use App\Pdf\Enums\PdfMove;
use App\Pdf\Enums\PdfTextAlignment;
use App\Utils\FormatUtils;

/**
 * Trait to handle PDF bookmarks.
 *
 * @psalm-type PdfBookmarkType = array{
 *      text: string,
 *      level: int,
 *      y: float,
 *      page: int,
 *      link: string|int,
 *      parent?: int,
 *      first?: int,
 *      prev?: int,
 *      next?: int,
 *      last?: int}
 */
trait PdfBookmarkTrait
{
    /**
     * The default index title.
     */
    private const INDEX_TITLE = 'Index';

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
     * Add a bookmark.
     *
     * @param string $text       the bookmark text
     * @param bool   $isUTF8     indicates if the text is encoded in ISO-8859-1 (false) or UTF-8 (true)
     * @param int    $level      the outline level (0 is top level, 1 is just below, and so on)
     * @param bool   $useCurrent the ordinate of the outline destination in the current page.
     *                           true means the current position. false means top of page.
     * @param bool   $link       true to create and add a link at the given ordinate position and page
     *
     * @see PdfDocument::addPageIndex()
     */
    public function addBookmark(string $text, bool $isUTF8 = false, int $level = 0, bool $useCurrent = true, bool $link = true): self
    {
        if (!$isUTF8) {
            $text = (string) $this->_UTF8encode($text);
        }

        $page = $this->page;
        $y = $useCurrent ? $this->y : 0.0;
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
    public function addPageIndex(string $title = null, PdfStyle $titleStyle = null, PdfStyle $contentStyle = null, bool $addBookmark = true): PdfDocument
    {
        if ([] === $this->bookmarks) {
            return $this;
        }
        // title
        $this->AddPage();
        $titleBookmark = $this->_outputIndexTitle($title, $titleStyle, $addBookmark);

        // bookmarks
        $space = 1.25;
        $contentStyle ??= PdfStyle::getDefaultStyle();
        $contentStyle->apply($this);
        $line_height = $this->getFontSize() + $space;
        $printable_width = $this->getPrintableWidth();

        foreach ($this->bookmarks as $bookmark) {
            // skip title bookmark
            if ($titleBookmark === $bookmark) {
                continue;
            }

            // page size
            $page_text = FormatUtils::formatInt($bookmark['page']);
            $page_size = $this->GetStringWidth($page_text) + $space;

            // level
            $offset = $this->_outputIndexLevel($bookmark['level'], $space);

            // text
            $link = $bookmark['link'];
            $text_size = $this->_outputIndexText(
                $bookmark['text'],
                $printable_width,
                $line_height,
                $page_size,
                $offset,
                $space,
                $link
            );

            // dot
            $this->_outputIndexDot(
                $printable_width,
                $line_height,
                $page_size,
                $offset,
                $text_size,
                $space,
                $link
            );

            // page
            $this->_outputIndexPage(
                $line_height,
                $page_size,
                $page_text,
                $link
            );
        }

        return $this->resetStyle();
    }

    protected function putBookmarksToCatalog(): void
    {
        if ([] === $this->bookmarks) {
            return;
        }
        $this->_putParams('/Outlines %d 0 R', $this->bookmarkRoot);
        $this->_put('/PageMode /UseOutlines');
    }

    protected function putBookmarksToResources(): void
    {
        if ([] === $this->bookmarks) {
            return;
        }
        $n = $this->n + 1;
        $lastReference = $this->_updateBookmarks();
        foreach ($this->bookmarks as $bookmark) {
            $this->_putBookmark($bookmark, $n);
        }
        $this->_newobj();
        $this->bookmarkRoot = $this->n;
        $this->_putParams('<</Type /Outlines /First %d 0 R', $n);
        $this->_putParams('/Last %d 0 R>>', $n + $lastReference);
        $this->_endobj();
    }

    private function _endobj(): void
    {
        $this->_put('endobj');
    }

    private function _outputIndexDot(
        float $printable_width,
        float $line_height,
        float $page_size,
        float $offset,
        float $text_size,
        float $space,
        string|int $link
    ): void {
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
    }

    private function _outputIndexLevel(int $level, float $space): float
    {
        if ($level > 0) {
            $offset = (float) $level * 2.0 * $space;
            $this->Cell($offset);

            return $offset;
        }

        return 0;
    }

    private function _outputIndexPage(
        float $line_height,
        float $page_size,
        string $page_text,
        string|int $link
    ): void {
        $this->Cell(
            w: $page_size,
            h: $line_height,
            txt: $page_text,
            ln: PdfMove::NEW_LINE,
            align: PdfTextAlignment::RIGHT,
            link: $link
        );
    }

    private function _outputIndexText(
        string $text,
        float $printable_width,
        float $line_height,
        float $page_size,
        float $offset,
        float $space,
        string|int $link
    ): float {
        $text = $this->_cleanText($text);
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

        return $text_size;
    }

    /**
     * @psalm-return PdfBookmarkType|false
     */
    private function _outputIndexTitle(?string $title, ?PdfStyle $titleStyle, bool $addBookmark): array|false
    {
        $title ??= self::INDEX_TITLE;
        $titleStyle ??= PdfStyle::getBoldCellStyle();

        if ($addBookmark) {
            $this->addBookmark($title);
        }
        $titleStyle->apply($this);
        $this->Cell(txt: $title, ln: PdfMove::NEW_LINE, align: PdfTextAlignment::CENTER);

        return $addBookmark ? \end($this->bookmarks) : false;
    }

    /**
     * @psalm-param PdfBookmarkType $bookmark
     */
    private function _putBookmark(array $bookmark, int $n): void
    {
        $this->_newobj();
        $this->_putParams('<</Title %s', $this->_textstring($bookmark['text']));
        foreach (['parent', 'prev', 'next', 'first', 'last'] as $key) {
            if (isset($bookmark[$key])) {
                $this->_putParams('/%s %d 0 R', \ucfirst($key), $n + (int) $bookmark[$key]);
            }
        }
        $pageN = $this->PageInfo[$bookmark['page']]['n'];
        $this->_putParams('/Dest [%d 0 R /XYZ 0 %.2F null]', $pageN, $bookmark['y']);
        $this->_put('/Count 0>>');
        $this->_endobj();
    }

    private function _putParams(string $format, float|int|string ...$values): void
    {
        $this->_put(\sprintf($format, ...$values));
    }

    private function _updateBookmarks(): int
    {
        $level = 0;
        $count = \count($this->bookmarks);
        /** @psalm-var array<int, int> $references */
        $references = [];
        foreach ($this->bookmarks as $index => $bookmark) {
            if ($bookmark['level'] > 0) {
                $parent = $references[$bookmark['level'] - 1];
                $this->bookmarks[$index]['parent'] = $parent;
                $this->bookmarks[$parent]['last'] = $index;
                if ($bookmark['level'] > $level) {
                    $this->bookmarks[$parent]['first'] = $index;
                }
            } else {
                $this->bookmarks[$index]['parent'] = $count;
            }
            if ($bookmark['level'] <= $level && $index > 0) {
                $prev = $references[$bookmark['level']];
                $this->bookmarks[$prev]['next'] = $index;
                $this->bookmarks[$index]['prev'] = $prev;
            }
            $references[$bookmark['level']] = $index;
            $level = $bookmark['level'];
        }

        return $references[0];
    }
}
