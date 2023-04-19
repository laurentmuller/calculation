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

namespace App\Word;

use App\Utils\FormatUtils;
use PhpOffice\PhpWord\Element\Section;
use PhpOffice\PhpWord\Shared\Converter;
use PhpOffice\PhpWord\SimpleType\Jc;

/**
 * Footer with pages, date and application name (as link).
 */
class WordFooter
{
    private ?string $name = null;
    private ?string $url = null;

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    /**
     * Add this footer to the given section.
     */
    public function output(Section $section): self
    {
        if (null === $this->name) {
            return $this;
        }

        $cellStyle = ['size' => 8];
        $tableStyle = ['borderTopSize' => 1];
        $spaceBefore = Converter::pointToTwip(3);

        $footer = $section->addFooter();
        $row = $footer->addTable($tableStyle)->addRow();

        // page
        $page = 'Page {PAGE} / {NUMPAGES}';
        $leftCell = $row->addCell(4000);
        $leftCell->addPreserveText($page, $cellStyle, ['alignment' => Jc::START, 'spaceBefore' => $spaceBefore]);

        // application
        $centerCell = $row->addCell(4000);
        $name = $this->cleanText($this->name);
        if (null === $this->url) {
            $centerCell->addText($name, $cellStyle, ['alignment' => Jc::CENTER, 'spaceBefore' => $spaceBefore]);
        } else {
            $centerCell->addLink($this->url, $name, $cellStyle, ['alignment' => Jc::CENTER, 'spaceBefore' => $spaceBefore]);
        }

        // date
        $date = FormatUtils::formatDateTime(new \DateTime());
        $rightCell = $row->addCell(4000);
        $rightCell->addText($date, $cellStyle, ['alignment' => Jc::END, 'spaceBefore' => $spaceBefore]);

        return $this;
    }

    public function setName(?string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function setUrl(?string $url): self
    {
        $this->url = $url;

        return $this;
    }

    private function cleanText(?string $str): string
    {
        return null !== $str ? \htmlspecialchars($str) : '';
    }
}
