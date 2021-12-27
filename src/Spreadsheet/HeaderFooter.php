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
    public const CENTER_PART = '&C';
    public const LEFT_PART = '&L';
    public const RIGTH_PART = '&R';

    private String $center = '';
    private bool $header;
    private String $left = '';
    private String $right = '';

    /**
     * Constructor.
     *
     * @param bool $header true to apply to the work sheet header, false to apply to the work sheet footer
     */
    public function __construct(bool $header)
    {
        $this->header = $header;
    }

    public function addCenter(string $text, bool $bold = false, bool $clean = true): self
    {
        return $this->updateText($this->center, $text, $bold, $clean);
    }

    public function addDateTime(string $part): void
    {
    }

    public function addLeft(string $text, bool $bold = false, bool $clean = true): self
    {
        return $this->updateText($this->left, $text, $bold, $clean);
    }

    public function addPages(string $part): void
    {
    }

    public function addRight(string $text, bool $bold = false, bool $clean = true): self
    {
        return $this->updateText($this->right, $text, $bold, $clean);
    }

    /**
     * Apply this content to the given work sheet.
     */
    public function apply(Worksheet $sheet): self
    {
        $content = $this->getContent();
        $headerFooter = $sheet->getHeaderFooter();
        if ($this->header) {
            $headerFooter->setOddHeader($content);
        } else {
            $headerFooter->setOddFooter($content);
        }

        return $this;
    }

    public function getContent(): string
    {
        $content = '';
        if (!empty($this->left)) {
            $content .= self::LEFT_PART . $this->left;
        }
        if (!empty($this->center)) {
            $content .= self::CENTER_PART . $this->center;
        }
        if (!empty($this->right)) {
            $content .= self::RIGTH_PART . $this->right;
        }

        return $content;
    }

    private function cleanText(string $text): string
    {
        return \str_replace('&', '&&', $text);
    }

    private function updateText(string &$value, string $text, bool $bold, bool $clean): self
    {
        if (!empty($value)) {
            $value .= "\n";
        }

        if ($bold) {
            $value .= '&B';
        } else {
            $value .= '&"-,Regular"';
        }
        if ($clean) {
            $text = $this->cleanText($text);
        }
        $value .= $text;

        return $this;
    }
}
