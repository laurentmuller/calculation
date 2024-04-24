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

use App\Controller\AbstractController;
use App\Entity\Group;
use App\Pdf\PdfColumn;
use App\Pdf\PdfStyle;
use App\Pdf\PdfTable;
use App\Utils\FormatUtils;
use fpdf\PdfBorder;
use fpdf\PdfOrientation;
use fpdf\PdfPageSize;
use fpdf\PdfUnit;

/**
 * Report for the list of groups.
 *
 * @extends AbstractArrayReport<Group>
 */
class GroupsReport extends AbstractArrayReport
{
    /**
     * @param Group[] $entities
     */
    public function __construct(
        AbstractController $controller,
        array $entities,
        PdfUnit $unit = PdfUnit::MILLIMETER,
        PdfPageSize $size = PdfPageSize::A4
    ) {
        parent::__construct($controller, $entities, PdfOrientation::LANDSCAPE, $unit, $size);
    }

    protected function doRender(array $entities): bool
    {
        $this->setTitleTrans('group.list.title', [], true);

        $this->addPage();
        $table = $this->createTable();
        $last = \end($entities);
        $emptyStyle = PdfStyle::getCellStyle()->setBorder(PdfBorder::leftRight());
        foreach ($entities as $entity) {
            $this->outputGroup($table, $entity);
            if ($entity !== $last) {
                $table->singleLine(null, $emptyStyle);
            }
        }
        $this->renderTotal($table, $entities);

        return true;
    }

    private function createTable(): PdfTable
    {
        return PdfTable::instance($this)
            ->addColumns(
                PdfColumn::left($this->trans('group.fields.code'), 40, true),
                PdfColumn::left($this->trans('group.fields.description'), 50),
                PdfColumn::right($this->trans('group.fields.categories'), 26, true),
                PdfColumn::right($this->trans('category.fields.products'), 26, true),
                PdfColumn::right($this->trans('category.fields.tasks'), 26, true),
                PdfColumn::right($this->trans('globalmargin.fields.minimum'), 22, true),
                PdfColumn::right($this->trans('globalmargin.fields.maximum'), 22, true),
                PdfColumn::right($this->trans('globalmargin.fields.delta'), 22, true),
                PdfColumn::right($this->trans('globalmargin.fields.margin'), 22, true)
            )->outputHeaders();
    }

    private function formatCount(string $id, array|int $value): string
    {
        return $this->trans($id, ['count' => \is_array($value) ? \count($value) : $value]);
    }

    private function formatInt(int $value): string
    {
        return 0 === $value ? '' : FormatUtils::formatInt($value);
    }

    private function outputGroup(PdfTable $table, Group $group): void
    {
        $emptyValue = \array_fill(0, 5, '');
        $table->startRow()
            ->add($group->getCode())
            ->add($group->getDescription())
            ->add($this->formatInt($group->countCategories()))
            ->add($this->formatInt($group->countProducts()))
            ->add($this->formatInt($group->countTasks()));
        if ($group->hasMargins()) {
            $skip = false;
            $margins = $group->getMargins();
            foreach ($margins as $margin) {
                if ($skip) {
                    $table->startRow()
                        ->addValues(...$emptyValue);
                }
                $table->addAmount($margin->getMinimum())
                    ->addAmount($margin->getMaximum())
                    ->addAmount($margin->getDelta())
                    ->addPercent($margin->getMargin())
                    ->endRow();
                $skip = true;
            }
        } else {
            $empty = $this->trans('group.edit.empty_margins');
            $table->add($empty, 4)->endRow();
        }
    }

    /**
     * @param Group[] $entities
     */
    private function renderTotal(PdfTable $table, array $entities): void
    {
        $margins = 0;
        $categories = 0;
        $products = 0;
        $tasks = 0;

        foreach ($entities as $entity) {
            $margins += $entity->countMargins();
            $categories += $entity->countCategories();
            $products += $entity->countProducts();
            $tasks += $entity->countTasks();
        }

        $txtEntities = $this->formatCount('counters.groups', $entities);
        $txtCategories = $this->formatCount('counters.categories', $categories);
        $txtProducts = $this->formatCount('counters.products', $products);
        $txtTasks = $this->formatCount('counters.tasks', $tasks);
        $txtMargins = $this->formatCount('counters.margins', $margins);

        $table->startHeaderRow()
            ->add($txtEntities, 2)
            ->add($txtCategories)
            ->add($txtProducts)
            ->add($txtTasks)
            ->add($txtMargins, 4)
            ->endRow();
    }
}
