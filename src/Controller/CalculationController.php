<?php
/*
 * This file is part of the Calculation package.
 *
 * Copyright (c) 2019 bibi.nu. All rights reserved.
 *
 * This computer code is protected by copyright law and international
 * treaties. Unauthorised reproduction or distribution of this code, or
 * any portion of it, may result in severe civil and criminal penalties,
 * and will be prosecuted to the maximum extent possible under the law.
 */

declare(strict_types=1);

namespace App\Controller;

use App\DataTable\CalculationDataTable;
use App\Entity\AbstractEntity;
use App\Entity\Calculation;
use App\Entity\Category;
use App\Excel\ExcelDocument;
use App\Excel\ExcelResponse;
use App\Form\Calculation\CalculationEditStateType;
use App\Form\Calculation\CalculationType;
use App\Pdf\PdfResponse;
use App\Pivot\Aggregator\SumAggregator;
use App\Pivot\Field\PivotFieldFactory;
use App\Pivot\PivotTable;
use App\Pivot\PivotTableFactory;
use App\Report\CalculationReport;
use App\Report\CalculationsReport;
use App\Repository\CalculationRepository;
use App\Repository\CalculationStateRepository;
use App\Service\CalculationService;
use Doctrine\Common\Collections\Criteria;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Controller for calculation entities.
 *
 * @Route("/calculation")
 * @IsGranted("ROLE_USER")
 */
class CalculationController extends AbstractEntityController
{
    /**
     * The list route.
     */
    private const ROUTE_LIST = 'calculation_list';

    /**
     * The table route.
     */
    private const ROUTE_TABLE = 'calculation_table';

    /**
     * The service to compute calculations.
     *
     * @var CalculationService
     */
    private $calculationService;

    /**
     * Constructor.
     *
     * @param CalculationService $calculationService the service to compute calculations
     */
    public function __construct(CalculationService $calculationService)
    {
        parent::__construct(Calculation::class);
        $this->calculationService = $calculationService;
    }

    /**
     * Add a new calculation.
     *
     * @Route("/add", name="calculation_add", methods={"GET", "POST"})
     */
    public function add(Request $request): Response
    {
        // create
        $item = new Calculation();
        if ($state = $this->getApplication()->getDefaultState()) {
            $item->setState($state);
        }

        $parameters = ['overall_below' => false];

        return $this->editEntity($request, $item, $parameters);
    }

    /**
     * List the calculations.
     *
     * @Route("", name="calculation_list", methods={"GET", "POST"})
     */
    public function card(Request $request): Response
    {
        $sortedFields = [
            ['name' => 'id', 'label' => 'calculation.fields.id', 'numeric' => true],
            ['name' => 'date', 'label' => 'calculation.fields.date'],
            ['name' => 'customer', 'label' => 'calculation.fields.customer'],
            ['name' => 'description', 'label' => 'calculation.fields.description'],
            ['name' => 'overallTotal', 'label' => 'calculation.fields.total', 'numeric' => true],
        ];
        $parameters = [
            'min_margin' => $this->getApplication()->getMinMargin(),
        ];

        return $this->renderCard($request, 'id', Criteria::DESC, $sortedFields, $parameters);
    }

    /**
     * Edit a copy (cloned) calculation.
     *
     * @Route("/clone/{id}", name="calculation_clone", requirements={"id": "\d+" }, methods={"GET", "POST"})
     */
    public function clone(Request $request, Calculation $item): Response
    {
        // clone
        $state = $this->getApplication()->getDefaultState();
        $clone = $item->clone($state);

        $parameters = [
            'overall_below' => $this->isMarginBelow($clone),
        ];

        return $this->editEntity($request, $clone, $parameters);
    }

    /**
     * Delete a calculation.
     *
     * @Route("/delete/{id}", name="calculation_delete", requirements={"id": "\d+" })
     */
    public function delete(Request $request, Calculation $item): Response
    {
        // parameters
        $parameters = [
            'title' => 'calculation.delete.title',
            'message' => 'calculation.delete.message',
            'success' => 'calculation.delete.success',
            'failure' => 'calculation.delete.failure',
        ];

        // delete
        return $this->deleteEntity($request, $item, $parameters);
    }

    /**
     * Edit a calculation.
     *
     * @Route("/edit/{id}", name="calculation_edit", requirements={"id": "\d+" }, methods={"GET", "POST"})
     */
    public function edit(Request $request, Calculation $item): Response
    {
        $parameters = ['overall_below' => $this->isMarginBelow($item)];

        return $this->editEntity($request, $item, $parameters);
    }

