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
 * Class to output footer in PDF documents.
 *
 * @author Laurent Muller
 */
class PdfFooter implements PdfDocumentUpdaterInterface, PdfConstantsInterface
{
    /**
     * The application name.
     */
    protected ?string $applicationName = null;

    /**
     * The owner URL.
     */
    protected ?string $ownerUrl = null;

    /**
     * {@inheritDoc}
     */
    public function apply(PdfDocument $doc): void
    {
        // font
        $style = PdfStyle::getDefaultStyle()->setFontSize(8);
        $style->apply($doc);

        // margins
        $margins = $doc->setCellMargin(0);

        // position and cells width
        $doc->SetY(PdfDocument::FOOTER_OFFSET);
        $cellWidth = $doc->getPrintableWidth() / 3;

        // pages
        $text = 'Page ' . $doc->PageNo() . ' / {nb}';
        $doc->Cell($cellWidth, self::LINE_HEIGHT, $text, self::BORDER_TOP, self::MOVE_TO_RIGHT, self::ALIGN_LEFT);

        // program and version and owner link (if any)
        $text = $this->applicationName ?: '';
        $doc->Cell($cellWidth, self::LINE_HEIGHT, $text, self::BORDER_TOP, self::MOVE_TO_RIGHT, self::ALIGN_CENTER, false, $this->ownerUrl);

        // date
        $text = \date('d.m.Y - H:i');
        $doc->Cell($cellWidth, self::LINE_HEIGHT, $text, self::BORDER_TOP, self::MOVE_TO_RIGHT, self::ALIGN_RIGHT);

        // reset
        $doc->setCellMargin($margins);
        $doc->resetStyle();
    }

    /**
     * Gets the application name.
     */
    public function getApplicationName(): ?string
    {
        return $this->applicationName;
    }

    /**
     * Gets the owner URL.
     */
    public function getOwnerUrl(): ?string
    {
        return $this->ownerUrl;
    }

    /**
     * Sets the application name.
     */
    public function setApplicationName(?string $applicationName): self
    {
        $this->applicationName = $applicationName;

        return $this;
    }

    /**
     * Sets the owner URL.
     */
    public function setOwnerUrl(?string $ownerUrl): self
    {
        $this->ownerUrl = $ownerUrl;

        return $this;
    }
}
