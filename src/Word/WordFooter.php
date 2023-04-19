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
use PhpOffice\PhpWord\Element\Row;
use PhpOffice\PhpWord\Element\Section;
use PhpOffice\PhpWord\Shared\Converter;
use PhpOffice\PhpWord\SimpleType\Jc;

/**
 * Class to output footer in Word documents.
 *
 * This footer add the following texts:
 * <ul>
 * <li>The current and total pages at the left.</li>
 * <li>The application name at the center</li>
 * <li>The date and time at the right.</li>
 * </ul>
 */
class WordFooter extends AbstractHeaderFooter
{
    private ?string $name = null;
    private ?string $url = null;

    /**
     * {@inheritdoc}
     */
    public function output(Section $section): void
    {
        $cellStyle = ['size' => 8];
        $textStyle = ['spaceBefore' => Converter::pointToTwip(3)];
        $row = $section->addFooter()
            ->addTable(['borderTopSize' => 1])
            ->addRow();
        $width = self::TOTAL_WIDTH / 3;
        $this->addPage($row, $width, $cellStyle, $textStyle);
        $this->addName($row, $width, $cellStyle, $textStyle);
        $this->addDate($row, $width, $cellStyle, $textStyle);
    }

    /**
     * Set the application name.
     */
    public function setName(?string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Set the application URL.
     */
    public function setUrl(?string $url): self
    {
        $this->url = $url;

        return $this;
    }

    private function addDate(Row $row, int $width, array $cellStyle, array $textStyle): void
    {
        $textStyle['alignment'] = Jc::END;
        $cell = $row->addCell($width);
        $text = FormatUtils::formatDateTime(new \DateTime());
        $cell->addText($text, $cellStyle, $textStyle);
    }

    private function addName(Row $row, int $width, array $cellStyle, array $textStyle): void
    {
        $textStyle['alignment'] = Jc::CENTER;
        $cell = $row->addCell($width);
        $text = $this->name ?? '';
        if (null === $this->url) {
            $cell->addText($text, $cellStyle, $textStyle);
        } else {
            $cell->addLink($this->url, $text, $cellStyle, $textStyle);
        }
    }

    private function addPage(Row $row, int $width, array $cellStyle, array $textStyle): void
    {
        $textStyle['alignment'] = Jc::START;
        $cell = $row->addCell($width);
        $text = $this->trans('word.footer.page');
        $cell->addPreserveText($text, $cellStyle, $textStyle);
    }
}
