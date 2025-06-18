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
use App\Pdf\PdfStyle;
use App\Pdf\PdfTable;
use App\Report\Table\ReportTable;
use App\Utils\FormatUtils;
use fpdf\Enums\PdfOrientation;
use fpdf\PdfBorder;

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
        array $entities
    ) {
        parent::__construct($controller, $entities, PdfOrientation::LANDSCAPE);
        $this->setTranslatedTitle('group.list.title');
    }

    #[\Override]
    protected function doRender(array $entities): bool
    {
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
        $this->outputTotals($table, $entities);

        return true;
    }

    private function createTable(): ReportTable
    {
        return ReportTable::fromReport($this)
            ->addColumns(
                $this->leftColumn('group.fields.code', 45, true),
                $this->leftColumn('group.fields.description', 50),
                $this->rightColumn('group.fields.categories', 26, true),
                $this->rightColumn('category.fields.products', 26, true),
                $this->rightColumn('category.fields.tasks', 26, true),
                $this->rightColumn('globalmargin.fields.minimum', 22, true),
                $this->rightColumn('globalmargin.fields.maximum', 22, true),
                $this->rightColumn('globalmargin.fields.margin', 22, true)
            )->outputHeaders();
    }

    private function formatCount(string $id, int $value): string
    {
        return $this->trans($id, ['count' => $value]);
    }

    private function formatInt(int $value): string
    {
        return 0 !== $value ? FormatUtils::formatInt($value) : '';
    }

    /**
     * @param Group[] $entities
     *
     * @return int[]
     */
    private function getTotals(array $entities): array
    {
        return \array_reduce(
            $entities,
            /** @phpstan-param int[] $carry */
            function (array $carry, Group $group): array {
                ++$carry[0];
                $carry[1] += $group->countCategories();
                $carry[2] += $group->countProducts();
                $carry[3] += $group->countTasks();
                $carry[4] += $group->countMargins();

                return $carry;
            },
            [0, 0, 0, 0, 0]
        );
    }

    private function outputGroup(ReportTable $table, Group $group): void
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
                $table->addCellAmount($margin->getMinimum())
                    ->addCellAmount($margin->getMaximum())
                    ->addCellPercent($margin->getMargin())
                    ->endRow();
                $skip = true;
            }
        } else {
            $table->addCellTrans('group.edit.empty_margins', 3)->endRow();
        }
    }

    /**
     * @param Group[] $entities
     */
    private function outputTotals(PdfTable $table, array $entities): void
    {
        $values = $this->getTotals($entities);
        $texts = [
            $this->formatCount('counters.groups', $values[0]),
            $this->formatCount('counters.categories', $values[1]),
            $this->formatCount('counters.products', $values[2]),
            $this->formatCount('counters.tasks', $values[3]),
            $this->formatCount('counters.margins', $values[4]),
        ];

        $table->startHeaderRow()
            ->add($texts[0], 2)
            ->add($texts[1])
            ->add($texts[2])
            ->add($texts[3])
            ->add($texts[4], 3)
            ->endRow();
    }
}
