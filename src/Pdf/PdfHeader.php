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
class PdfHeader implements PdfDocumentUpdaterInterface, PdfConstantsInterface
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
     * {@inheritDoc}
     */
    public function apply(PdfDocument $doc): void
    {
        // style
        $style = PdfStyle::getDefaultStyle();
        $style->apply($doc);

        // margins
        $margins = $doc->setCellMargin(0);

        // total width
        $printableWidth = $doc->getPrintableWidth();

        //save position
        [$x, $y] = $doc->GetXY();

        if ($this->isPrintAddress()) {
            // name (left) + title(center) + contact(right)
            $cellWidth = $printableWidth / 3;
            $this->outputName($doc, $style, $cellWidth);
            $this->outputTitle($doc, $style, $cellWidth);
            $this->outputContact($doc, $style, $cellWidth);

            // address (left)
            $doc->SetXY($x, $y + self::LINE_HEIGHT);
            $this->outputAddress($doc, $style, $printableWidth);
        } else {
            // title(left) + name(right)
            $cellWidth = $printableWidth / 2;
            $this->outputTitle($doc, $style, $cellWidth);
            $this->outputName($doc, $style, $cellWidth);
            $doc->Ln();
        }

        // description
        $this->outputDescription($doc, $style, $printableWidth);

        // reset
        $doc->setCellMargin($margins);
        $doc->resetStyle()->Ln(3);
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

    private function getAddress(): ?string
    {
        if ($this->isPrintAddress()) {
            return $this->customer->getAddress();
        }

        return null;
    }

    private function getEmail(): ?string
    {
        return null === $this->customer ? null : $this->customer->getEmail();
    }

    private function getFax(PdfDocument $doc): string
    {
        if (null !== $this->customer) {
            $fax = $this->customer->getFax() ?: '';
            if ($doc instanceof AbstractReport) {
                return $doc->trans('report.fax', ['{0}' => $fax]);
            }

            return "F: $fax";
        }

        return '';
    }

    private function getName(): ?string
    {
        return null === $this->customer ? null : $this->customer->getName();
    }

    private function getPhone(PdfDocument $doc): string
    {
        if (null !== $this->customer) {
            $phone = $this->customer->getPhone() ?: '';
            if ($doc instanceof AbstractReport) {
                return $doc->trans('report.phone', ['{0}' => $phone]);
            }

            return "T: $phone";
        }

        return '';
    }

    private function getUrl(): ?string
    {
        return null === $this->customer ? null : $this->customer->getUrl();
    }

    private function isPrintAddress(): bool
    {
        return null !== $this->customer && $this->customer->isPrintAddress() && !empty($this->customer->getAddress());
    }

    private function outputAddress(PdfDocument $doc, PdfStyle $style, float $width): void
    {
        $style->reset()->setFontSize(8)->apply($doc);
        $doc->MultiCell($width, self::LINE_HEIGHT - 1, $this->getAddress(), self::BORDER_BOTTOM, self::ALIGN_LEFT, false);
    }

    private function outputContact(PdfDocument $doc, PdfStyle $style, float $width): void
    {
        $phone = $this->getPhone($doc);
        $fax = $this->getFax($doc);
        $email = $this->getEmail() ?: '';
        $text = "$phone\n$fax\n$email";
        $style->reset()->setFontSize(8)->apply($doc);
        $doc->MultiCell($width, self::LINE_HEIGHT - 1, $text, self::BORDER_NONE, self::ALIGN_RIGHT, false);
    }

    private function outputDescription(PdfDocument $doc, PdfStyle $style, float $width): void
    {
        if (!empty($this->description)) {
            $style->reset()->setFontSize(8)->apply($doc);
            $doc->Cell($width, self::LINE_HEIGHT, $this->description);
            $doc->Ln();
        }
    }

    private function outputName(PdfDocument $doc, PdfStyle $style, float $width): void
    {
        $name = $this->getName() ?: '';
        $address = $this->isPrintAddress();
        $style->setFontBold()->setFontSize(8)->apply($doc);
        $align = $address ? self::ALIGN_LEFT : self::ALIGN_RIGHT;
        $border = $address ? self::BORDER_NONE : self::BORDER_BOTTOM;
        $doc->Cell($width, self::LINE_HEIGHT, $name, $border, self::MOVE_TO_RIGHT, $align, false, $this->getUrl() ?: '');
    }

    private function outputTitle(PdfDocument $doc, PdfStyle $style, float $width): void
    {
        $title = $doc->getTitle() ?: '';
        $address = $this->isPrintAddress();
        $style->setFontBold()->setFontSize(10)->apply($doc);
        $align = $address ? self::ALIGN_CENTER : self::ALIGN_LEFT;
        $border = $address ? self::BORDER_NONE : self::BORDER_BOTTOM;
        $doc->Cell($width, self::LINE_HEIGHT, $title, $border, self::MOVE_TO_RIGHT, $align);
    }
}
