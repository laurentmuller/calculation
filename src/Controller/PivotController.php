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

namespace App\Controller;

use App\Attribute\GetRoute;
use App\Attribute\IndexRoute;
use App\Interfaces\RoleInterface;
use App\Pivot\Aggregator\SumAggregator;
use App\Pivot\Field\PivotFieldFactory;
use App\Pivot\PivotTable;
use App\Pivot\PivotTableFactory;
use App\Repository\CalculationRepository;
use App\Response\CsvResponse;
use App\Utils\FormatUtils;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Controller to display the pivot table.
 *
 * @phpstan-import-type PivotType from CalculationRepository
 */
#[AsController]
#[Route(path: '/pivot', name: 'calculation_pivot_')]
#[IsGranted(RoleInterface::ROLE_USER)]
class PivotController extends AbstractController
{
    public function __construct(private readonly CalculationRepository $repository)
    {
    }

    /**
     * Show the pivot data table.
     */
    #[IndexRoute]
    public function index(): Response
    {
        return $this->render('calculation/calculation_pivot.html.twig', [
            'highlight' => $this->isSessionBool('highlight'),
            'popover' => $this->isSessionBool('popover', true),
            'table' => $this->createTable(),
        ]);
    }

    /**
     * Export pivot data to CSV.
     */
    #[GetRoute(path: '/csv', name: 'csv')]
    public function toCsv(): CsvResponse
    {
        $dataset = $this->createDataset();
        $callback = function () use ($dataset): void {
            if ([] !== $dataset) {
                /** @var resource $handle */
                $handle = \fopen('php://output', 'w+');
                \fprintf($handle, \chr(0xEF) . \chr(0xBB) . \chr(0xBF));
                \fputcsv($handle, \array_keys($dataset[0]), ';');
                foreach ($dataset as $row) {
                    $row['calculation_date'] = FormatUtils::formatDate($row['calculation_date']);
                    $row['calculation_overall_margin'] = \round($row['calculation_overall_margin'], 3);
                    $row['item_total'] = \round($row['item_total'], 2);
                    \fputcsv($handle, $row, ';');
                }
                \fclose($handle);
            }
        };

        return new CsvResponse($callback);
    }

    /**
     * Export pivot data to JSON.
     */
    #[GetRoute(path: '/json', name: 'json')]
    public function toJson(): JsonResponse
    {
        $table = $this->createTable();

        return $this->json($table);
    }

    /**
     * Gets the pivot dataset.
     *
     * @phpstan-return PivotType[]
     */
    private function createDataset(): array
    {
        return $this->repository->getPivot();
    }

    /**
     * Gets the pivot table.
     */
    private function createTable(): ?PivotTable
    {
        $semesterFormatter = fn (int $semestre): string => $this->trans("pivot.semester.$semestre");
        $quarterFormatter = fn (int $quarter): string => $this->trans("pivot.quarter.$quarter");
        $data = PivotFieldFactory::float('item_overall', $this->trans('calculation.fields.overallTotal'));
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
        $dataset = $this->createDataset();
        $title = $this->trans('calculation.list.title');

        return PivotTableFactory::instance($dataset, SumAggregator::class, $title)
            ->setColumnFields($columns)
            ->setRowFields($rows)
            ->setDataField($data)
            ->create();
    }
}
