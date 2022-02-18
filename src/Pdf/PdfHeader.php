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

namespace App\Pdf;

use App\Model\CustomerInformation;

/**
 * Class to output header in PDF documents.
 *
 * @author Laurent Muller
 */
class PdfHeader implements PdfConstantsInterface
{
    /**
     * The line height for customer address and contact.
     */
    private const SMALL_HEIGHT = self::LINE_HEIGHT - 1;

    /**
     * The customer information.
     */
    protected ?CustomerInformation $customer = null;

    /**
     * The document description.
     */
    protected ?string $description = null;

    /**
     * the parent document.
     */
    protected PdfDocument $parent;

    /**
     * The style for the customer name.
     */
    private ?PdfStyle $nameStyle = null;

    /**
     * The style for customer address, contact and description.
     */
    private ?PdfStyle $smallStyle = null;

    /**
     * The style for the document title.
     */
    private ?PdfStyle $titleStyle = null;

    /**
     * Constructor.
     */
    public function __construct(PdfDocument $parent)
    {
        $this->parent = $parent;
    }

    /**
     * Output this content to the parent document.
     */
    public function output(): void
    {
        // margins
        $margins = $this->parent->setCellMargin(0);

        // lines
        $isAddress = $this->isPrintAddress();
        $printableWidth = $this->parent->getPrintableWidth();
        $this->line1($printableWidth, $isAddress);
        if ($isAddress) {
            $this->line2($printableWidth);
            $this->line3($printableWidth);
        }

        // reset
        $this->parent->setCellMargin($margins);
        $this->parent->resetStyle()->Ln(2);
    }

    /**
     * Sets the customer informations.
     */
    public function setCustomer(CustomerInformation $customer): self
    {
        $this->customer = $customer;

        return $this;
    }

    /**
     * Sets the document description.
     */
    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    private function applyNameStyle(): void
    {
        if (null === $this->nameStyle) {
            $this->nameStyle = PdfStyle::getDefaultStyle()->setFontBold()->setFontSize(8);
        }
        $this->nameStyle->apply($this->parent);
    }

    private function applySmallStyle(): void
    {
        if (null === $this->smallStyle) {
            $this->smallStyle = PdfStyle::getDefaultStyle()->setFontSize(8);
        }
        $this->smallStyle->apply($this->parent);
    }

    private function applyTitleStyle(): void
    {
        if (null === $this->titleStyle) {
            $this->titleStyle = PdfStyle::getDefaultStyle()->setFontBold()->setFontSize(10);
        }
        $this->titleStyle->apply($this->parent);
    }

    private function getAddress(): string
    {
        return null !== $this->customer ? $this->toEmpty($this->customer->getAddress()) : '';
    }

    private function getEmail(): string
    {
        return null !== $this->customer ? $this->toEmpty($this->customer->getEmail()) : '';
    }

    private function getFax(): string
    {
        return null !== $this->customer ? $this->customer->getTranslatedFax($this->parent) : '';
    }

    private function getName(): string
    {
        return null !== $this->customer ? $this->toEmpty($this->customer->getName()) : '';
    }

    private function getPhone(): string
    {
        return null !== $this->customer ? $this->customer->getTranslatedPhone($this->parent) : '';
    }

    private function getUrl(): string
    {
        return null !== $this->customer ? $this->toEmpty($this->customer->getUrl()) : '';
    }

    private function getZipCity(): string
    {
        return null !== $this->customer ? $this->toEmpty($this->customer->getZipCity()) : '';
    }

    private function isPrintAddress(): bool
    {
        return null !== $this->customer && $this->customer->isPrintAddress();
    }

    private function line1(float $printableWidth, bool $isAddress): void
    {
        if ($isAddress) {
            // name + title + phone
            $cellWidth = $printableWidth / 3;
            $this->outputName($cellWidth, true);
            $this->outputTitle($cellWidth, true);
            $this->outputPhone($cellWidth);
        } else {
            // title + name
            $cellWidth = $printableWidth / 2;
            $this->outputTitle($cellWidth, false);
            $this->outputName($cellWidth, false);

            // description
            $this->outputDescription($printableWidth, false);
        }
    }

