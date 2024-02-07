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

namespace App\Spreadsheet;

use App\Utils\StringUtils;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Class containing the header or footer content.
 */
class HeaderFooter
{
    /**
     * The center section prefix.
     */
    final public const CENTER_SECTION = '&C';

    /**
     * The default font size.
     */
    final public const DEFAULT_FONT_SIZE = 9;

    /**
     * The left section prefix.
     */
    final public const LEFT_SECTION = '&L';

    /**
     * The right section prefix.
     */
    final public const RIGHT_SECTION = '&R';

    // the date and time format
    private const DATE_AND_TIME = '&D - &T';
    // the initial font size
    private const INITIAL_FONT_SIZE = 11;
    // the page/pages format
    private const PAGE_AND_PAGES = 'Page &P / &N';

    // the center text
    private string $centerText = '';
    // the left text
    private string $leftText = '';
    // the right text
    private string $rightText = '';

    /**
     * @param bool $isHeader true to apply to the worksheet header, false for worksheet footer
     */
    private function __construct(private readonly bool $isHeader, private readonly int $fontSize)
    {
    }

    /**
     * Add the given text to the center section.
     *
     * Do nothing if the text is null or empty('').
     *
     * @param ?string $text the text to add
     * @param bool    $bold true to use bold font
     */
    public function addCenter(?string $text, bool $bold = false): self
    {
        return $this->updateText($this->centerText, $text, $bold, true);
    }

    /**
     * Add the current date and time to the given section.
     */
    public function addDateTime(string $part = self::RIGHT_SECTION): self
    {
        return match ($part) {
            self::LEFT_SECTION => $this->updateText($this->leftText, self::DATE_AND_TIME),
            self::CENTER_SECTION => $this->updateText($this->centerText, self::DATE_AND_TIME),
            default => $this->updateText($this->rightText, self::DATE_AND_TIME),
        };
    }

    /**
     * Add the given text to the left section.
     *
     * Do nothing if the text is null or empty('').
     *
     * @param ?string $text the text to add
     * @param bool    $bold true to use bold font
     */
    public function addLeft(?string $text, bool $bold = false): self
    {
        return $this->updateText($this->leftText, $text, $bold, true);
    }

    /**
     * Add the current page and total pages to the given section.
     */
    public function addPages(string $part = self::LEFT_SECTION): self
    {
        return match ($part) {
            self::CENTER_SECTION => $this->updateText($this->centerText, self::PAGE_AND_PAGES),
            self::RIGHT_SECTION => $this->updateText($this->rightText, self::PAGE_AND_PAGES),
            default => $this->updateText($this->leftText, self::PAGE_AND_PAGES),
        };
    }

    /**
     * Add the given text to the right section.
     *
     * Do nothing if the text is null or empty('').
     *
     * @param ?string $text the text to add
     * @param bool    $bold true to use bold font
     */
    public function addRight(?string $text, bool $bold = false): self
    {
        return $this->updateText($this->rightText, $text, $bold, true);
    }

    /**
     * Apply this content to the given worksheet.
     */
    public function apply(Worksheet $sheet): self
    {
        $content = $this->getContent();
        $headerFooter = $sheet->getHeaderFooter();
        if ($this->isHeader) {
            $headerFooter->setOddHeader($content);
        } else {
            $headerFooter->setOddFooter($content);
        }

        return $this;
    }

    /**
     * Create a footer instance.
     */
    public static function footer(int $fontSize = self::DEFAULT_FONT_SIZE): self
    {
        return new self(false, $fontSize);
    }

    /**
     * Create a header instance.
     */
    public static function header(int $fontSize = self::DEFAULT_FONT_SIZE): self
    {
        return new self(true, $fontSize);
    }

    /**
     * Gets content of all sections.
     */
    private function getContent(): string
    {
        $content = '';
        if (StringUtils::isString($this->leftText)) {
            $content .= self::LEFT_SECTION . $this->leftText;
        }
        if (StringUtils::isString($this->centerText)) {
            $content .= self::CENTER_SECTION . $this->centerText;
        }
        if (StringUtils::isString($this->rightText)) {
            $content .= self::RIGHT_SECTION . $this->rightText;
        }

        return $content;
    }

    private function updateText(string &$value, ?string $text, bool $bold = false, bool $clean = false): self
    {
        if (!StringUtils::isString($text)) {
            return $this;
        }
        if ('' !== $value) {
            $value .= StringUtils::NEW_LINE;
        }
        if (self::INITIAL_FONT_SIZE !== $this->fontSize) {
            $value .= \sprintf('&%d', $this->fontSize);
        }
        if ($bold) {
            $value .= '&B';
        } else {
            $value .= '&"-,Regular"';
        }
        if ($clean) {
            $text = \str_replace('&', '&&', $text);
        }
        $value .= $text;

        return $this;
    }
}
