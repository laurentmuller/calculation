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

/**
 * Class to output header in PDF documents.
 *
 * @author Laurent Muller
 */
class PdfHeader implements PdfDocumentUpdaterInterface, PdfConstantsInterface
{
    /**
     * The company address.
     */
    protected ?string $companyAddress = null;

    /**
     * The company name.
     */
    protected ?string $companyName = null;

    /**
     * The company web site.
     */
    protected ?string $companyUrl = null;

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

        // get values
        $title = $doc->getTitle() ?: '';
        $company = $this->getCompanyName() ?: '';
        $address = $this->getCompanyAddress() ?: '';
        $url = $this->getCompanyUrl() ?: '';
        $printableWidth = $doc->getPrintableWidth();

        // title or company?
        if (!empty($title) || !empty($company)) {
            // cells width and border
            $cellWidth = $printableWidth / 2;
            $border = empty($address) ? self::BORDER_BOTTOM : self::BORDER_NONE;

            // title
            $style->setFontBold()->setFontSize(10)->apply($doc);
            $doc->Cell($cellWidth, self::LINE_HEIGHT, $title, $border, self::MOVE_TO_RIGHT, self::ALIGN_LEFT);

            // company name and web site
            $style->setFontBold()->setFontSize(8)->apply($doc);
            $doc->Cell($cellWidth, self::LINE_HEIGHT, $company, $border, self::MOVE_TO_RIGHT, self::ALIGN_RIGHT, false, $url);
            $doc->Ln();

            // company address
            if (!empty($address)) {
                $style->setFontRegular()->apply($doc);
                $doc->MultiCell($printableWidth, self::LINE_HEIGHT - 1, $address, self::BORDER_BOTTOM, self::ALIGN_RIGHT, false);
            }
        }

        // description
        $description = $this->getDescription() ?: '';
        if (!empty($description)) {
            $style->reset()->setFontSize(8)->apply($doc);
            $doc->Cell($printableWidth, self::LINE_HEIGHT, $description);
            $doc->Ln();
        }

        // reset
        $doc->setCellMargin($margins);
        $doc->resetStyle()->Ln(3);
    }

    /**
     * Gets the company address.
     */
    public function getCompanyAddress(): ?string
    {
        return $this->companyAddress;
    }

    /**
     * Gets the company name.
     */
    public function getCompanyName(): ?string
    {
        return $this->companyName;
    }

    /**
     * Gets the company web site.
     */
    public function getCompanyUrl(): ?string
    {
        return $this->companyUrl;
    }

    /**
     * Gets the document description.
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * Sets the company address.
     */
    public function setCompanyAddress(?string $companyAddress): self
    {
        $this->companyAddress = $companyAddress;

        return $this;
    }

    /**
     * Sets the company name.
     */
    public function setCompanyName(?string $companyName): self
    {
        $this->companyName = $companyName;

        return $this;
    }

    /**
     * Sets the company web site.
     */
    public function setCompanyUrl(?string $companyUrl): self
    {
        $this->companyUrl = $companyUrl;

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
}
