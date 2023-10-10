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

namespace App\Report;

use App\Pdf\PdfColumn;
use App\Pdf\PdfTableBuilder;
use App\Utils\FormatUtils;

/**
 * Report for the list of global margins.
 *
 * @extends AbstractArrayReport<\App\Entity\GlobalMargin>
 */
class GlobalMarginsReport extends AbstractArrayReport
{
    protected function doRender(array $entities): bool
    {
        $this->setTitleTrans('globalmargin.list.title');
        $this->AddPage();

        $table = PdfTableBuilder::instance($this)
            ->addColumns(
                PdfColumn::right($this->trans('globalmargin.fields.minimum'), 50),
                PdfColumn::right($this->trans('globalmargin.fields.maximum'), 50),
                PdfColumn::right($this->trans('globalmargin.fields.delta'), 50),
                PdfColumn::right($this->trans('globalmargin.fields.margin'), 50)
            )->outputHeaders();

        foreach ($entities as $entity) {
            $table->addRow(
                FormatUtils::formatAmount($entity->getMinimum()),
                FormatUtils::formatAmount($entity->getMaximum()),
                FormatUtils::formatAmount($entity->getDelta()),
                FormatUtils::formatPercent($entity->getMargin())
            );
        }

        return $this->renderCount($table, $entities, 'counters.margins');
    }
}