    /**
     * Export the calculations to an Excel document.
     *
     * @Route("/excel", name="calculation_excel")
     */
    public function excel(CalculationRepository $repository): ExcelResponse
    {
        $doc = new ExcelDocument($this->getTranslator());
        $doc->initialize($this, 'calculation.list.title', true);

        // headers
        $doc->setHeaderValues([
            'calculation.fields.id' => Alignment::HORIZONTAL_CENTER,
            'calculation.fields.date' => Alignment::HORIZONTAL_CENTER,
            'calculation.fields.state' => Alignment::HORIZONTAL_GENERAL,
            'calculation.fields.customer' => Alignment::HORIZONTAL_GENERAL,
            'calculation.fields.description' => Alignment::HORIZONTAL_GENERAL,
            'calculationgroup.fields.amount' => Alignment::HORIZONTAL_RIGHT,
            'calculation.fields.margin' => Alignment::HORIZONTAL_RIGHT,
            'calculation.fields.total' => Alignment::HORIZONTAL_RIGHT,
        ]);

        // formats
        $percentage = NumberFormat::FORMAT_PERCENTAGE;
        $minMargin = $this->getApplication()->getMinMargin();
        $format = "[Red][<$minMargin]$percentage;$percentage";
        $doc->setColumnFormatId(1)
            ->setColumnFormatDate(2)
            ->setColumnFormatAmount(6)
            ->setColumnFormat(7, $format)
            ->setColumnFormatAmount(8);

        /** @var Calculation[] $calculations */
        $calculations = $repository->findAllById();

        // rows
        $row = 2;
        foreach ($calculations as $calculation) {
            $doc->setRowValues($row++, [
                $calculation->getId(),
                $calculation->getDate(),
                $calculation->getStateCode(),
                $calculation->getCustomer(),
                $calculation->getDescription(),
                $calculation->getItemsTotal(),
                $calculation->getOverallMargin(),
                $calculation->getOverallTotal(),
                ]);
        }

        $doc->setSelectedCell('A2');

        return $this->renderExcelDocument($doc);
    }

    /**
     * Export calculations to a PDF document.
     *
     * @Route("/pdf", name="calculation_pdf")
     */
    public function pdf(Request $request): PdfResponse
    {
        // get calculations
        $calculations = $this->getRepository()->findAll();
        if (empty($calculations)) {
            $message = $this->trans('calculation.list.empty');

            throw $this->createNotFoundException($message);
        }

        // create and render report
        $grouped = (bool) $request->get('grouped', true);
        $report = new CalculationsReport($this);
        $report->setCalculations($calculations)
            ->setGrouped($grouped);

        return $this->renderPdfDocument($report);
    }

    /**
     * Export a single calculation to a PDF document.
     *
     * @Route("/pdf/{id}", name="calculation_pdf_id", requirements={"id": "\d+" })
     */
    public function pdfById(Calculation $calculation): PdfResponse
    {
        // create and render report
        $report = new CalculationReport($this);
        $report->setCalculation($calculation);

        return $this->renderPdfDocument($report);
    }

    /**
     * Show the pivot data table.
     *
     * @Route("/pivot", name="calculation_pivot", methods={"GET", "POST"})
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
     * @Route("/pivot/export", name="calculation_pivot_export", methods={"GET", "POST"})
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
                        $row['calculation_date'] = $this->localeDate($row['calculation_date']);
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
     * @Route("/pivot/json", name="calculation_pivot_json", methods={"GET", "POST"})
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
     * Show properties of a calculation.
     *
     * @Route("/show/{id}", name="calculation_show", requirements={"id": "\d+" }, methods={"GET", "POST"})
     */
    public function show(Calculation $item): Response
    {
        $parameters = [
            'min_margin' => $this->getApplication()->getMinMargin(),
            'duplicate_items' => $item->hasDuplicateItems(),
            'emty_items' => $item->hasEmptyItems(),
        ];

        return $this->showEntity($item, $parameters);
    }

