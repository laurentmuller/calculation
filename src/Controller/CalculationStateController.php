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

use App\DataTable\CalculationStateDataTable;
use App\Entity\AbstractEntity;
use App\Entity\CalculationState;
use App\Excel\ExcelDocument;
use App\Excel\ExcelResponse;
use App\Form\CalculationState\CalculationStateType;
use App\Interfaces\ApplicationServiceInterface;
use App\Pdf\PdfResponse;
use App\Report\CalculationStatesReport;
use App\Repository\CalculationRepository;
use App\Repository\CalculationStateRepository;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Controller for calculation state entities.
 *
 * @Route("/calculationstate")
 * @IsGranted("ROLE_USER")
 */
class CalculationStateController extends AbstractEntityController
{
    /**
     * The list route.
     */
    private const ROUTE_LIST = 'calculationstate_list';

    /**
     * The table route.
     */
    private const ROUTE_TABLE = 'calculationstate_table';

    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct(CalculationState::class);
    }

    /**
     * Add a new calculation state.
     *
     * @Route("/add", name="calculationstate_add", methods={"GET", "POST"})
     */
    public function add(Request $request): Response
    {
        return $this->editEntity($request, new CalculationState());
    }

    /**
     * Render the card view.
     *
     * @Route("", name="calculationstate_list", methods={"GET"})
     */
    public function card(Request $request): Response
    {
        return $this->renderCard($request, 'code');
    }

    /**
     * Delete a calculation state.
     *
     * @Route("/delete/{id}", name="calculationstate_delete", requirements={"id": "\d+" })
     */
    public function delete(Request $request, CalculationState $item, CalculationRepository $repository): Response
    {
        // calculation?
        $calculations = $repository->countStateReferences($item);
        if (0 !== $calculations) {
            $display = $item->getDisplay();
            $calculationsText = $this->trans('counters.calculations_lower', ['count' => $calculations]);
            $message = $this->trans('calculationstate.delete.failure', [
                '%name%' => $display,
                '%calculations%' => $calculationsText,
                ]);
            $parameters = [
                'id' => $item->getId(),
                'title' => 'calculationstate.delete.title',
                'message' => $message,
                'back_page' => $this->getDefaultRoute(),
                'back_text' => 'common.button_back_list',
            ];

            return $this->render('cards/card_warning.html.twig', $parameters);
        }

        $parameters = [
            'title' => 'calculationstate.delete.title',
            'message' => 'calculationstate.delete.message',
            'success' => 'calculationstate.delete.success',
            'failure' => 'calculationstate.delete.failure',
        ];

        return $this->deleteEntity($request, $item, $parameters);
    }

    /**
     * Edit a calculation state.
     *
     * @Route("/edit/{id}", name="calculationstate_edit", requirements={"id": "\d+" }, methods={"GET", "POST"})
     */
    public function edit(Request $request, CalculationState $item): Response
    {
        return $this->editEntity($request, $item);
    }

    /**
     * Export the calculation states to an Excel document.
     *
     * @Route("/excel", name="calculationstate_excel")
     */
    public function excel(CalculationStateRepository $repository): ExcelResponse
    {
        $doc = new ExcelDocument($this->getTranslator());
        $doc->initialize($this, 'calculationstate.list.title', true);

        // headers
        $doc->setHeaderValues([
            'calculationstate.fields.code' => Alignment::HORIZONTAL_GENERAL,
            'calculationstate.fields.description' => Alignment::HORIZONTAL_GENERAL,
            'calculationstate.fields.editable' => Alignment::HORIZONTAL_RIGHT,
            'calculationstate.fields.calculations' => Alignment::HORIZONTAL_RIGHT,
            'calculationstate.fields.color' => Alignment::HORIZONTAL_CENTER,
        ]);

        // formats
        $doc->setFormatYesNo(3)
            ->setFormatInt(4);

        /** @var CalculationState[] $states */
        $states = $repository->findAllByCode();

        // rows
        $row = 2;
        foreach ($states as $state) {
            $doc->setRowValues($row, [
                $state->getCode(),
                $state->getDescription(),
                $state->isEditable(),
                $state->countCalculations(),
            ]);

            // color
            $col = $doc->stringFromColumnIndex(5);
            $color = new Color(\substr($state->getColor(), 1));
            $fill = $doc->getActiveSheet()
                ->getStyle("$col$row")
                ->getFill();
            $fill->setFillType(Fill::FILL_SOLID)
                ->setStartColor($color);

            ++$row;
        }
        $doc->setSelectedCell('A2');

        return $this->renderExcelDocument($doc);
    }

    /**
     * Export the calculation states to a PDF document.
     *
     * @Route("/pdf", name="calculationstate_pdf")
     */
    public function pdf(): PdfResponse
    {
        // get states
        $states = $this->getRepository()->findAll();
        if (empty($states)) {
            $message = $this->trans('calculationstate.list.empty');

            throw $this->createNotFoundException($message);
        }

        // create and render report
        $report = new CalculationStatesReport($this);
        $report->setStates($states);

        return $this->renderPdfDocument($report);
    }

    /**
     * Show properties of a calculation state.
     *
     * @Route("/show/{id}", name="calculationstate_show", requirements={"id": "\d+" }, methods={"GET", "POST"})
     */
    public function show(CalculationState $item): Response
    {
        return $this->showEntity($item);
    }

    /**
     * Render the table view.
     *
     * @Route("/table", name="calculationstate_table", methods={"GET", "POST"})
     */
    public function table(Request $request, CalculationStateDataTable $table): Response
    {
        // callback?
        $attributes = [];
        if (!$request->isXmlHttpRequest()) {
            $route = $this->isDisplayTabular() ? 'calculation_table' : 'calculationstate_list';
            $attributes = [
                'link_href' => $this->generateUrl($route),
                'link_title' => $this->trans('calculationstate.list.calculation_title'),
            ];
        }

        return $this->renderTable($request, $table, $attributes);
    }

    /**
     * {@inheritdoc}
     *
     * @param CalculationState $item
     */
    protected function deleteFromDatabase(AbstractEntity $item): void
    {
        // update default state (if applicable)
        $id = $this->getApplication()->getDefaultStateId();
        if ($id === $item->getId()) {
            $this->getApplication()->setProperties([ApplicationServiceInterface::P_DEFAULT_STATE => null]);
        }
        parent::deleteFromDatabase($item);
    }

    /**
     * {@inheritdoc}
     *
     * @param CalculationState $item
     */
    protected function editEntity(Request $request, AbstractEntity $item, array $parameters = []): Response
    {
        // update parameters
        $parameters['success'] = $item->isNew() ? 'calculationstate.add.success' : 'calculationstate.edit.success';

        return parent::editEntity($request, $item, $parameters);
    }

    /**
     * {@inheritdoc}
     */
    protected function getCardTemplate(): string
    {
        return 'calculationstate/calculationstate_card.html.twig';
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
        return CalculationStateType::class;
    }

    /**
     * {@inheritdoc}
     */
    protected function getEditTemplate(): string
    {
        return 'calculationstate/calculationstate_edit.html.twig';
    }

    /**
     * {@inheritdoc}
     */
    protected function getShowTemplate(): string
    {
        return 'calculationstate/calculationstate_show.html.twig';
    }

    /**
     * {@inheritdoc}
     */
    protected function getTableTemplate(): string
    {
        return 'calculationstate/calculationstate_table.html.twig';
    }
}
