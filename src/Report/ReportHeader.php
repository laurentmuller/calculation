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

namespace App\Report;

use App\Model\CustomerInformation;
use App\Pdf\PdfFont;
use App\Pdf\PdfStyle;
use App\Utils\StringUtils;
use fpdf\Enums\PdfMove;
use fpdf\Enums\PdfTextAlignment;
use fpdf\PdfBorder;
use fpdf\PdfDocument;

/**
 * Class to output header in PDF documents.
 */
class ReportHeader
{
    /**
     *  The default font size.
     */
    private const DEFAULT_FONT_SIZE = PdfFont::DEFAULT_SIZE - 1.0;

    /**
     * The default line height.
     */
    private const LINE_HEIGHT = PdfDocument::LINE_HEIGHT;

    /**
     * The line height for the customer address.
     */
    private const SMALL_HEIGHT = PdfDocument::LINE_HEIGHT - 1.0;

    /**
     * The title font size.
     */
    private const TITLE_FONT_SIZE = PdfFont::DEFAULT_SIZE + 1.0;

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

    public function __construct(private readonly AbstractReport $parent)
    {
    }

    /**
     * Gets the required height.
     */
    public function getHeight(): float
    {
        $height = self::LINE_HEIGHT;
        if ($this->isPrintAddress()) {
            $height += 7.0;
        }
        if (!StringUtils::isString($this->description)) {
            return $height;
        }

        $parent = $this->parent;
        $width = $parent->getPrintableWidth();
        $parent->setFontSizeInPoint(self::DEFAULT_FONT_SIZE);
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
        $this->parent->useCellMargin(fn () => $this->outputLines());
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
                ->setFontSize(self::DEFAULT_FONT_SIZE)
                ->setFontBold();
        }
        $this->nameStyle->apply($this->parent);
    }

    private function applySmallStyle(): void
    {
        if (!$this->smallStyle instanceof PdfStyle) {
            $this->smallStyle = PdfStyle::default()
                ->setFontSize(self::DEFAULT_FONT_SIZE);
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
        $this->outputText(
            $width,
            self::SMALL_HEIGHT,
            $this->customer?->getAddress(),
            PdfBorder::none(),
            PdfTextAlignment::RIGHT,
            PdfMove::NEW_LINE
        );
    }

    private function outputDescription(): void
    {
        $description = $this->description ?? '';
        if ('' === $description) {
            return;
        }
        $this->applySmallStyle();
        $this->parent->multiCell(height: self::SMALL_HEIGHT, text: $description, align: PdfTextAlignment::LEFT);
    }

    private function outputLines(): void
    {
        $parent = $this->parent;
        $isAddress = $this->isPrintAddress();
        $printableWidth = $parent->getPrintableWidth();
        $this->outputTitleAndName($printableWidth, $isAddress);
        if ($isAddress) {
            $this->outputAddress($printableWidth);
            $this->outputZipCity($printableWidth);
        }
        $this->outputDescription();
        $parent->resetStyle()->lineBreak(2);
    }

    private function outputName(float $width, bool $isAddress): void
    {
        $this->applyNameStyle();
        $border = $isAddress ? PdfBorder::none() : PdfBorder::bottom();
        $move = $isAddress ? PdfMove::NEW_LINE : PdfMove::BELOW;
        $this->outputText(
            $width,
            self::LINE_HEIGHT,
            $this->customer?->getName(),
            $border,
            PdfTextAlignment::RIGHT,
            $move,
            $this->customer?->getUrl()
        );
    }

    private function outputText(
        float $width,
        float $height,
        ?string $text,
        PdfBorder $border,
        PdfTextAlignment $align,
        PdfMove $move = PdfMove::RIGHT,
        string|int|null $link = null
    ): void {
        $this->parent->cell(
            width: $width,
            height: $height,
            text: $text ?? '',
            border: $border,
            move: $move,
            align: $align,
            link: $link
        );
    }

    private function outputTitle(float $width, bool $isAddress): void
    {
        $this->applyTitleStyle();
        $border = $isAddress ? PdfBorder::none() : PdfBorder::bottom();
        $this->outputText(
            $width,
            self::LINE_HEIGHT,
            $this->parent->getTitle(),
            $border,
            PdfTextAlignment::LEFT,
        );
    }

    private function outputTitleAndName(float $printableWidth, bool $isAddress): void
    {
        // title + customer name
        $cellWidth = $printableWidth / 2.0;
        $this->outputTitle($cellWidth, $isAddress);
        $this->outputName($cellWidth, $isAddress);
    }

    private function outputZipCity(float $width): void
    {
        $this->applySmallStyle();
        $this->outputText(
            $width,
            self::SMALL_HEIGHT,
            $this->customer?->getZipCity(),
            PdfBorder::bottom(),
            PdfTextAlignment::RIGHT,
            PdfMove::NEW_LINE
        );
    }
}
