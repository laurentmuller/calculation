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

namespace App\Pdf\Traits;

use App\Pdf\Enums\PdfMove;
use App\Pdf\Enums\PdfTextAlignment;
use App\Pdf\PdfDocument;
use App\Pdf\PdfException;
use App\Pdf\PdfStyle;
use App\Utils\FormatUtils;

/**
 * Trait to handle bookmarks and page index.
 *
 * @psalm-type PdfBookmarkType = array{
 *      text: string,
 *      level: non-negative-int,
 *      y: float,
 *      page: int,
 *      link: string|int,
 *      hierarchy: array<string, int>}
 */
trait PdfBookmarkTrait
{
    /**
     * The default index title.
     */
    private const INDEX_TITLE = 'Index';

    /**
     * The space between texts.
     */
    private const SPACE = 1.25;

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
     * @param string $text     the bookmark text
     * @param bool   $isUTF8   indicates if the text is encoded in ISO-8859-1 (false) or UTF-8 (true)
     * @param int    $level    the outline level (0 is top level, 1 is just below, and so on)
     * @param bool   $currentY indicate if the ordinate of the outline destination in the current page
     *                         is the current position (true) or the top of the page (false)
     * @param bool   $link     true to create and add a link at the given ordinate position and page
     *
     * @throws PdfException if the given level is invalid. A level is not valid if:
     *                      <ul>
     *                      <li>Is smaller than 0.</li>
     *                      <li>Is greater than the level of the previous bookmark plus 1. For example if the previous
     *                      bookmark is 2; the allowed values are 0..3.</li>
     *                      </ul>
     *
     * @see PdfDocument::addPageIndex()
     *
     * @psalm-param non-negative-int $level
     */
    public function addBookmark(
        string $text,
        bool $isUTF8 = false,
        int $level = 0,
        bool $currentY = true,
        bool $link = true
    ): self {
        // validate
        $this->_validateLevel($level);

        // convert
        if (!$isUTF8) {
            $text = (string) $this->_UTF8encode($text);
        }

        // add
        $page = $this->page;
        $y = $currentY ? $this->y : 0.0;
        $id = $link ? $this->CreateLink($y, $page) : '';
        $y = ($this->h - $y) * $this->k;
        $this->bookmarks[] = [
            'text' => $text,
            'level' => $level,
            'y' => $y,
            'page' => $page,
            'link' => $id,
            'hierarchy' => [],
        ];

        return $this;
    }

    /**
     * Add an index page (as new page) containing all bookmarks.
     *
     * Each line contain the text on the left, the page number on the right and are separate by the given separator
     * characters.
     *
     * <b>Remark:</b> Do nothing if no bookmark is defined.
     *
     * @param ?string   $title        the index title or null to use the default title ('Index')
     * @param ?PdfStyle $titleStyle   the title style or null to use the default style (Font Arial 9 points Bold)
     * @param ?PdfStyle $contentStyle the content style or null to use the default style (Font Arial 9 points Regular)
     * @param bool      $addBookmark  true to add the index page itself in the list of the bookmarks
     * @param string    $separator    the separator character used between the text and the page
     *
     * @see PdfDocument::addBookmark()
     */
    public function addPageIndex(
        string $title = null,
        PdfStyle $titleStyle = null,
        PdfStyle $contentStyle = null,
        bool $addBookmark = true,
        string $separator = '.'
    ): PdfDocument {
        // empty?
        if ([] === $this->bookmarks) {
            return $this;
        }

        // title
        $this->AddPage();
        $titleBookmark = $this->_outputIndexTitle($title, $titleStyle, $addBookmark);

        // content style
        $contentStyle ??= PdfStyle::default();
        $contentStyle->apply($this);

        $height = $this->getFontSize() + self::SPACE;
        $printable_width = $this->getPrintableWidth();
        if ('' === $separator) {
            $separator = ' ';
        }

        // bookmarks
        foreach ($this->bookmarks as $bookmark) {
            // skip title bookmark
            if ($titleBookmark === $bookmark) {
                continue;
            }

            // page text and size
            $page_text = FormatUtils::formatInt($bookmark['page']);
            $page_size = $this->GetStringWidth($page_text) + self::SPACE;
            // level offset
            $offset = $this->_outputIndexLevel($bookmark['level']);
            // text
            $link = $bookmark['link'];
            $width = $printable_width - $offset - $page_size - self::SPACE;
            $text_size = $this->_outputIndexText($bookmark['text'], $width, $height, $link);
            // separator
            $width -= $text_size + self::SPACE;
            $this->_outputIndexSeparator($separator, $width, $height, $link);
            // page
            $this->_outputIndexPage($page_text, $page_size, $height, $link);
        }

        return $this->resetStyle();
    }

