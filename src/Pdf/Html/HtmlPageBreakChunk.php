<?php
/*
 * This file is part of the Calculation package.
 *
 * Copyright (c) 2019 bibi.nu. All rights reserved.
 *
 * This computer code is protected by copyright law and international
 * treaties. Unauthorised reproduction or distribution of this code, or
 * any portion of it, may result in severe civil and criminal penalties,
 * and will be prosecuted to the maximum extent possible under the law.
 */

declare(strict_types=1);

namespace App\Pdf\Html;

use App\Report\HtmlReport;

/**
 * Special chunk to add a page break to report.
 *
 * @author Laurent Muller
 */
class HtmlPageBreakChunk extends HtmlChunk
{
    /**
     * @param string          $name   the tag name
     * @param HtmlParentChunk $parent the parent chunk
     */
    public function __construct(string $name, ?HtmlParentChunk $parent = null)
    {
        parent::__construct($name, $parent);
    }

    /**
     * {@inheritdoc}
     */
    public function output(HtmlReport $report): void
    {
        $report->AddPage();
    }
}
