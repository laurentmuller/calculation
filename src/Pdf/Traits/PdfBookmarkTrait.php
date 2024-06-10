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

use App\Pdf\PdfDocument;
use App\Pdf\PdfStyle;
use App\Utils\FormatUtils;
use fpdf\PdfException;
use fpdf\PdfMove;
use fpdf\PdfTextAlignment;

/**
 * Trait to handle bookmarks and page index.
 *
 * @psalm-type PdfBookmarkType = array{
 *      text: string,
 *      level: non-negative-int,
 *      y: float,
 *      page: int,
 *      link: int|null,
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
     * @param string $text       the bookmark text
     * @param bool   $isUTF8     indicates if the text is encoded in ISO-8859-1 (false) or UTF-8 (true)
     * @param int    $level      the outline level (0 is top level, 1 is just below, and so on)
     * @param bool   $currentY   indicate if the ordinate of the outline destination in the current page
     *                           is the current position (true) or the top of the page (false)
     * @param bool   $createLink true to create and add a link at the given ordinate position and page
     *
     * @throws PdfException if the given level is invalid. A level is not valid if:
     *                      <ul>
     *                      <li>Is smaller than 0.</li>
     *                      <li>Is greater than the level of the previous bookmark plus 1. For example, if the previous
     *                      bookmark is 2, the allowed values are 0..3.</li>
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
        bool $createLink = true
    ): self {
        // validate
        $this->validateLevel($level);
        // convert
        if (!$isUTF8) {
            $text = $this->convertIsoToUtf8($text);
        }
        // add
        $page = $this->page;
        $y = $currentY ? $this->y : 0.0;
        $link = $createLink ? $this->createLink($y, $page) : null;
        $y = ($this->height - $y) * $this->scaleFactor;
        $this->bookmarks[] = [
            'text' => $text,
            'level' => $level,
            'y' => $y,
            'page' => $page,
            'link' => $link,
            'hierarchy' => [],
        ];

        return $this;
    }

    /**
     * Add an index page (as new page) containing all bookmarks.
     *
     * Each line contains the text on the left, the page number on the right and are separate by the given separator
     * characters.
     *
     * <b>Remark:</b> Do nothing if no bookmark is defined.
     *
     * @param ?string   $title        the index title or null to use the default title ('Index')
     * @param ?PdfStyle $titleStyle   the title style or null to use the default style (Font Arial 9 points Bold)
     * @param ?PdfStyle $contentStyle the content style or null to use the default style (Font Arial 9-point Regular)
     * @param bool      $addBookmark  true to add the index page itself in the list of the bookmarks
     * @param string    $separator    the separator character used between the text and the page
     *
     * @see PdfDocument::addBookmark()
     */
    public function addPageIndex(
        ?string $title = null,
        ?PdfStyle $titleStyle = null,
        ?PdfStyle $contentStyle = null,
        bool $addBookmark = true,
        string $separator = '.'
    ): PdfDocument {
        // empty?
        if ([] === $this->bookmarks) {
            return $this;
        }

        // title
        $this->addPage();
        $titleBookmark = $this->outputIndexTitle($title, $titleStyle, $addBookmark);

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
            $page_size = $this->getStringWidth($page_text) + self::SPACE;
            // level offset
            $offset = $this->outputIndexLevel($bookmark['level']);
            // text
            $link = $bookmark['link'];
            $width = $printable_width - $offset - $page_size - self::SPACE;
            $text_size = $this->outputIndexText($bookmark['text'], $width, $height, $link);
            // separator
            $width -= $text_size + self::SPACE;
            $this->outputIndexSeparator($separator, $width, $height, $link);
            // page
            $this->outputIndexPage($page_text, $page_size, $height, $link);
        }

        return $this->resetStyle();
    }

    protected function putCatalog(): void
    {
        parent::putCatalog();
        if ([] === $this->bookmarks) {
            return;
        }
        $this->putf('/Outlines %d 0 R', $this->bookmarkRoot);
        $this->put('/PageMode /UseOutlines');
    }

    protected function putResources(): void
    {
        parent::putResources();
        if ([] === $this->bookmarks) {
            return;
        }
        $number = $this->objectNumber + 1;
        $lastReference = $this->updateBookmarks();
        foreach ($this->bookmarks as $bookmark) {
            $this->putBookmark($bookmark, $number);
        }
        $this->putNewObj();
        $this->bookmarkRoot = $this->objectNumber;
        $this->putf('<</Type /Outlines /First %d 0 R', $number);
        $this->putf('/Last %d 0 R>>', $number + $lastReference);
        $this->putEndObj();
    }

    private function outputIndexLevel(int $level): float
    {
        $offset = 0;
        if ($level > 0) {
            $offset = (float) $level * 2.0 * self::SPACE;
            $this->x += $offset;
        }

        return $offset;
    }

    private function outputIndexPage(
        string $page,
        float $width,
        float $height,
        ?int $link
    ): void {
        $this->cell(
            width: $width,
            height: $height,
            text: $page,
            move: PdfMove::NEW_LINE,
            align: PdfTextAlignment::RIGHT,
            link: $link
        );
    }

    private function outputIndexSeparator(
        string $separator,
        float $width,
        float $height,
        ?int $link
    ): void {
        $count = (int) \floor($width / $this->getStringWidth($separator));
        $width += self::SPACE;
        if ($count > 0) {
            $text = \str_repeat($separator, $count);
            $this->cell(
                width: $width,
                height: $height,
                text: $text,
                align: PdfTextAlignment::RIGHT,
                link: $link
            );
        } else {
            $this->x += $width;
        }
    }

    private function outputIndexText(
        string $text,
        float $width,
        float $height,
        ?int $link
    ): float {
        $text = $this->cleanText($text);
        $text_width = $this->getStringWidth($text);
        while ($text_width > $width) {
            $text = \substr($text, 0, -1);
            $text_width = $this->getStringWidth($text);
        }
        $this->cell(
            width: $text_width + self::SPACE,
            height: $height,
            text: $text,
            link: $link
        );

        return $text_width;
    }

    /**
     * @psalm-return PdfBookmarkType|false
     */
    private function outputIndexTitle(?string $title, ?PdfStyle $titleStyle, bool $addBookmark): array|false
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
        $this->cell(text: $title, move: PdfMove::NEW_LINE, align: PdfTextAlignment::CENTER);
        $this->resetStyle();

        return $result;
    }

    /**
     * @psalm-param PdfBookmarkType $bookmark
     */
    private function putBookmark(array $bookmark, int $number): void
    {
        $this->putNewObj();
        $this->putf('<</Title %s', $this->textString($bookmark['text']));
        foreach ($bookmark['hierarchy'] as $key => $value) {
            $this->putf('/%s %d 0 R', $key, $number + $value);
        }
        $pageNumber = $this->pageInfos[$bookmark['page']]['number'];
        $this->putf('/Dest [%d 0 R /XYZ 0 %.2F null]', $pageNumber, $bookmark['y']);
        $this->put('/Count 0>>');
        $this->putEndObj();
    }

    private function updateBookmarks(): int
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
    private function validateLevel(int $level): void
    {
        $maxLevel = 0;
        if ([] !== $this->bookmarks) {
            $bookmark = \end($this->bookmarks);
            $maxLevel = $bookmark['level'] + 1;
        }
        if ($level < 0 || $level > $maxLevel) {
            $allowed = \implode('...', \array_unique([0, $maxLevel]));
            $message = \sprintf('Invalid bookmark level: %d. Allowed value: %s.', $level, $allowed);
            throw PdfException::instance($message);
        }
    }
}