    protected function _putcatalog(): void
    {
        parent::_putcatalog();
        if ([] === $this->bookmarks) {
            return;
        }
        $this->_putParams('/Outlines %d 0 R', $this->bookmarkRoot);
        $this->_put('/PageMode /UseOutlines');
    }

    protected function _putresources(): void
    {
        parent::_putresources();
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

    private function _outputIndexLevel(int $level): float
    {
        $offset = 0;
        if ($level > 0) {
            $offset = (float) $level * 2.0 * self::SPACE;
            $this->x += $offset;
        }

        return $offset;
    }

    private function _outputIndexPage(
        string $page,
        float $width,
        float $height,
        string|int $link
    ): void {
        $this->Cell(
            w: $width,
            h: $height,
            txt: $page,
            ln: PdfMove::NEW_LINE,
            align: PdfTextAlignment::RIGHT,
            link: $link
        );
    }

    private function _outputIndexSeparator(
        string $separator,
        float $width,
        float $height,
        string|int $link
    ): void {
        $count = (int) ($width / $this->GetStringWidth($separator));
        $width += self::SPACE;
        if ($count > 0) {
            $text = \str_repeat($separator, $count);
            $this->Cell(
                w: $width,
                h: $height,
                txt: $text,
                align: PdfTextAlignment::RIGHT,
                link: $link
            );
        } else {
            $this->x += $width;
        }
    }

    private function _outputIndexText(
        string $text,
        float $width,
        float $height,
        string|int $link
    ): float {
        $text = $this->_cleanText($text);
        $text_width = $this->GetStringWidth($text);
        while ($text_width > $width) {
            $text = \substr($text, 0, -1);
            $text_width = $this->GetStringWidth($text);
        }
        $this->Cell(
            w: $text_width + self::SPACE,
            h: $height,
            txt: $text,
            link: $link
        );

        return $text_width;
    }

    /**
     * @psalm-return PdfBookmarkType|false
     */
    private function _outputIndexTitle(?string $title, ?PdfStyle $titleStyle, bool $addBookmark): array|false
    {
        $title ??= self::INDEX_TITLE;
        $titleStyle ??= PdfStyle::getBoldCellStyle();

        $result = false;
        if ($addBookmark) {
            try {
                $this->addBookmark($title, currentY: false);
                $result = \end($this->bookmarks);
            } catch (PdfException) {
            }
        }
        $titleStyle->apply($this);
        $this->Cell(txt: $title, ln: PdfMove::NEW_LINE, align: PdfTextAlignment::CENTER);
        $this->resetStyle();

        return $result;
    }

    /**
     * @psalm-param PdfBookmarkType $bookmark
     */
    private function _putBookmark(array $bookmark, int $n): void
    {
        $this->_newobj();
        $this->_putParams('<</Title %s', $this->_textstring($bookmark['text']));
        foreach ($bookmark['hierarchy'] as $key => $value) {
            $this->_putParams('/%s %d 0 R', $key, $n + $value);
        }
        $page = $this->PageInfo[$bookmark['page']]['n'];
        $this->_putParams('/Dest [%d 0 R /XYZ 0 %.2F null]', $page, $bookmark['y']);
        $this->_put('/Count 0>>');
        $this->_endobj();
    }

    private function _updateBookmarks(): int
    {
        $level = 0;
        $references = [];
        $count = \count($this->bookmarks);
        foreach ($this->bookmarks as $index => &$bookmark) {
            $currentLevel = $bookmark['level'];
            if ($currentLevel > 0) {
                $parent = $references[$currentLevel - 1];
                $bookmark['hierarchy']['Parent'] = $parent;
                $this->bookmarks[$parent]['hierarchy']['Last'] = $index;
                if ($currentLevel > $level) {
                    $this->bookmarks[$parent]['hierarchy']['First'] = $index;
                }
            } else {
                $bookmark['hierarchy']['Parent'] = $count;
            }
            if ($currentLevel <= $level && $index > 0) {
                $prev = $references[$currentLevel];
                $bookmark['hierarchy']['Prev'] = $prev;
                $this->bookmarks[$prev]['hierarchy']['Next'] = $index;
            }
            $references[$currentLevel] = $index;
            $level = $currentLevel;
        }

        return $references[0];
    }

    /**
     * @throws PdfException
     */
    private function _validateLevel(int $level): void
    {
        $maxLevel = 0;
        if ([] !== $this->bookmarks) {
            $bookmark = \end($this->bookmarks);
            $maxLevel = $bookmark['level'] + 1;
        }
        if ($level < 0 || $level > $maxLevel) {
            $allowed = \implode('...', \array_unique([0, $maxLevel]));
            $message = \sprintf('Invalid bookmark level: %d. Allowed value: %s.', $level, $allowed);
            $this->Error($message);
        }
    }
}
