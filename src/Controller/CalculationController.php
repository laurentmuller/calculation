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

use App\DataTables\CalculationBelowDataTable;
use App\DataTables\CalculationDataTable;
use App\DataTables\CalculationDuplicateDataTable;
use App\DataTables\CalculationEmptyDataTable;
use App\Entity\AbstractEntity;
use App\Entity\Calculation;
use App\Entity\Category;
use App\Form\CalculationEditStateType;
use App\Form\CalculationType;
use App\Interfaces\ApplicationServiceInterface;
use App\Listener\CalculationListener;
use App\Pdf\PdfResponse;
use App\Pivot\Aggregator\SumAggregator;
use App\Pivot\Field\PivotFieldFactory;
use App\Pivot\PivotTable;
use App\Pivot\PivotTableFactory;
use App\Report\CalculationDuplicateTableReport;
use App\Report\CalculationEmptyTableReport;
use App\Report\CalculationReport;
use App\Report\CalculationsReport;
use App\Service\CalculationService;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\EventManager;
use Gedmo\Blameable\BlameableListener;
use Gedmo\Timestampable\TimestampableListener;
use Psr\Log\LoggerInterface;
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
class CalculationController extends EntityController
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
     * The edit template.
     */
    private const TEMPLATE_EDIT = 'calculation/calculation_edit.html.twig';

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

        // default values
        $userName = $this->getUserName();
        if ($userName) {
            $item->setCreatedBy($userName)
                ->setUpdatedBy($userName);
        }

        // default state
        if ($state = $this->getApplication()->getDefaultState()) {
            $item->setState($state);
        }

        $parameters = [
            'overall_below' => false,
        ];

        return $this->editEntity($request, $item, $parameters);
    }

    /**
     * Find calculations where margins is below the minimum.
     *
     * @Route("/below", name="calculation_below")
     * @IsGranted("ROLE_ADMIN")
     */
    public function belowCard(Request $request): Response
    {
        // get values
        $minMargin = $this->getApplication()->getMinMargin();
        $calculations = $this->getBelowMargin($minMargin);
        $selection = $request->get('selection', 0);
        $edit = $this->getApplication()->isEditAction();

        // parameters
        $parameters = [
            'items' => $calculations,
            'items_count' => \count($calculations),
            'min_margin' => $minMargin,
            'query' => false,
            'selection' => $selection,
            'sortField' => 'id',
            'sortMode' => Criteria::DESC,
            'sortFields' => [],
            'edit' => $edit,
        ];

        return $this->render('calculation/calculation_card_below.html.twig', $parameters);
    }

    /**
     * Report calculations where margins is below the minimum.
     *
     * @Route("/below/pdf", name="calculation_below_pdf")
     * @IsGranted("ROLE_ADMIN")
     */
    public function belowPdf(): Response
    {
        $minMargin = $this->getApplication()->getMinMargin();
        $calculations = $this->getBelowMargin($minMargin);
        if (empty($calculations)) {
            $this->warningTrans('calculation.below.empty');

            return  $this->redirectToHomePage();
        }

        $percent = $this->localePercent($minMargin);
        $description = $this->trans('calculation.below.description', ['%margin%' => $percent]);

        $report = new CalculationsReport($this);
        $report->setCalculations($calculations)
            ->setTitleTrans('calculation.below.title')
            ->setDescription($description);

        return $this->renderDocument($report);
    }

    /**
     * Find calculations where margins is below the minimum.
     *
     * @Route("/below/table", name="calculation_below_table")
     * @IsGranted("ROLE_ADMIN")
     */
    public function belowTable(Request $request, CalculationBelowDataTable $table): Response
    {
        $attributes = [];

        // callback?
        if (!$request->isXmlHttpRequest()) {
            $margin = $this->getApplication()->getMinMargin();
            $margin_text = $this->trans('calculation.list.margin_below', ['%minimum%' => $this->localePercent($margin)]);
            $attributes = [
                'min_margin' => $margin,
                'min_margin_text' => $margin_text,
            ];
        }

        return $this->renderTable($request, $table, 'calculation/calculation_table_below.html.twig', $attributes);
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

        return $this->renderCard($request, 'calculation/calculation_card.html.twig', 'id', Criteria::DESC, $sortedFields, $parameters);
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
        $userName = $this->getUserName();
        $clone = $item->clone($state, $userName);

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
     * Find duplicate items in the calculations.
     *
     * @Route("/duplicate", name="calculation_duplicate")
     * @IsGranted("ROLE_ADMIN")
     */
    public function duplicateCard(Request $request): Response
    {
        $calculations = $this->getDuplicateItems();
        $selection = $request->get('selection', 0);
        $edit = $this->getApplication()->isEditAction();

        // number of items
        $items_count = \array_reduce($calculations, function (float $carry, array $calculation) {
            foreach ($calculation['items'] as $item) {
                $carry += $item['count'];
            }

            return $carry;
        }, 0);

        // parameters
        $parameters = [
            'items' => $calculations,
            'items_count' => $items_count,
            'query' => false,
            'selection' => $selection,
            'sortField' => 'id',
            'sortMode' => Criteria::DESC,
            'sortFields' => [],
            'edit' => $edit,
        ];

        return $this->render('calculation/calculation_card_duplicate.html.twig', $parameters);
    }

    /**
     * Report for duplicate items in the calculations.
     *
     * @Route("/duplicate/pdf", name="calculation_duplicate_pdf")
     * @IsGranted("ROLE_ADMIN")
     */
    public function duplicatePdf(): Response
    {
        $items = $this->getDuplicateItems();
        if (empty($items)) {
            $this->warningTrans('calculation.duplicate.empty');

            return  $this->redirectToHomePage();
        }

        $report = new CalculationDuplicateTableReport($this);
        $report->setItems($items);

        return $this->renderDocument($report);
    }

    /**
     * Display the duplicate items in the calculations.
     *
     * @Route("/duplicate/table", name="calculation_duplicate_table")
     * @IsGranted("ROLE_ADMIN")
     */
    public function duplicateTable(Request $request, CalculationDuplicateDataTable $table): Response
    {
        $results = $table->handleRequest($request);
        if ($table->isCallback()) {
            return $this->json($results);
        }

        // attributes
        $attributes = [
            'edit-action' => \json_encode($this->getApplication()->isEditAction()),
            'itemsCount' => $table->getItemCounts(),
        ];

        // parameters
        $parameters = [
            'results' => $results,
            'attributes' => $attributes,
            'columns' => $table->getColumns(),
        ];

        return $this->render('calculation/calculation_table_duplicate.html.twig', $parameters);
    }

    /**
     * Edit a calculation.
     *
     * @Route("/edit/{id}", name="calculation_edit", requirements={"id": "\d+" }, methods={"GET", "POST"})
     */
    public function edit(Request $request, Calculation $item): Response
    {
        return $this->editEntity($request, $item, [
            'overall_below' => $this->isMarginBelow($item),
        ]);
    }

    /**
     * Find empty items in the calculations. Items are empty if the price or the quantity is equal to 0.
     *
     * @Route("/empty", name="calculation_empty")
     * @IsGranted("ROLE_ADMIN")
     */
    public function emptyCard(Request $request): Response
    {
        $calculations = $this->getEmptyItems();
        $selection = $request->get('selection', 0);
        $edit = $this->getApplication()->isEditAction();

        // number of items
        $items_count = \array_reduce($calculations, function (float $carry, array $calculation) {
            return $carry + \count($calculation['items']);
        }, 0);

        // parameters
        $parameters = [
            'items' => $calculations,
            'items_count' => $items_count,
            'query' => false,
            'selection' => $selection,
            'sortField' => 'id',
            'sortMode' => Criteria::DESC,
            'sortFields' => [],
            'edit' => $edit,
        ];

        return $this->render('calculation/calculation_card_empty.html.twig', $parameters);
    }

    /**
     * Report for empty items in the calculations.
     *
     * @Route("/empty/pdf", name="calculation_empty_pdf")
     * @IsGranted("ROLE_ADMIN")
     */
    public function emptyPdf(): Response
    {
        $items = $this->getEmptyItems();
        if (empty($items)) {
            $this->warningTrans('calculation.empty.empty');

            return  $this->redirectToHomePage();
        }

        $report = new CalculationEmptyTableReport($this);
        $report->setItems($items);

        return $this->renderDocument($report);
    }

    /**
     * Display the duplicate items in the calculations.
     *
     * @Route("/empty/table", name="calculation_empty_table")
     * @IsGranted("ROLE_ADMIN")
     */
    public function emptyTable(Request $request, CalculationEmptyDataTable $table): Response
    {
        $results = $table->handleRequest($request);
        if ($table->isCallback()) {
            return $this->json($results);
        }

        // attributes
        $attributes = [
            'edit-action' => \json_encode($this->getApplication()->isEditAction()),
            'itemsCount' => $table->getItemCounts(),
        ];

        // parameters
        $parameters = [
            'results' => $results,
            'attributes' => $attributes,
            'columns' => $table->getColumns(),
        ];

        return $this->render('calculation/calculation_table_empty.html.twig', $parameters);
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

        return $this->renderDocument($report);
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

        return $this->renderDocument($report);
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
        $popover = $this->getSessionBool('popover', true);
        $highlight = $this->getSessionBool('highlight', false);

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
            'template' => 'calculation/calculation_show.html.twig',
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
    public function table(Request $request, CalculationDataTable $table): Response
    {
        $attributes = [];

        // callback?
        if (!$request->isXmlHttpRequest()) {
            // attributes
            $margin = $this->getApplication()->getMinMargin();
            $margin_text = $this->trans('calculation.list.margin_below', ['%minimum%' => $this->localePercent($margin)]);
            $attributes = [
                'min_margin' => $margin,
                'min_margin_text' => $margin_text,
            ];
        }

        return $this->renderTable($request, $table, 'calculation/calculation_table.html.twig', $attributes);
    }

    /**
     * Update calculation totals.
     *
     * @Route("/update", name="calculation_update", methods={"GET", "POST"})
     * @IsGranted("ROLE_ADMIN")
     */
    public function update(Request $request, LoggerInterface $logger): Response
    {
        // create form
        $helper = $this->createFormHelper();

        // fields
        $helper->field('closed')
            ->label('calculation.update.closed_label')
            ->updateOption('help', 'calculation.update.closed_description')
            ->updateHelpAttribute('class', 'ml-4 mb-2')
            ->updateRowAttribute('class', 'mb-0')
            ->notRequired()
            ->addCheckboxType();

        // handle request
        $form = $helper->createForm();
        if ($this->handleRequestForm($request, $form)) {
            $data = $form->getData();
            $closed = (bool) $data['closed'];

            $updated = 0;
            $skipped = 0;
            $unmodifiable = 0;
            $suspended = $this->disableListeners();

            try {
                /** @var Calculation[] $calculations */
                $calculations = $this->getRepository()->findAll();
                foreach ($calculations as $calculation) {
                    if ($closed || $calculation->isEditable()) {
                        if ($this->calculationService->updateTotal($calculation)) {
                            ++$updated;
                        } else {
                            ++$skipped;
                        }
                    } else {
                        ++$unmodifiable;
                    }
                }

                if ($updated > 0) {
                    $this->getManager()->flush();
                }
            } finally {
                $this->enableListeners($suspended);
            }

            $total = \count($calculations);

            // update last update
            $this->getApplication()->setProperties([ApplicationServiceInterface::LAST_UPDATE => new \DateTime()]);

            // log results
            $context = [
                    $this->trans('calculation.update.updated') => $updated,
                    $this->trans('calculation.update.skipped') => $skipped,
                    $this->trans('calculation.update.unmodifiable') => $unmodifiable,
                    $this->trans('calculation.update.total') => $total,
                ];
            $message = $this->trans('calculation.update.title');
            $logger->info($message, $context);

            // display results
            $data = [
                'updated' => $updated,
                'skipped' => $skipped,
                'unmodifiable' => $unmodifiable,
                'total' => $total,
            ];

            return $this->render('calculation/calculation_result.html.twig', $data);
        }

        // display
        return $this->render('calculation/calculation_update.html.twig', [
            'last_update' => $this->getApplication()->getLastUpdate(),
            'form' => $form->createView(),
        ]);
    }

    /**
     * {@inheritdoc}
     *
     * @param Calculation $item
     */
    protected function editEntity(Request $request, AbstractEntity $item, array $parameters = []): Response
    {
        // update parameters
        $parameters['type'] = CalculationType::class;
        $parameters['template'] = self::TEMPLATE_EDIT;
        $parameters['route'] = $this->getDefaultRoute();
        $parameters['success'] = $item->isNew() ? 'calculation.add.success' : 'calculation.edit.success';

        $parameters['groups'] = $this->calculationService->createGroupsFromCalculation($item);
        $parameters['min_margin'] = $this->getApplication()->getMinMargin();

        $parameters['duplicate_items'] = $item->hasDuplicateItems();
        $parameters['emty_items'] = $item->hasEmptyItems();

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
    protected function getDefaultRoute(): string
    {
        if ($this->getApplication()->isDisplayTabular()) {
            return self::ROUTE_TABLE;
        } else {
            return self::ROUTE_LIST;
        }
    }

    /**
     * {@inheritdoc}
     *
     * @param Calculation $item
     */
    protected function updateEntity(AbstractEntity $item): bool
    {
        // compute total
        $this->calculationService->updateTotal($item);

        return parent::updateEntity($item);
    }

    /**
     * Disabled doctrine event listeners.
     *
     * @return array an array containing the event names and listerners
     */
    private function disableListeners(): array
    {
        $suspended = [];
        $manager = $this->getEventManager();
        $allListeners = $manager->getListeners();
        foreach ($allListeners as $event => $listeners) {
            foreach ($listeners as $listener) {
                if ($listener instanceof TimestampableListener
                    || $listener instanceof BlameableListener
                    || $listener instanceof CalculationListener) {
                    $suspended[$event][] = $listener;
                    $manager->removeEventListener($event, $listener);
                }
            }
        }

        return $suspended;
    }

    /**
     * Enabled doctrine event listeners.
     *
     * @param array $suspended the event names and listeners to activate
     */
    private function enableListeners(array $suspended): void
    {
        $manager = $this->getEventManager();
        foreach ($suspended as $event => $listeners) {
            foreach ($listeners as $listener) {
                $manager->addEventListener($event, $listener);
            }
        }
    }

    /**
     * Gets calculations with the overall margin below the given value.
     *
     * @param float $minMargin the minimum margin
     *
     * @return Calculation[] the below calculations
     */
    private function getBelowMargin(float $minMargin): array
    {
        /** @var \App\Repository\CalculationRepository $repository */
        $repository = $this->getRepository();

        return $repository->getBelowMargin($minMargin);
    }

    /**
     * Gets the categories.
     *
     * @return Category[]
     */
    private function getCategories(): array
    {
        /** var App\Repository\CategoryRepository $repository */
        $repository = $this->getManager()->getRepository(Category::class);

        return $repository->getList();
    }

    /**
     * Gets the duplicate items.
     */
    private function getDuplicateItems(): array
    {
        /** @var \App\Repository\CalculationRepository $repository */
        $repository = $this->getRepository();

        return $repository->getDuplicateItems();
    }

    /**
     * Gets the empty items.
     */
    private function getEmptyItems(): array
    {
        /** @var \App\Repository\CalculationRepository $repository */
        $repository = $this->getRepository();

        return $repository->getEmptyItems();
    }

    /**
     * Gets the doctrine event manager.
     *
     * @return EventManager the event manager
     */
    private function getEventManager(): EventManager
    {
        return $this->getManager()->getEventManager();
    }

    /**
     * Gets the pivot data.
     */
    private function getPivotData(): array
    {
        /** @var \App\Repository\CalculationRepository $repository */
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
