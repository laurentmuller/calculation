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

use App\Pdf\Colors\PdfTextColor;
use App\Pdf\PdfColumn;
use App\Pdf\PdfException;
use App\Pdf\PdfGroupTable;
use App\Pdf\PdfStyle;
use App\Utils\FormatUtils;

/**
 * Report for the list of products.
 *
 * @extends AbstractArrayReport<\App\Entity\Product>
 */
class ProductsReport extends AbstractArrayReport
{
    /**
     * @throws PdfException
     */
    protected function doRender(array $entities): bool
    {
        $this->setTitleTrans('product.list.title');
        $this->AddPage();
        $table = $this->createTable();
        $emptyStyle = PdfStyle::getCellStyle()
            ->setTextColor(PdfTextColor::red());

        $groupCode = '';
        $categoryCode = '';
        foreach ($entities as $entity) {
            if ($groupCode !== $entity->getGroupCode()) {
                $groupCode = $entity->getGroupCode();
                $this->addBookmark($groupCode, true);
            }
            if ($categoryCode !== $entity->getCategoryCode()) {
                $categoryCode = $entity->getCategoryCode();
                $this->addBookmark($categoryCode, true, 1);
            }
            $key = \sprintf('%s / %s', $groupCode, $categoryCode);
            $table->setGroupKey($key);
            $style = empty($entity->getPrice()) ? $emptyStyle : null;
            $table->startRow()
                ->add($entity->getDescription())
                ->add(text: FormatUtils::formatAmount($entity->getPrice()), style: $style)
                ->add($entity->getUnit())
                ->add($entity->getSupplier())
                ->endRow();
        }
        $this->renderCount($table, $entities, 'counters.products');
        $this->addPageIndex();

        return true;
    }

    /**
     * Creates the table.
     */
    private function createTable(): PdfGroupTable
    {
        return PdfGroupTable::instance($this)
            ->addColumns(
                PdfColumn::left($this->trans('product.fields.description'), 90),
                PdfColumn::right($this->trans('product.fields.price'), 20, true),
                PdfColumn::left($this->trans('product.fields.unit'), 20, true),
                PdfColumn::left($this->trans('product.fields.supplier'), 45, true)
            )->outputHeaders();
    }
}
