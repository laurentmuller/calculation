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
use App\Pdf\PdfGroupTableBuilder;
use App\Pdf\PdfStyle;
use App\Pdf\PdfTextColor;
use App\Util\FormatUtils;

/**
 * Report for the list of products.
 *
 * @extends AbstractArrayReport<\App\Entity\Product>
 */
class ProductsReport extends AbstractArrayReport
{
    /**
     * {@inheritdoc}
     */
    protected function doRender(array $entities): bool
    {
        $this->setTitleTrans('product.list.title');
        $this->AddPage();
        $table = $this->createTable();
        $emptyStyle = PdfStyle::getCellStyle()
            ->setTextColor(PdfTextColor::red());
        $groupCode = null;
        $categoryCode = null;
        foreach ($entities as $entity) {
            if ($groupCode !== $entity->getGroupCode()) {
                $this->addBookmark((string) $entity->getGroupCode(), true);
                $groupCode = $entity->getGroupCode();
            }
            if ($categoryCode !== $entity->getCategoryCode()) {
                $this->addBookmark((string) $entity->getCategoryCode(), true, 1);
                $categoryCode = $entity->getCategoryCode();
            }
            $key = \sprintf('%s / %s', (string) $groupCode, (string) $categoryCode);
            $table->setGroupKey($key);
            $style = empty($entity->getPrice()) ? $emptyStyle : null;
            $table->startRow()
                ->add($entity->getDescription())
                ->add(text: FormatUtils::formatAmount($entity->getPrice()), style: $style)
                ->add($entity->getUnit())
                ->add($entity->getSupplier())
                ->endRow();
        }
        $this->renderCount($entities, 'counters.products');
        $this->addPageIndex();

        return true;
    }

    /**
     * Creates the table.
     */
    private function createTable(): PdfGroupTableBuilder
    {
        return PdfGroupTableBuilder::instance($this)
            ->addColumns(
                PdfColumn::left($this->trans('product.fields.description'), 90),
                PdfColumn::right($this->trans('product.fields.price'), 20, true),
                PdfColumn::left($this->trans('product.fields.unit'), 20, true),
                PdfColumn::left($this->trans('product.fields.supplier'), 45, true)
            )->outputHeaders();
    }
}
