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

namespace App\Controller;

use App\Pivot\Aggregator\SumAggregator;
use App\Pivot\Field\PivotFieldFactory;
use App\Pivot\PivotTable;
use App\Pivot\PivotTableFactory;
use App\Repository\CalculationRepository;
use App\Response\CsvResponse;
use App\Util\FormatUtils;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Controller to display the pivot table.
 *
 * @author Laurent Muller
 *
 * @Route("/pivot")
 * @IsGranted("ROLE_USER")
 */
class PivotController extends AbstractController
{
    /**
     * Constructor.
     */
    public function __construct(private CalculationRepository $repository)
    {
    }

    /**
     * Show the pivot data table.
     *
     * @Route("", name="calculation_pivot")
     */
    public function pivot(): Response
    {
        return $this->renderForm('calculation/calculation_pivot.html.twig', [
            'highlight' => $this->isSessionBool('highlight'),
            'popover' => $this->isSessionBool('popover', true),
            'table' => $this->getTable(),
        ]);
    }

    /**
     * Export pivot data to CSV.
     *
     * @Route("/csv", name="calculation_pivot_csv")
     */
    public function pivotCsv(): CsvResponse
    {
        // load data
        $dataset = $this->getDataset();

        // callback
        $callback = function () use ($dataset): void {
            // data?
            if ([] !== $dataset) {
                /** @var resource $handle */
                $handle = \fopen('php://output', 'w+');

                // utf-8
                \fprintf($handle, \chr(0xEF) . \chr(0xBB) . \chr(0xBF));

                // headers
                \fputcsv($handle, \array_keys($dataset[0]), ';');

                // rows
                foreach ($dataset as $row) {
                    $row['calculation_date'] = FormatUtils::formatDate($row['calculation_date']);
                    $row['calculation_overall_margin'] = \round($row['calculation_overall_margin'], 3);
                    $row['item_total'] = \round($row['item_total'], 2);
                    \fputcsv($handle, $row, ';');
                }

                // close
                \fclose($handle);
            }
        };

        return new CsvResponse($callback);
    }

    /**
     * Export pivot data to JSON.
     *
     * @Route("/json", name="calculation_pivot_json")
     */
    public function pivotJson(): JsonResponse
    {
        $table = $this->getTable();

        return $this->json($table);
    }

    /**
     * Gets the pivot dataset.
     *
     * @psalm-return array<array{
     *      calculation_id: int,
     *      calculation_date: \DateTimeInterface,
     *      calculation_overall_margin: float,
     *      calculation_overall_total: float,
     *      calculation_state: string,
     *      item_group: string,
     *      item_category: string,
     *      item_description: string,
     *      item_price: float,
     *      item_quantity: float,
     *      item_total: float}>
     */
    private function getDataset(): array
    {
        return $this->repository->getPivot();
    }

    /**
     * Gets the pivot table.
     */
    private function getTable(): ?PivotTable
    {
        // callbacks
        $semesterFormatter = function (int $semestre): string {
            return $this->trans("pivot.semester.$semestre");
        };
        $quarterFormatter = function (int $quarter): string {
            return $this->trans("pivot.quarter.$quarter");
        };

        // fields
        $key = PivotFieldFactory::integer('calculation_id', $this->trans('calculation.fields.id'));
        $data = PivotFieldFactory::float('calculation_overall_total', $this->trans('calculation.fields.overallTotal'));
        $rows = [
            PivotFieldFactory::default('calculation_state', $this->trans('calculationstate.name')),
            PivotFieldFactory::default('item_group', $this->trans('group.name')),
            PivotFieldFactory::default('item_category', $this->trans('category.name')),
        ];
        $columns = [
            PivotFieldFactory::year('calculation_date', $this->trans('pivot.fields.year')),
            PivotFieldFactory::semester('calculation_date', $this->trans('pivot.fields.semester'))
                ->setFormatter($semesterFormatter),
            PivotFieldFactory::quarter('calculation_date', $this->trans('pivot.fields.quarter'))
                ->setFormatter($quarterFormatter),
            PivotFieldFactory::month('calculation_date', $this->trans('pivot.fields.month')),
        ];

        $dataset = $this->getDataset();
        $title = $this->trans('calculation.list.title');

        // create pivot table
        return PivotTableFactory::instance($dataset, $title, SumAggregator::class)
            ->setColumnFields($columns)
            ->setRowFields($rows)
            ->setDataField($data)
            ->setKeyField($key)
            ->create();
    }
}
