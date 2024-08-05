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

namespace App\Report\Table;

use App\Pdf\PdfGroupTable;
use App\Pdf\Traits\PdfCellFormatTrait;
use App\Pdf\Traits\PdfCellTranslatorTrait;
use App\Pdf\Traits\PdfColumnTranslatorTrait;
use App\Report\AbstractReport;
use fpdf\PdfDocument;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Extends the PdfGroupTable with format and translation capabilities.
 */
class ReportGroupTable extends PdfGroupTable
{
    use PdfCellFormatTrait;
    use PdfCellTranslatorTrait;
    use PdfColumnTranslatorTrait;

    /**
     * @param PdfDocument         $parent     the parent document to print in
     * @param TranslatorInterface $translator the translator used to translate cells
     * @param bool                $fullWidth  a value indicating if the table takes all the printable width
     */
    public function __construct(
        PdfDocument $parent,
        private readonly TranslatorInterface $translator,
        bool $fullWidth = true
    ) {
        parent::__construct($parent, $fullWidth);
    }

    /**
     * Creates a new instance from the given report.
     *
     * @param AbstractReport $parent    the parent report to print in
     * @param bool           $fullWidth a value indicating if the table takes all the printable width
     */
    public static function fromReport(
        AbstractReport $parent,
        bool $fullWidth = true
    ): self {
        return new self($parent, $parent->getTranslator(), $fullWidth);
    }

    public function getTranslator(): TranslatorInterface
    {
        return $this->translator;
    }
}
