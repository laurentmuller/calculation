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

namespace App\Report;

use App\Entity\GlobalMargin;
use App\Pdf\PdfColumn;
use App\Pdf\PdfTableBuilder;
use App\Util\FormatUtils;

/**
 * Report for the list of global margins.
 *
 * @author Laurent Muller
 */
class GlobalMarginsReport extends AbstractArrayReport
{
    /**
     * {@inheritdoc}
     */
    protected function doRender(array $entities): bool
    {
        // title
        $this->setTitleTrans('globalmargin.list.title');

        // new page
        $this->AddPage();

        // table
        $table = new PdfTableBuilder($this);
        $table->addColumn(PdfColumn::right($this->trans('globalmargin.fields.minimum'), 50))
            ->addColumn(PdfColumn::right($this->trans('globalmargin.fields.maximum'), 50))
            ->addColumn(PdfColumn::right($this->trans('globalmargin.fields.margin'), 50))
            ->outputHeaders();

        /** @var GlobalMargin $entity */
        foreach ($entities as $entity) {
            $table->startRow()
                ->add(FormatUtils::formatAmount($entity->getMinimum()))
                ->add(FormatUtils::formatAmount($entity->getMaximum()))
                ->add(FormatUtils::formatPercent($entity->getMargin()))
                ->endRow();
        }

        // count
        return $this->renderCount($entities);
    }
}
