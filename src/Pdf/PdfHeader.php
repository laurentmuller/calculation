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
use App\Report\AbstractReport;

/**
 * Class to output header in PDF documents.
 *
 * @author Laurent Muller
 */
class PdfHeader implements PdfConstantsInterface
{
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
        // style
        $style = PdfStyle::getDefaultStyle();
        $style->apply($this->parent);

        // margins
        $margins = $this->parent->setCellMargin(0);

        // lines
        $isAddress = $this->isPrintAddress();
        $printableWidth = $this->parent->getPrintableWidth();
        $this->line1($style, $printableWidth, $isAddress);
        if ($isAddress) {
            $this->line2($style, $printableWidth);
            $this->line3($style, $printableWidth);
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

        // update bottom margin
        if ($this->customer->isPrintAddress()) {
        } else {
        }

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

    private function getAddress(): string
    {
        return null === $this->customer ? '' : ($this->customer->getAddress() ?? '');
    }

    private function getEmail(): string
    {
        return null === $this->customer ? '' : ($this->customer->getEmail() ?? '');
    }

    private function getFax(): string
    {
        if (null !== $this->customer) {
            $fax = $this->customer->getFax() ?: '';
            if ($this->parent instanceof AbstractReport) {
                return $this->parent->trans('report.fax', ['{0}' => $fax]);
            }

            return "Fax : $fax";
        }

        return '';
    }

    private function getName(): string
    {
        return null === $this->customer ? '' : ($this->customer->getName() ?? '');
    }

    private function getPhone(): string
    {
        if (null !== $this->customer) {
            $phone = $this->customer->getPhone() ?: '';
            if ($this->parent instanceof AbstractReport) {
                return $this->parent->trans('report.phone', ['{0}' => $phone]);
            }

            return "Tél. : $phone";
        }

        return '';
    }

    private function getUrl(): string
    {
        return null === $this->customer ? '' : ($this->customer->getUrl() ?? '');
    }

    private function getZipCity(): string
    {
        return null === $this->customer ? '' : ($this->customer->getZipCity() ?? '');
    }

    private function isPrintAddress(): bool
    {
        return null !== $this->customer && $this->customer->isPrintAddress();
    }

    private function line1(PdfStyle $style, float $printableWidth, bool $isAddress): void
    {
        if ($isAddress) {
            // name (left) + title (center) + phone (right)
            $cellWidth = $printableWidth / 3;
            $this->outputName($style, $cellWidth, true);
            $this->outputTitle($style, $cellWidth, true);
            $this->outputPhone($style, $cellWidth);
        } else {
            // title (left) + name (right)
            $cellWidth = $printableWidth / 2;
            $this->outputTitle($style, $cellWidth, false);
            $this->outputName($style, $cellWidth, false);

            // description
            $this->outputDescription($style, $printableWidth, false);
        }
    }

    private function line2(PdfStyle $style, float $printableWidth): void
    {
        if (null !== $this->description) {
            // address (left) + description (center) + fax (right)
            $cellWidth = $printableWidth / 3;
            $this->outputAddress($style, $cellWidth);
            $this->outputDescription($style, $cellWidth, true);
            $this->outputFax($style, $cellWidth);
        } else {
            // address (left) + fax (right)
            $cellWidth = $printableWidth / 2;
            $this->outputAddress($style, $cellWidth);
            $this->outputFax($style, $cellWidth);
        }
    }

    private function line3(PdfStyle $style, float $printableWidth): void
    {
        // zip city (left) + e-mail (right)
        $cellWidth = $printableWidth / 2;
        $this->outputZipCity($style, $cellWidth);
        $this->outputEmail($style, $cellWidth);
    }

    private function outputAddress(PdfStyle $style, float $width): void
    {
        $text = $this->getAddress();
        $style->reset()->setFontSize(8)->apply($this->parent);
        $this->parent->Cell($width, self::LINE_HEIGHT - 1, $text, self::BORDER_NONE, self::MOVE_TO_RIGHT, self::ALIGN_LEFT);
    }

    private function outputDescription(PdfStyle $style, float $width, bool $isAddress): void
    {
        if (!empty($this->description)) {
            $style->reset()->setFontSize(8)->apply($this->parent);
            $align = $isAddress ? self::ALIGN_CENTER : self::ALIGN_LEFT;
            $move = $isAddress ? self::MOVE_TO_RIGHT : self::MOVE_TO_NEW_LINE;
            $this->parent->Cell($width, self::LINE_HEIGHT, $this->description, self::BORDER_NONE, $move, $align);
        }
    }

    private function outputEmail(PdfStyle $style, float $width): void
    {
        $text = $this->getEmail();
        $link = empty($text) ? '' : "mailto:$text";
        $style->reset()->setFontSize(8)->apply($this->parent);
        $this->parent->Cell($width, self::LINE_HEIGHT - 1, $text, self::BORDER_BOTTOM, self::MOVE_TO_NEW_LINE, self::ALIGN_RIGHT, false, $link);
    }

    private function outputFax(PdfStyle $style, float $width): void
    {
        $text = $this->getFax();
        $style->reset()->setFontSize(8)->apply($this->parent);
        $this->parent->Cell($width, self::LINE_HEIGHT - 1, $text, self::BORDER_NONE, self::MOVE_TO_NEW_LINE, self::ALIGN_RIGHT);
    }

    private function outputName(PdfStyle $style, float $width, bool $isAddress): void
    {
        $name = $this->getName();
        $style->setFontBold()->setFontSize(8)->apply($this->parent);
        $align = $isAddress ? self::ALIGN_LEFT : self::ALIGN_RIGHT;
        $border = $isAddress ? self::BORDER_NONE : self::BORDER_BOTTOM;
        $move = $isAddress ? self::MOVE_TO_RIGHT : self::MOVE_TO_NEW_LINE;
        $this->parent->Cell($width, self::LINE_HEIGHT, $name, $border, $move, $align, false, $this->getUrl() ?: '');
    }

    private function outputPhone(PdfStyle $style, float $width): void
    {
        $text = $this->getPhone();
        $style->reset()->setFontSize(8)->apply($this->parent);
        $this->parent->Cell($width, self::LINE_HEIGHT - 1, $text, self::BORDER_NONE, self::MOVE_TO_NEW_LINE, self::ALIGN_RIGHT);
    }

    private function outputTitle(PdfStyle $style, float $width, bool $isAddress): void
    {
        $title = $this->parent->getTitle() ?: '';
        $style->setFontBold()->setFontSize(10)->apply($this->parent);
        $align = $isAddress ? self::ALIGN_CENTER : self::ALIGN_LEFT;
        $border = $isAddress ? self::BORDER_NONE : self::BORDER_BOTTOM;
        $this->parent->Cell($width, self::LINE_HEIGHT, $title, $border, self::MOVE_TO_RIGHT, $align);
    }

    private function outputZipCity(PdfStyle $style, float $width): void
    {
        $text = $this->getZipCity();
        $style->reset()->setFontSize(8)->apply($this->parent);
        $this->parent->Cell($width, self::LINE_HEIGHT - 1, $text, self::BORDER_BOTTOM, self::MOVE_TO_RIGHT, self::ALIGN_LEFT);
    }
}
