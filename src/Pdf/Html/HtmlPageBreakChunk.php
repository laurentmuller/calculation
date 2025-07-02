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

namespace App\Pdf\Html;

use App\Report\HtmlReport;

/**
 * A special chunk to add a page break to the report.
 */
class HtmlPageBreakChunk extends AbstractHtmlChunk
{
    public function __construct(?HtmlParentChunk $parent = null, ?string $className = null)
    {
        parent::__construct(HtmlTag::PAGE_BREAK, $parent, $className);
    }

    #[\Override]
    public function output(HtmlReport $report): void
    {
        $report->addPage();
    }
}
