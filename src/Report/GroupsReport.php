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
use App\Pdf\Enums\PdfDocumentOrientation;
use App\Pdf\Enums\PdfDocumentSize;
use App\Pdf\Enums\PdfDocumentUnit;
use App\Pdf\PdfBorder;
use App\Pdf\PdfColumn;
use App\Pdf\PdfStyle;
use App\Pdf\PdfTableBuilder;
use App\Utils\FormatUtils;

/**
 * Report for the list of groups.
 *
 * @extends AbstractArrayReport<Group>
 */
class GroupsReport extends AbstractArrayReport
{
    /**
     * @psalm-param Group[] $entities
     */
    public function __construct(AbstractController $controller, array $entities, PdfDocumentUnit $unit = PdfDocumentUnit::MILLIMETER, PdfDocumentSize $size = PdfDocumentSize::A4)
    {
        parent::__construct($controller, $entities, PdfDocumentOrientation::LANDSCAPE, $unit, $size);
    }

    /**
     * @param Group[] $entities
     */
    protected function doRender(array $entities): bool
    {
        $this->setTitleTrans('group.list.title', [], true);

        $this->AddPage();
        $table = $this->createTable();
        $last = \end($entities);
        $emptyStyle = PdfStyle::getCellStyle()->setBorder(PdfBorder::LEFT . PdfBorder::RIGHT);
        foreach ($entities as $entity) {
            $this->outputGroup($table, $entity);
            if ($entity !== $last) {
                $table->singleLine(null, $emptyStyle);
            }
        }
        $this->renderTotal($table, $entities);

        return true;
    }

    private function createTable(): PdfTableBuilder
    {
        return PdfTableBuilder::instance($this)
            ->addColumns(
                PdfColumn::left($this->trans('group.fields.code'), 40, true),
                PdfColumn::left($this->trans('group.fields.description'), 50),
                PdfColumn::right($this->trans('group.fields.categories'), 26, true),
                PdfColumn::right($this->trans('category.fields.products'), 26, true),
                PdfColumn::right($this->trans('category.fields.tasks'), 26, true),
                PdfColumn::right($this->trans('groupmargin.fields.minimum'), 22, true),
                PdfColumn::right($this->trans('groupmargin.fields.maximum'), 22, true),
                PdfColumn::right($this->trans('groupmargin.fields.margin'), 22, true)
            )->outputHeaders();
    }

    private function formatCount(string $id, array|int $value): string
    {
        return $this->trans($id, ['count' => \is_array($value) ? \count($value) : $value]);
    }

    private function outputGroup(PdfTableBuilder $table, Group $group): void
    {
        $emptyValue = \array_fill(0, 5, '');
        $table->startRow()
            ->add($group->getCode())
            ->add($group->getDescription())
            ->add(FormatUtils::formatInt($group->countCategories()))
            ->add(FormatUtils::formatInt($group->countProducts()))
            ->add(FormatUtils::formatInt($group->countTasks()));
        if ($group->hasMargins()) {
            $skip = false;
            $margins = $group->getMargins();
            foreach ($margins as $margin) {
                if ($skip) {
                    $table->startRow()
                        ->addValues(...$emptyValue);
                }
                $table->add(FormatUtils::formatAmount($margin->getMinimum()))
                    ->add(FormatUtils::formatAmount($margin->getMaximum()))
                    ->add(FormatUtils::formatPercent($margin->getMargin()))
                    ->endRow();
                $skip = true;
            }
        } else {
            $empty = $this->trans('group.edit.empty_margins');
            $table->add($empty, 3)->endRow();
        }
    }

    /**
     * @param Group[] $entities
     */
    private function renderTotal(PdfTableBuilder $table, array $entities): void
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
            ->add($txtMargins, 3)
            ->endRow();
    }
}
