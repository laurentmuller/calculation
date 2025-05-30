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

use App\Pdf\PdfTable;
use App\Pdf\Traits\PdfCellFormatTrait;
use App\Pdf\Traits\PdfCellTranslatorTrait;
use App\Pdf\Traits\PdfColumnTranslatorTrait;
use App\Report\AbstractReport;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Extends the PdfTable with format and translation capabilities.
 */
class ReportTable extends PdfTable
{
    use PdfCellFormatTrait;
    use PdfCellTranslatorTrait;
    use PdfColumnTranslatorTrait;

    private readonly TranslatorInterface $translator;

    /**
     * @param AbstractReport $parent    the parent report to print in
     * @param bool           $fullWidth a value indicating if the table takes all the printable width
     */
    public function __construct(AbstractReport $parent, bool $fullWidth = true)
    {
        parent::__construct($parent, $fullWidth);
        $this->translator = $parent->getTranslator();
    }

    /**
     * Creates a new instance from the given report.
     *
     * @param AbstractReport $parent    the parent report to print in
     * @param bool           $fullWidth a value indicating if the table takes all the printable width
     */
    public static function fromReport(AbstractReport $parent, bool $fullWidth = true): self
    {
        return new self($parent, $fullWidth);
    }

    #[\Override]
    public function getTranslator(): TranslatorInterface
    {
        return $this->translator;
    }
}