    /**
     * Edit the state of a calculation.
     *
     * @Route("/state/{id}", name="calculation_state", requirements={"id": "\d+" }, methods={"GET", "POST"})
     */
    public function state(Request $request, Calculation $item): Response
    {
        $oldState = $item->getState();
        $form = $this->createForm(CalculationEditStateType::class, $item);
        if ($this->handleRequestForm($request, $form)) {
            //change?
            if ($oldState !== $item->getState()) {
                // update
                $this->getManager()->flush();
            }

            // message
            $this->succesTrans('calculation.state.success', ['%name%' => $item->getDisplay()]);

            return $this->getUrlGenerator()->redirect($request, $item->getId(), $this->getDefaultRoute());
        }

        // display
        return $this->render('calculation/calculation_state.html.twig', [
            'form' => $form->createView(),
            'item' => $item,
        ]);
    }

    /**
     * Render the table view.
     *
     * @Route("/table", name="calculation_table", methods={"GET", "POST"})
     */
    public function table(Request $request, CalculationDataTable $table, CalculationStateRepository $repository): Response
    {
        $attributes = [];
        $parameters = [];

        // callback?
        if (!$request->isXmlHttpRequest()) {
            // attributes
            $margin = $this->getApplication()->getMinMargin();
            $margin_text = $this->trans('calculation.list.margin_below', ['%minimum%' => $this->localePercent($margin)]);
            $attributes = [
                'min_margin' => $margin,
                'min_margin_text' => $margin_text,
            ];

            // parameters
            $states = $repository->getListCount();
            $total = \array_sum(\array_column($states, 'count'));
            $parameters = [
                'states' => $states,
                'total' => $total,
            ];
        }

        return $this->renderTable($request, $table, $attributes, $parameters);
    }

    /**
     * {@inheritdoc}
     *
     * @param Calculation $item
     */
    protected function editEntity(Request $request, AbstractEntity $item, array $parameters = []): Response
    {
        // update parameters
        $parameters['success'] = $item->isNew() ? 'calculation.add.success' : 'calculation.edit.success';
        $parameters['groups'] = $this->calculationService->createGroupsFromCalculation($item);
        $parameters['min_margin'] = $this->getApplication()->getMinMargin();
        $parameters['duplicate_items'] = $item->hasDuplicateItems();
        $parameters['emty_items'] = $item->hasEmptyItems();

        // editable?
        if ($parameters['editable'] = $item->isEditable()) {
            $parameters['groupIndex'] = $item->getGroupsCount();
            $parameters['itemIndex'] = $item->getLinesCount();
            $parameters['categories'] = $this->getCategories();
            $parameters['grouping'] = $this->getApplication()->getGrouping();
            $parameters['decimal'] = $this->getApplication()->getDecimal();
        }

        return parent::editEntity($request, $item, $parameters);
    }

    /**
     * {@inheritdoc}
     */
    protected function getCardTemplate(): string
    {
        return 'calculation/calculation_card.html.twig';
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultRoute(): string
    {
        return $this->isDisplayTabular() ? self::ROUTE_TABLE : self::ROUTE_LIST;
    }

    /**
     * {@inheritdoc}
     */
    protected function getEditFormType(): string
    {
        return CalculationType::class;
    }

    /**
     * {@inheritdoc}
     */
    protected function getEditTemplate(): string
    {
        return 'calculation/calculation_edit.html.twig';
    }

    /**
     * {@inheritdoc}
     */
    protected function getShowTemplate(): string
    {
        return 'calculation/calculation_show.html.twig';
    }

    /**
     * {@inheritdoc}
     */
    protected function getTableTemplate(): string
    {
        return 'calculation/calculation_table.html.twig';
    }

    /**
     * {@inheritdoc}
     *
     * @param Calculation $item
     */
    protected function saveToDatabase(AbstractEntity $item): void
    {
        $this->calculationService->updateTotal($item);
        parent::saveToDatabase($item);
    }

    /**
     * Gets the categories.
     *
     * @return Category[]
     */
    private function getCategories(): array
    {
        /** @var \App\Repository\CategoryRepository $repository */
        $repository = $this->getManager()->getRepository(Category::class);

        return $repository->getList();
    }

    /**
     * Gets the pivot data.
     */
    private function getPivotData(): array
    {
        /** @var CalculationRepository $repository */
        $repository = $this->getRepository();

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
            PivotFieldFactory::default('item_group', $this->trans('category.name')),
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

    /**
     * Returns if the given calculation has an overall margin below the minimum.
     *
     * @param Calculation $calculation the calculation to verify
     *
     * @return bool true if margin is below
     */
    private function isMarginBelow(Calculation $calculation): bool
    {
        $margin = $this->getApplication()->getMinMargin();

        return $calculation->isMarginBelow($margin);
    }
}
