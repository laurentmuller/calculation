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

use App\DataTables\CalculationStateDataTable;
use App\Entity\CalculationState;
use App\Form\CalculationStateType;
use App\Interfaces\IApplicationService;
use App\Pdf\PdfResponse;
use App\Report\CalculationStatesReport;
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
class CalculationStateController extends EntityController
{
    /**
     * The delete route.
     */
    public const ROUTE_DELETE = 'calculationstate_delete';

    /**
     * The list route.
     */
    public const ROUTE_LIST = 'calculationstate_list';

    /**
     * The edit template.
     */
    public const TEMPLATE_EDIT = 'calculationstate/calculationstate_edit.html.twig';

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
        return $this->editItem($request, ['item' => new CalculationState()]);
    }

    /**
     * List the calculation states.
     *
     * @Route("", name="calculationstate_list", methods={"GET"})
     */
    public function card(Request $request): Response
    {
        return $this->renderCard($request, 'calculationstate/calculationstate_card.html.twig', 'code');
    }

    /**
     * Delete a calculation state.
     *
     * @Route("/delete/{id}", name="calculationstate_delete", requirements={"id": "\d+" })
     */
    public function delete(Request $request, CalculationState $item): Response
    {
        $parameters = [
            'item' => $item,
            'page_list' => self::ROUTE_LIST,
            'page_delete' => self::ROUTE_DELETE,
            'title' => 'calculationstate.delete.title',
            'message' => 'calculationstate.delete.message',
            'success' => 'calculationstate.delete.success',
            'failure' => 'calculationstate.delete.failure',
        ];

        return $this->deletItem($request, $parameters);
    }

    /**
     * Edit a calculation state.
     *
     * @Route("/edit/{id}", name="calculationstate_edit", requirements={"id": "\d+" }, methods={"GET", "POST"})
     */
    public function edit(Request $request, CalculationState $item): Response
    {
        return $this->editItem($request, ['item' => $item]);
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

        return $this->renderDocument($report);
    }

    /**
     * Show properties of a calculation state.
     *
     * @Route("/show/{id}", name="calculationstate_show", requirements={"id": "\d+" }, methods={"GET", "POST"})
     */
    public function show(CalculationState $item): Response
    {
        return $this->showItem('calculationstate/calculationstate_show.html.twig', $item);
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
            $attributes = [
                'link_href' => $this->generateUrl('calculation_table'),
                'link_title' => $this->trans('calculationstate.list.calculation_title'),
            ];
        }

        return $this->showTable($request, $table, 'calculationstate/calculationstate_table.html.twig', $attributes);
    }

    /**
     * {@inheritdoc}
     *
     * @param CalculationState $item
     */
    protected function canDelete($item): bool
    {
        // remove from properties if this state is the default
        $id = $this->getApplication()->getDefaultStateId();
        if ($id === $item->getId()) {
            $this->getApplication()->setProperties([IApplicationService::DEFAULT_STATE => null]);
        }

        return parent::canDelete($item);
    }

    /**
     * {@inheritdoc}
     */
    protected function editItem(Request $request, array $parameters): Response
    {
        /** @var CalculationState $item */
        $item = $parameters['item'];

        // $parameters['title'] = $item->isNew() ? 'calculationstate.add.title' : 'calculationstate.edit.title';
        $parameters['type'] = CalculationStateType::class;
        $parameters['template'] = self::TEMPLATE_EDIT;
        $parameters['route'] = self::ROUTE_LIST;
        $parameters['success'] = $item->isNew() ? 'calculationstate.add.success' : 'calculationstate.edit.success';

        return parent::editItem($request, $parameters);
    }
}