    private function line2(float $printableWidth): void
    {
        if (null !== $this->description) {
            // address + description + fax
            $cellWidth = $printableWidth / 3;
            $this->outputAddress($cellWidth);
            $this->outputDescription($cellWidth, true);
            $this->outputFax($cellWidth);
        } else {
            // address + fax
            $cellWidth = $printableWidth / 2;
            $this->outputAddress($cellWidth);
            $this->outputFax($cellWidth);
        }
    }

    private function line3(float $printableWidth): void
    {
        // zip city + e-mail
        $cellWidth = $printableWidth / 2;
        $this->outputZipCity($cellWidth);
        $this->outputEmail($cellWidth);
    }

    private function outputAddress(float $width): void
    {
        $this->applySmallStyle();
        $text = $this->getAddress();
        $this->outputText($width, self::SMALL_HEIGHT, $text, self::BORDER_NONE, self::MOVE_TO_RIGHT, self::ALIGN_LEFT);
    }

    private function outputDescription(float $width, bool $isAddress): void
    {
        if (!empty($this->description)) {
            $this->applySmallStyle();
            $align = $isAddress ? self::ALIGN_CENTER : self::ALIGN_LEFT;
            $move = $isAddress ? self::MOVE_TO_RIGHT : self::MOVE_TO_NEW_LINE;
            $this->outputText($width, self::LINE_HEIGHT, $this->description, self::BORDER_NONE, $move, $align);
        }
    }

    private function outputEmail(float $width): void
    {
        $this->applySmallStyle();
        $text = $this->getEmail();
        $link = empty($text) ? '' : "mailto:$text";
        $this->outputText($width, self::SMALL_HEIGHT, $text, self::BORDER_BOTTOM, self::MOVE_TO_NEW_LINE, self::ALIGN_RIGHT, $link);
    }

    private function outputFax(float $width): void
    {
        $this->applySmallStyle();
        $text = $this->getFax();
        $this->outputText($width, self::SMALL_HEIGHT, $text, self::BORDER_NONE, self::MOVE_TO_NEW_LINE, self::ALIGN_RIGHT);
    }

    private function outputName(float $width, bool $isAddress): void
    {
        $this->applyNameStyle();
        $name = $this->getName();
        $align = $isAddress ? self::ALIGN_LEFT : self::ALIGN_RIGHT;
        $border = $isAddress ? self::BORDER_NONE : self::BORDER_BOTTOM;
        $move = $isAddress ? self::MOVE_TO_RIGHT : self::MOVE_TO_NEW_LINE;
        $this->outputText($width, self::LINE_HEIGHT, $name, $border, $move, $align, $this->getUrl() ?: '');
    }

    private function outputPhone(float $width): void
    {
        $this->applySmallStyle();
        $text = $this->getPhone();
        $this->outputText($width, self::SMALL_HEIGHT, $text, self::BORDER_NONE, self::MOVE_TO_NEW_LINE, self::ALIGN_RIGHT);
    }

    /**
     * @param string|int $border
     */
    private function outputText(float $width, float $height, string $text, $border, int $move, string $align, string $link = ''): void
    {
        $this->parent->Cell($width, $height, $text, $border, $move, $align, false, $link);
    }

    private function outputTitle(float $width, bool $isAddress): void
    {
        $this->applyTitleStyle();
        $title = $this->toEmpty($this->parent->getTitle());
        $align = $isAddress ? self::ALIGN_CENTER : self::ALIGN_LEFT;
        $border = $isAddress ? self::BORDER_NONE : self::BORDER_BOTTOM;
        $this->outputText($width, self::LINE_HEIGHT, $title, $border, self::MOVE_TO_RIGHT, $align);
    }

    private function outputZipCity(float $width): void
    {
        $this->applySmallStyle();
        $text = $this->getZipCity();
        $this->outputText($width, self::SMALL_HEIGHT, $text, self::BORDER_BOTTOM, self::MOVE_TO_RIGHT, self::ALIGN_LEFT);
    }

    private function toEmpty(?string $value): string
    {
        return null === $value ? '' : $value;
    }
}
