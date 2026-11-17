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

namespace App\Pdf\QrCode;

use Endroid\QrCode\Matrix\MatrixInterface;
use Endroid\QrCode\Writer\Result\AbstractResult;
use fpdf\Enums\PdfDestination;
use fpdf\PdfDocument;

final class PdfDocumentResult extends AbstractResult
{
    public function __construct(
        MatrixInterface $matrix,
        private readonly PdfDocument $document,
    ) {
        parent::__construct($matrix);
    }

    #[\Override]
    public function getMimeType(): string
    {
        return 'application/pdf';
    }

    public function getPdfDocument(): PdfDocument
    {
        return $this->document;
    }

    #[\Override]
    public function getString(): string
    {
        return $this->document->output(PdfDestination::STRING);
    }
}
