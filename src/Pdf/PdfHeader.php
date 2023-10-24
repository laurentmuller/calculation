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
use App\Utils\StringUtils;

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
     * Gets the required height.
     */
    public function getHeight(): float
    {
        $height = PdfDocument::LINE_HEIGHT;
        if ($this->isPrintAddress()) {
            $height += 2.0 * self::SMALL_HEIGHT;
        }
        if (StringUtils::isString($this->description)) {
            $width = $this->parent->getPrintableWidth();
            $lines = $this->parent->getLinesCount($this->description, $width);
            $height += self::SMALL_HEIGHT * (float) $lines;
        }

        return $height;
    }

    /**
     * Output this content to the parent document.
     */
    public function output(): void
    {
        $parent = $this->parent;
        $printableWidth = $parent->getPrintableWidth();
        $parent->useCellMargin(function () use ($printableWidth): void {
            $isAddress = $this->isPrintAddress();
            $this->line1($printableWidth, $isAddress);
            if ($isAddress) {
                $this->line2($printableWidth);
                $this->line3($printableWidth);
            }
            $this->outputDescription();
        });
        $parent->resetStyle()->Ln(2);
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

    private function isPrintAddress(): bool
    {
        return $this->customer instanceof CustomerInformation && $this->customer->isPrintAddress();
    }

    private function line1(float $printableWidth, bool $isAddress): void
    {
        if ($isAddress) {
            // customer + title + phone
            $cellWidth = $printableWidth / 3.0;
            $this->outputName($cellWidth, true);
            $this->outputTitle($cellWidth, true);
            $this->outputPhone($cellWidth);
        } else {
            // title + name
            $cellWidth = $printableWidth / 2.0;
            $this->outputTitle($cellWidth, false);
            $this->outputName($cellWidth, false);
        }
    }

    private function line2(float $printableWidth): void
    {
        $cellWidth = $printableWidth / 2.0;
        $this->outputAddress($cellWidth);
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
        $text = $this->customer?->getAddress() ?? '';
        $this->outputText($width, self::SMALL_HEIGHT, $text, PdfBorder::NONE, PdfTextAlignment::LEFT);
    }

    private function outputDescription(): void
    {
        $description = $this->description ?? '';
        if ('' === $description) {
            return;
        }
        $this->applySmallStyle();
        $this->parent->MultiCell(h: self::SMALL_HEIGHT, txt: $description, align: PdfTextAlignment::LEFT);
    }

    private function outputEmail(float $width): void
    {
        $this->applySmallStyle();
        $text = $this->customer?->getEmail() ?? '';
        $link = '' === $text ? '' : "mailto:$text";
        $this->outputText($width, self::SMALL_HEIGHT, $text, PdfBorder::BOTTOM, PdfTextAlignment::RIGHT, PdfMove::NEW_LINE, $link);
    }

    private function outputFax(float $width): void
    {
        $this->applySmallStyle();
        $text = $this->customer?->getTranslatedFax($this->parent) ?? '';
        $this->outputText($width, self::SMALL_HEIGHT, $text, PdfBorder::NONE, PdfTextAlignment::RIGHT, PdfMove::NEW_LINE);
    }

    private function outputName(float $width, bool $isAddress): void
    {
        $this->applyNameStyle();
        $name = $this->customer?->getName() ?? '';
        $link = $this->customer?->getUrl() ?? '';
        $align = $isAddress ? PdfTextAlignment::LEFT : PdfTextAlignment::RIGHT;
        $border = $isAddress ? PdfBorder::NONE : PdfBorder::BOTTOM;
        $move = $isAddress ? PdfMove::RIGHT : PdfMove::NEW_LINE;
        $this->outputText($width, PdfDocument::LINE_HEIGHT, $name, $border, $align, $move, $link);
    }

    private function outputPhone(float $width): void
    {
        $this->applySmallStyle();
        $text = $this->customer?->getTranslatedPhone($this->parent) ?? '';
        $this->outputText($width, self::SMALL_HEIGHT, $text, PdfBorder::NONE, PdfTextAlignment::RIGHT, PdfMove::NEW_LINE);
    }

    private function outputText(float $width, float $height, ?string $text, string|int $border, PdfTextAlignment $align, PdfMove $move = PdfMove::RIGHT, string $link = ''): void
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
        $border = $isAddress ? PdfBorder::NONE : PdfBorder::BOTTOM;
        $this->outputText($width, PdfDocument::LINE_HEIGHT, $title, $border, $align);
    }

    private function outputZipCity(float $width): void
    {
        $this->applySmallStyle();
        $text = $this->customer?->getZipCity() ?? '';
        $this->outputText($width, self::SMALL_HEIGHT, $text, PdfBorder::BOTTOM, PdfTextAlignment::LEFT);
    }
}
