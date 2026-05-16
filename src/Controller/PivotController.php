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

use App\Attribute\ForUser;
use App\Attribute\GetRoute;
use App\Attribute\IndexRoute;
use App\Model\TranslatableFlashMessage;
use App\Pivot\Field\PivotField;
use App\Pivot\Field\PivotFieldFactory;
use App\Pivot\PivotOperation;
use App\Pivot\PivotTable;
use App\Pivot\PivotTableFactory;
use App\Repository\CalculationRepository;
use App\Response\CsvResponse;
use App\Utils\DateUtils;
use App\Utils\FormatUtils;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Controller to display the pivot table.
 *
 * @phpstan-import-type PivotType from CalculationRepository
 */
#[ForUser]
#[Route(path: '/pivot', name: 'calculation_pivot_')]
class PivotController extends AbstractController
{
    public function __construct(private readonly CalculationRepository $repository)
    {
    }

    /**
     * Show the pivot data table.
     */
    #[IndexRoute]
    public function index(
        #[MapQueryParameter]
        ?int $months = null,
        #[MapQueryParameter]
        ?PivotOperation $operation = null
    ): Response {
        $months = $this->validateMonths($months);
        $operation = $this->validateOperation($operation);
        $table = $this->createTable($months, $operation);
        if (!$table instanceof PivotTable) {
            return $this->getEmptyResponse();
        }

        return $this->render('calculation/calculation_pivot.html.twig', [
            'highlight' => $this->isSessionBool('pivot.highlight'),
            'popover' => $this->isSessionBool('pivot.popover', true),
            'table' => $table,
            'months' => $months,
            'operation' => $operation,
        ]);
    }

    /**
     * Export pivot data to CSV.
     */
    #[GetRoute(path: '/csv', name: 'csv')]
    public function toCsv(#[MapQueryParameter] ?int $months = null): Response
    {
        $months = $this->validateMonths($months);
        $dataset = $this->createDataset($months);
        if ([] === $dataset) {
            return $this->getEmptyResponse();
        }

        $callback = static function () use ($dataset): void {
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
        };

        return new CsvResponse(callback: $callback, name: \sprintf('pivot_%d', $months));
    }

    /**
     * Export pivot data to JSON.
     */
    #[GetRoute(path: '/json', name: 'json')]
    public function toJson(
        #[MapQueryParameter]
        ?int $months = null,
        #[MapQueryParameter]
        ?PivotOperation $operation = null
    ): JsonResponse {
        $months = $this->validateMonths($months);
        $operation = $this->validateOperation($operation);
        $table = $this->createTable($months, $operation);
        if (!$table instanceof PivotTable) {
            return $this->jsonFalse([
                'message' => $this->trans('pivot.empty'),
            ]);
        }

        return $this->json($table);
    }

    protected function getEmptyResponse(): RedirectResponse
    {
        return $this->redirectToHomePage(
            message: TranslatableFlashMessage::info('pivot.empty')
        );
    }

    /**
     * Gets the pivot dataset.
     *
     * @phpstan-return PivotType[]
     */
    private function createDataset(int $months): array
    {
        [, $endDate] = $this->repository->getMinMaxDates();
        $endDate ??= DateUtils::createDate();
        $endDate = $endDate->modify('first day of next month');
        $startDate = DateUtils::sub($endDate, \sprintf('P%dM', $months));

        return $this->repository->getPivot($startDate, $endDate);
    }

    /**
     * Gets the pivot table.
     */
    private function createTable(int $months, PivotOperation $operation): ?PivotTable
    {
        $dataset = $this->createDataset($months);
        if ([] === $dataset) {
            return null;
        }

        $title = $this->trans('calculation.list.title');
        $data = PivotFieldFactory::float('item_overall', $this->trans('calculation.fields.overallTotal'));

        return PivotTableFactory::instance($dataset, $operation, $title)
            ->setColumnFields(...$this->getColumnFields())
            ->setRowFields(...$this->getRowFields())
            ->setDataField($data)
            ->create();
    }

    /**
     * @return PivotField[]
     */
    private function getColumnFields(): array
    {
        $semesterFormatter = fn (int $semestre): string => $this->trans('counters.semester', ['count' => $semestre]);
        $quarterFormatter = fn (int $quarter): string => $this->trans('counters.quarter', ['count' => $quarter]);

        return [
            PivotFieldFactory::year('calculation_date', $this->trans('pivot.fields.year')),
            PivotFieldFactory::semester('calculation_date', $this->trans('pivot.fields.semester'))
                ->setFormatter($semesterFormatter),
            PivotFieldFactory::quarter('calculation_date', $this->trans('pivot.fields.quarter'))
                ->setFormatter($quarterFormatter),
            PivotFieldFactory::month('calculation_date', $this->trans('pivot.fields.month')),
        ];
    }

    /**
     * @return PivotField[]
     */
    private function getRowFields(): array
    {
        return [
            PivotFieldFactory::default('calculation_state', $this->trans('calculationstate.name')),
            PivotFieldFactory::default('item_group', $this->trans('group.name')),
            PivotFieldFactory::default('item_category', $this->trans('category.name')),
        ];
    }

    /**
     * @throws BadRequestHttpException
     */
    private function validateMonths(?int $months): int
    {
        $months ??= $this->getSessionInt('pivot.months', 3);
        if ($months < 1 || $months > 12) {
            throw new BadRequestHttpException($this->trans('pivot.invalid_months'));
        }
        $this->setSessionValue('pivot.months', $months);

        return $months;
    }

    private function validateOperation(?PivotOperation $operation): PivotOperation
    {
        $operation ??= $this->getSessionEnum('pivot.operation', PivotOperation::getDefault());
        $this->setSessionValue('pivot.operation', $operation);

        return $operation;
    }
}
