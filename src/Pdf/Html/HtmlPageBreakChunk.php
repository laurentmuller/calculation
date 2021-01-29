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

namespace App\Pdf\Html;

use App\Report\HtmlReport;

/**
 * Special chunk to add a page break to report.
 *
 * @author Laurent Muller
 */
class HtmlPageBreakChunk extends AbstractHtmlChunk
{
    /**
     * {@inheritdoc}
     */
    public function output(HtmlReport $report): void
    {
        $report->AddPage();
    }
}
