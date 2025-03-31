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

use App\Model\CustomerInformation;
use PhpOffice\PhpWord\Element\Cell;
use PhpOffice\PhpWord\Element\Row;
use PhpOffice\PhpWord\Element\Section;
use PhpOffice\PhpWord\Shared\Converter;
use PhpOffice\PhpWord\SimpleType\Jc;

/**
 * Class to output header in Word documents.
 *
 * This header adds the following texts, depending on the printing address state:
 * <ul>
 * <li>The customer's name at the left and the address if applicable.</li>
 * <li>The document's title at the center, if applicable; at the right else.</li>
 * <li>The phone and the email at the right if applicable.</li>
 * </ul>
 *
 * Do nothing if both the document's title and the customer's name are empty.
 */
class WordHeader extends AbstractHeaderFooter
{
    private ?CustomerInformation $customer = null;

    #[\Override]
    public function output(Section $section): void
    {
        $title = $this->getTitle() ?? '';
        $name = $this->customer?->getName() ?? '';
        $url = $this->customer?->getUrl();

        if ('' === $title && '' === $name) {
            return;
        }

        $row = $section->addHeader()
            ->addTable(['borderBottomSize' => 1])
            ->addRow();

        $cell = $this->outputTitleAndName($row, $title, $name, $url);
        if ($this->customer?->isPrintAddress() ?? false) {
            $this->outputAddress($cell);
        }
    }

    /**
     * Set the customer information.
     */
    public function setCustomer(CustomerInformation $customer): self
    {
        $this->customer = $customer;

        return $this;
    }

    private function addName(Cell $cell, string $name, ?string $url, array $cellStyle, array $textStyle): void
    {
        if (null !== $url) {
            $cell->addLink($url, $name, $cellStyle, $textStyle);
        } else {
            $cell->addText($name, $cellStyle, $textStyle);
        }
    }

    private function outputAddress(Cell $cell): void
    {
        $spaceAfter = Converter::pointToTwip(3);
        $cellStyle = ['size' => 8, 'bold' => false];
        $textStyle = ['spaceBefore' => 0,  'spaceAfter' => 0, 'alignment' => Jc::END];

        // address
        $cell->addText($this->customer?->getAddress() ?? '', $cellStyle, $textStyle);

        // zip and city
        $textStyle['spaceAfter'] = $spaceAfter;
        $cell->addText($this->customer?->getZipCity() ?? '', $cellStyle, $textStyle);
    }

    private function outputTitleAndName(Row $row, string $title, string $name, ?string $url): Cell
    {
        $width = self::TOTAL_WIDTH / 2;
        $cellStyle = ['size' => 10, 'bold' => true];
        $textStyle = ['spaceBefore' => 0,  'spaceAfter' => 0, 'alignment' => Jc::START];

        // title
        $cell = $row->addCell($width);
        $cell->addText($title, $cellStyle, $textStyle);

        // name
        $cellStyle['size'] = 8;
        $textStyle['alignment'] = Jc::END;
        $cell = $row->addCell($width);
        $this->addName($cell, $name, $url, $cellStyle, $textStyle);

        return $cell;
    }
}
