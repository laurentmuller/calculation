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
 * This header add the following texts, depending on the printing address state:
 * <ul>
 * <li>The customer's name at the left and the address if applicable.</li>
 * <li>The document's title at the center, if applicable; at the right else.</li>
 * <li>The phone, the fax and the email at the right if applicable.</li>
 * </ul>
 *
 * Do nothing if the document's title and the customer's name are empty.
 */
class WordHeader extends AbstractHeaderFooter
{
    private ?CustomerInformation $customer = null;

    private bool $printAddress = false;

    /**
     * {@inheritdoc}
     */
    public function output(Section $section): void
    {
        $title = $this->getTitle() ?? '';
        $name = $this->customer?->getName() ?? '';
        if ('' === $title && '' === $name) {
            return;
        }

        $row = $section->addHeader()
            ->addTable(['borderBottomSize' => 1])
            ->addRow();
        if ($this->printAddress) {
            $this->outputAddress($row, $title, $name);
        } else {
            $this->outputDefault($row, $title, $name);
        }
    }

    /**
     * Set the customer information.
     */
    public function setCustomer(?CustomerInformation $customer): self
    {
        $this->customer = $customer;

        return $this;
    }

    /**
     * Set a value indicating if the customer address is printed.
     */
    public function setPrintAddress(bool $printAddress): self
    {
        $this->printAddress = $printAddress;

        return $this;
    }

    private function addEmail(Cell $cell, array $cellStyle, array $textStyle): void
    {
        $email = $this->customer?->getEmail() ?? '';
        if ('' !== $email) {
            $cell->addLink("mailto:$email", $email, $cellStyle, $textStyle);
        } else {
            $cell->addText($email, $cellStyle, $textStyle);
        }
    }

    private function addFax(Cell $cell, array $cellStyle, array $textStyle): void
    {
        $text = $this->customer?->getTranslatedFax($this->getTranslator()) ?? '';
        $cell->addText($text, $cellStyle, $textStyle);
    }

    private function addName(Cell $cell, string $name, array $cellStyle, array $textStyle): void
    {
        if (null !== $url = $this->customer?->getUrl()) {
            $cell->addLink($url, $name, $cellStyle, $textStyle);
        } else {
            $cell->addText($name, $cellStyle, $textStyle);
        }
    }

    private function addPhone(Cell $cell, array $cellStyle, array $textStyle): void
    {
        $text = $this->customer?->getTranslatedPhone($this->getTranslator()) ?? '';
        $cell->addText($text, $cellStyle, $textStyle);
    }

    private function outputAddress(Row $row, string $title, string $name): void
    {
        $width = self::TOTAL_WIDTH / 3;
        $spaceAfter = Converter::pointToTwip(3);
        $cellStyle = ['size' => 8, 'bold' => true];
        $textStyle = ['spaceBefore' => 0,  'spaceAfter' => 0, 'alignment' => Jc::START];

        // name
        $cell = $row->addCell($width);
        $this->addName($cell, $name, $cellStyle, $textStyle);
        // address
        $cellStyle['bold'] = false;
        $text = $this->customer?->getAddress() ?? '';
        $cell->addText($text, $cellStyle, $textStyle);
        // zip and city
        $textStyle['spaceAfter'] = $spaceAfter;
        $text = $this->customer?->getZipCity() ?? '';
        $cell->addText($text, $cellStyle, $textStyle);

        // title
        $cellStyle['bold'] = true;
        $cellStyle['size'] = 10;
        $textStyle['alignment'] = Jc::CENTER;
        $textStyle['spaceAfter'] = 0;
        $cell = $row->addCell($width);
        $cell->addText($title, $cellStyle, $textStyle);

        // phone
        $cellStyle['bold'] = false;
        $cellStyle['size'] = 8;
        $textStyle['alignment'] = Jc::END;
        $cell = $row->addCell($width);
        $this->addPhone($cell, $cellStyle, $textStyle);
        // fax
        $this->addFax($cell, $cellStyle, $textStyle);
        // email
        $textStyle['spaceAfter'] = $spaceAfter;
        $this->addEmail($cell, $cellStyle, $textStyle);
    }

    private function outputDefault(Row $row, string $title, string $name): void
    {
        $width = self::TOTAL_WIDTH / 2;
        $cellStyle = ['size' => 10, 'bold' => true];
        $textStyle = ['spaceBefore' => 0, 'spaceAfter' => Converter::pointToTwip(3)];

        // title
        $textStyle['alignment'] = Jc::START;
        $cell = $row->addCell($width);
        $cell->addText($title, $cellStyle, $textStyle);

        // name
        $textStyle['alignment'] = Jc::END;
        $cell = $row->addCell($width);
        $this->addName($cell, $name, $cellStyle, $textStyle);
    }
}
