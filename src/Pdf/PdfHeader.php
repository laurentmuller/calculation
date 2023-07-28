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

namespace App\Pdf;

use App\Model\CustomerInformation;
use App\Pdf\Enums\PdfMove;
use App\Pdf\Enums\PdfTextAlignment;

/**
 * Class to output header in PDF documents.
 */
class PdfHeader
{
    /**
     * The line height for customer address and contact.
     */
    private const SMALL_HEIGHT = PdfDocument::LINE_HEIGHT - 1.0;

    /**
     * The customer information.
     */
    private ?CustomerInformation $customer = null;

    /**
     * The document description.
     */
    private ?string $description = null;

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
    public function __construct(private readonly PdfDocument $parent)
    {
    }

    /**
     * Output this content to the parent document.
     */
    public function output(): void
    {
        $parent = $this->parent;
        $parent->useCellMargin(function () use ($parent): void {
            $isAddress = $this->isPrintAddress();
            $printableWidth = $parent->getPrintableWidth();
            $this->line1($printableWidth, $isAddress);
            if ($isAddress) {
                $this->line2($printableWidth);
                $this->line3($printableWidth);
            }
            $parent->resetStyle()->Ln(2);
        });
    }

    /**
     * Sets the customer information to output.
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
        if (!$this->nameStyle instanceof PdfStyle) {
            $this->nameStyle = PdfStyle::getDefaultStyle()->setFontBold()->setFontSize(8);
        }
        $this->nameStyle->apply($this->parent);
    }

    private function applySmallStyle(): void
    {
        if (!$this->smallStyle instanceof PdfStyle) {
            $this->smallStyle = PdfStyle::getDefaultStyle()->setFontSize(8);
        }
        $this->smallStyle->apply($this->parent);
    }

    private function applyTitleStyle(): void
    {
        if (!$this->titleStyle instanceof PdfStyle) {
            $this->titleStyle = PdfStyle::getDefaultStyle()->setFontBold()->setFontSize(10);
        }
        $this->titleStyle->apply($this->parent);
    }

    private function getAddress(): string
    {
        return $this->customer?->getAddress() ?? '';
    }

    private function getEmail(): string
    {
        return $this->customer?->getEmail() ?? '';
    }

    private function getFax(): string
    {
        return $this->customer?->getTranslatedFax($this->parent) ?? '';
    }

    private function getName(): string
    {
        return $this->customer?->getName() ?? '';
    }

    private function getPhone(): string
    {
        return $this->customer?->getTranslatedPhone($this->parent) ?? '';
    }

    private function getUrl(): string
    {
        return $this->customer?->getUrl() ?? '';
    }

    private function getZipCity(): string
    {
        return $this->customer?->getZipCity() ?? '';
    }

    private function isPrintAddress(): bool
    {
        return $this->customer instanceof CustomerInformation && $this->customer->isPrintAddress();
    }

    private function line1(float $printableWidth, bool $isAddress): void
    {
        if ($isAddress) {
            // name + title + phone
            $cellWidth = $printableWidth / 3.0;
            $this->outputName($cellWidth, true);
            $this->outputTitle($cellWidth, true);
            $this->outputPhone($cellWidth);
        } else {
            // title + name
            $cellWidth = $printableWidth / 2.0;
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
            $cellWidth = $printableWidth / 3.0;
            $this->outputAddress($cellWidth);
            // description
            $this->outputDescription($cellWidth, true);
        } else {
            // address
            $cellWidth = $printableWidth / 2.0;
            $this->outputAddress($cellWidth);
        }
        // fax
        $this->outputFax($cellWidth);
    }

    private function line3(float $printableWidth): void
    {
        // zip city + e-mail
        $cellWidth = $printableWidth / 2.0;
        $this->outputZipCity($cellWidth);
        $this->outputEmail($cellWidth);
    }

    private function outputAddress(float $width): void
    {
        $this->applySmallStyle();
        $text = $this->getAddress();
        $this->outputText($width, self::SMALL_HEIGHT, $text, PdfBorder::none(), PdfTextAlignment::LEFT);
    }

    private function outputDescription(float $width, bool $isAddress): void
    {
        if (!empty($this->description)) {
            $this->applySmallStyle();
            $align = $isAddress ? PdfTextAlignment::CENTER : PdfTextAlignment::LEFT;
            $move = $isAddress ? PdfMove::RIGHT : PdfMove::NEW_LINE;
            $this->outputText($width, self::SMALL_HEIGHT, $this->description, PdfBorder::none(), $align, $move);
        }
    }

    private function outputEmail(float $width): void
    {
        $this->applySmallStyle();
        $text = $this->getEmail();

        $link = '' === $text ? '' : "mailto:$text";
        $this->outputText($width, self::SMALL_HEIGHT, $text, PdfBorder::bottom(), PdfTextAlignment::RIGHT, PdfMove::NEW_LINE, $link);
    }

    private function outputFax(float $width): void
    {
        $this->applySmallStyle();
        $text = $this->getFax();
        $this->outputText($width, self::SMALL_HEIGHT, $text, PdfBorder::none(), PdfTextAlignment::RIGHT, PdfMove::NEW_LINE);
    }

    private function outputName(float $width, bool $isAddress): void
    {
        $this->applyNameStyle();
        $name = $this->getName();
        $align = $isAddress ? PdfTextAlignment::LEFT : PdfTextAlignment::RIGHT;
        $border = $isAddress ? PdfBorder::none() : PdfBorder::bottom();
        $move = $isAddress ? PdfMove::RIGHT : PdfMove::NEW_LINE;
        $this->outputText($width, PdfDocument::LINE_HEIGHT, $name, $border, $align, $move, $this->getUrl());
    }

    private function outputPhone(float $width): void
    {
        $this->applySmallStyle();
        $text = $this->getPhone();
        $this->outputText($width, self::SMALL_HEIGHT, $text, PdfBorder::none(), PdfTextAlignment::RIGHT, PdfMove::NEW_LINE);
    }

    private function outputText(float $width, float $height, ?string $text, PdfBorder $border, PdfTextAlignment $align, PdfMove $move = PdfMove::RIGHT, string $link = ''): void
    {
        $this->parent->Cell(
            w: $width,
            h: $height,
            txt: $text ?? '',
            border: $border,
            ln: $move,
            align: $align,
            link: $link
        );
    }

    private function outputTitle(float $width, bool $isAddress): void
    {
        $this->applyTitleStyle();
        $title = $this->parent->getTitle() ?? '';
        $align = $isAddress ? PdfTextAlignment::CENTER : PdfTextAlignment::LEFT;
        $border = $isAddress ? PdfBorder::none() : PdfBorder::bottom();
        $this->outputText($width, PdfDocument::LINE_HEIGHT, $title, $border, $align);
    }

    private function outputZipCity(float $width): void
    {
        $this->applySmallStyle();
        $text = $this->getZipCity();
        $this->outputText($width, self::SMALL_HEIGHT, $text, PdfBorder::bottom(), PdfTextAlignment::LEFT);
    }
}
