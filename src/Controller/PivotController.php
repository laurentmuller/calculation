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

use App\Entity\Calculation;
use App\Pivot\Aggregator\SumAggregator;
use App\Pivot\Field\PivotFieldFactory;
use App\Pivot\PivotTable;
use App\Pivot\PivotTableFactory;
use App\Util\FormatUtils;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\StreamedResponse;
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
     * Show the pivot data table.
     *
     * @Route("", name="calculation_pivot")
     */
    public function pivot(): Response
    {
        // create table
        $table = $this->getPivotTable();

        // options
        $popover = $this->isSessionBool('popover', true);
        $highlight = $this->isSessionBool('highlight', false);

        // render
        return $this->render('calculation/calculation_pivot.html.twig', [
            'table' => $table,
            'popover' => $popover,
            'highlight' => $highlight,
        ]);
    }

    /**
     * Export pivot data to CSV.
     *
     * @Route("/export", name="calculation_pivot_export")
     */
    public function pivotExport(): Response
    {
        try {
            // load data
            $dataset = $this->getPivotData();

            // callback
            $callback = function () use ($dataset): void {
                // data?
                if (\count($dataset)) {
                    // open
                    $handle = \fopen('php://output', 'w+');

                    // headers
                    \fputcsv($handle, \array_keys($dataset[0]), ';');

                    // rows
                    foreach ($dataset as $row) {
                        // convert
                        $row['calculation_date'] = FormatUtils::formatDate($row['calculation_date']);
                        $row['calculation_overall_margin'] = \round($row['calculation_overall_margin'], 3);
                        $row['item_total'] = \round($row['item_total'], 2);

                        \fputcsv($handle, $row, ';');
                    }

                    // close
                    \fclose($handle);
                }
            };

            // create response
            $response = new StreamedResponse($callback);

            // headers
            $disposition = $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_INLINE, 'data.csv');
            $response->headers->set('Content-Type', 'text/csv');
            $response->headers->set('Content-Disposition', $disposition);

            return $response;
        } catch (\Exception $e) {
            return $this->jsonException($e);
        }
    }

    /**
     * Export pivot data to JSON.
     *
     * @Route("/json", name="calculation_pivot_json")
     */
    public function pivotJson(): JsonResponse
    {
        try {
            // create table
            $table = $this->getPivotTable();

            return new JsonResponse($table);
        } catch (\Exception $e) {
            return $this->jsonException($e);
        }
    }

    /**
     * Gets the pivot data.
     */
    private function getPivotData(): array
    {
        /** @var \App\Repository\CalculationRepository $repository */
        $repository = $this->getDoctrine()->getRepository(Calculation::class);

        return $repository->getPivot();
    }

    /**
     * Gets the pivot table.
     */
    private function getPivotTable(): PivotTable
    {
        // callbacks
        $semesterFormatter = function (int $semestre) {
            return $this->trans("pivot.semester.$semestre");
        };
        $quarterFormatter = function (int $quarter) {
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

        $dataset = $this->getPivotData();
        $title = $this->trans('calculation.list.title');

        // create pivot table
        return PivotTableFactory::instance($dataset, $title)
            //->setAggregatorClass(AverageAggregator::class)
            //->setAggregatorClass(CountAggregator::class)
            ->setAggregatorClass(SumAggregator::class)
            ->setColumnFields($columns)
            ->setRowFields($rows)
            ->setDataField($data)
            ->setKeyField($key)
            ->create();
    }
}
