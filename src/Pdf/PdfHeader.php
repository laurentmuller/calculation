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
     *  The default font size.
     */
    private const NORMAL_FONT_SIZE = 8;

    /**
     * The line height for customer address and contact.
     */
    private const SMALL_HEIGHT = PdfDocument::LINE_HEIGHT - 1.0;

    /**
     * The title font size.
     */
    private const TITLE_FONT_SIZE = 10;

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
            $height += 7.0;
        }
        if (!StringUtils::isString($this->description)) {
            return $height;
        }

        $parent = $this->parent;
        $width = $parent->getPrintableWidth();
        $parent->SetFontSize(self::NORMAL_FONT_SIZE);
        $lines = $parent->getLinesCount($this->description, $width, 0.0);
        $height += self::SMALL_HEIGHT * (float) $lines;
        $parent->resetStyle();

        return $height;
    }

    /**
     * Output this content to the parent document.
     */
    public function output(): void
    {
        $parent = $this->parent;
        $isAddress = $this->isPrintAddress();
        $printableWidth = $parent->getPrintableWidth();
        $parent->useCellMargin(function () use ($isAddress, $printableWidth): void {
            $this->outputLine1($printableWidth, $isAddress);
            $this->outputLine2($printableWidth, $isAddress);
            $this->outputLine3($printableWidth, $isAddress);
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
            $this->nameStyle = PdfStyle::default()
                ->setFontSize(self::NORMAL_FONT_SIZE)
                ->setFontBold();
        }
        $this->nameStyle->apply($this->parent);
    }

    private function applySmallStyle(): void
    {
        if (!$this->smallStyle instanceof PdfStyle) {
            $this->smallStyle = PdfStyle::default()
                ->setFontSize(self::NORMAL_FONT_SIZE);
        }
        $this->smallStyle->apply($this->parent);
    }

    private function applyTitleStyle(): void
    {
        if (!$this->titleStyle instanceof PdfStyle) {
            $this->titleStyle = PdfStyle::default()
                ->setFontSize(self::TITLE_FONT_SIZE)
                ->setFontBold();
        }
        $this->titleStyle->apply($this->parent);
    }

    private function isPrintAddress(): bool
    {
        return $this->customer instanceof CustomerInformation && $this->customer->isPrintAddress();
    }

    private function outputAddress(float $width): void
    {
        $this->applySmallStyle();
        $text = $this->customer?->getAddress();
        $this->outputText(
            $width,
            self::SMALL_HEIGHT,
            $text,
            PdfBorder::NONE,
            PdfTextAlignment::LEFT
        );
    }

    private function outputDescription(): void
    {
        $description = $this->description ?? '';
        if ('' === $description) {
            return;
        }
        $this->applySmallStyle();
        $this->parent->MultiCell(
            h: self::SMALL_HEIGHT,
            txt: $description,
            align: PdfTextAlignment::LEFT
        );
    }

    private function outputEmail(float $width): void
    {
        $this->applySmallStyle();
        $text = $this->customer?->getEmail() ?? '';
        $link = '' === $text ? '' : "mailto:$text";
        $this->outputText(
            $width,
            self::SMALL_HEIGHT,
            $text,
            PdfBorder::BOTTOM,
            PdfTextAlignment::RIGHT,
            PdfMove::NEW_LINE,
            $link
        );
    }

    private function outputFax(float $width): void
    {
        $this->applySmallStyle();
        $text = $this->customer?->getTranslatedFax($this->parent);
        $this->outputText(
            $width,
            self::SMALL_HEIGHT,
            $text,
            PdfBorder::NONE,
            PdfTextAlignment::RIGHT,
            PdfMove::NEW_LINE
        );
    }

    private function outputLine1(float $printableWidth, bool $isAddress): void
    {
        if ($isAddress) {
            // customer name + title + phone
            $cellWidth = $printableWidth / 3.0;
            $this->outputName($cellWidth, true);
            $this->outputTitle($cellWidth, true);
            $this->outputPhone($cellWidth);
        } else {
            // title + customer name
            $cellWidth = $printableWidth / 2.0;
            $this->outputTitle($cellWidth, false);
            $this->outputName($cellWidth, false);
        }
    }

    private function outputLine2(float $printableWidth, bool $isAddress): void
    {
        if (!$isAddress) {
            return;
        }
        $cellWidth = $printableWidth / 2.0;
        $this->outputAddress($cellWidth);
        $this->outputFax($cellWidth);
    }

    private function outputLine3(float $printableWidth, bool $isAddress): void
    {
        if (!$isAddress) {
            return;
        }
        $cellWidth = $printableWidth / 2.0;
        $this->outputZipCity($cellWidth);
        $this->outputEmail($cellWidth);
    }

    private function outputName(float $width, bool $isAddress): void
    {
        $this->applyNameStyle();
        $name = $this->customer?->getName();
        $link = $this->customer?->getUrl() ?? '';
        $align = $isAddress ? PdfTextAlignment::LEFT : PdfTextAlignment::RIGHT;
        $border = $isAddress ? PdfBorder::NONE : PdfBorder::BOTTOM;
        $move = $isAddress ? PdfMove::RIGHT : PdfMove::NEW_LINE;
        $this->outputText(
            $width,
            PdfDocument::LINE_HEIGHT,
            $name,
            $border,
            $align,
            $move,
            $link
        );
    }

    private function outputPhone(float $width): void
    {
        $this->applySmallStyle();
        $text = $this->customer?->getTranslatedPhone($this->parent);
        $this->outputText(
            $width,
            self::SMALL_HEIGHT,
            $text,
            PdfBorder::NONE,
            PdfTextAlignment::RIGHT,
            PdfMove::NEW_LINE
        );
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
        $title = $this->parent->getTitle();
        $align = $isAddress ? PdfTextAlignment::CENTER : PdfTextAlignment::LEFT;
        $border = $isAddress ? PdfBorder::NONE : PdfBorder::BOTTOM;
        $this->outputText(
            $width,
            PdfDocument::LINE_HEIGHT,
            $title,
            $border,
            $align
        );
    }

    private function outputZipCity(float $width): void
    {
        $this->applySmallStyle();
        $text = $this->customer?->getZipCity();
        $this->outputText(
            $width,
            self::SMALL_HEIGHT,
            $text,
            PdfBorder::BOTTOM,
            PdfTextAlignment::LEFT
        );
    }
}
