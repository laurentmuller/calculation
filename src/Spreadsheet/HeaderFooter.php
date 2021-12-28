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

namespace App\Spreadsheet;

use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Class containing the header or footer content.
 *
 * @author Laurent Muller
 */
class HeaderFooter
{
    public const DEFAULT_FONT_SIZE = 11;

    public const PART_CENTER = '&C';
    public const PART_LEFT = '&L';
    public const PART_RIGHT = '&R';

    private int $fontSize;
    private bool $isHeader;
    private String $textCenter = '';
    private String $textLeft = '';
    private String $textRight = '';

    /**
     * Constructor.
     *
     * @param bool $isHeader true to apply to the work sheet header, false to apply to the work sheet footer
     */
    public function __construct(bool $isHeader, int $fontSize = self::DEFAULT_FONT_SIZE)
    {
        $this->isHeader = $isHeader;
        $this->fontSize = $fontSize;
    }

    /**
     * Adds the given text to the center.
     */
    public function addCenter(string $text, bool $bold = false, bool $clean = true): self
    {
        return $this->updateText($this->textCenter, $text, $bold, $clean);
    }

    /**
     * Add the date and the time to the given part.
     */
    public function addDateTime(string $part = self::PART_RIGHT): self
    {
        $text = '&D - &T';
        switch ($part) {
            case self::PART_LEFT:
                return $this->addLeft($text, false, false);
            case self::PART_CENTER:
                return $this->addCenter($text, false, false);
            case self::PART_RIGHT:
            default:
                return $this->addRight($text, false, false);
        }
    }

    /**
     * Adds the given text to the left.
     */
    public function addLeft(string $text, bool $bold = false, bool $clean = true): self
    {
        return $this->updateText($this->textLeft, $text, $bold, $clean);
    }

    /**
     * Add the current page and the total pages to the given part.
     */
    public function addPages(string $part = self::PART_LEFT): self
    {
        $text = 'Page &P / &N';
        switch ($part) {
            case self::PART_CENTER:
                return $this->addCenter($text, false, false);
            case self::PART_RIGHT:
                return $this->addRight($text, false, false);
            case self::PART_LEFT:
            default:
                return $this->addLeft($text, false, false);
        }
    }

    /**
     * Adds the given text to the right.
     */
    public function addRight(string $text, bool $bold = false, bool $clean = true): self
    {
        return $this->updateText($this->textRight, $text, $bold, $clean);
    }

    /**
     * Apply this content to the given work sheet.
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
     * Gets all content.
     */
    public function getContent(): string
    {
        $content = '';
        if (!empty($this->textLeft)) {
            $content .= self::PART_LEFT . $this->textLeft;
        }
        if (!empty($this->textCenter)) {
            $content .= self::PART_CENTER . $this->textCenter;
        }
        if (!empty($this->textRight)) {
            $content .= self::PART_RIGHT . $this->textRight;
        }

        return $content;
    }

    private function updateText(string &$value, string $text, bool $bold, bool $clean): self
    {
        if ('' === $text) {
            return $this;
        }

        if (!empty($value)) {
            $value .= "\n";
        }

        if (self::DEFAULT_FONT_SIZE !== $this->fontSize) {
            $value .= '&' . $this->fontSize;
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
