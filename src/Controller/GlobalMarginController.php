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

use App\DataTables\GlobalMarginDataTable;
use App\Entity\EntityInterface;
use App\Entity\GlobalMargin;
use App\Form\GlobalMarginType;
use App\Pdf\PdfResponse;
use App\Report\GlobalMarginsReport;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * The controller for global margins entities.
 *
 * @Route("/globalmargin")
 * @IsGranted("ROLE_USER")
 */
class GlobalMarginController extends EntityController
{
    /**
     * The list route.
     */
    private const ROUTE_LIST = 'globalmargin_list';

    /**
     * The table route.
     */
    private const ROUTE_TABLE = 'globalmargin_table';

    /**
     * The edit template.
     */
    private const TEMPLATE_EDIT = 'globalmargin/globalmargin_edit.html.twig';

    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct(GlobalMargin::class);
    }

    /**
     * Add a global margin.
     *
     * @Route("/add", name="globalmargin_add", methods={"GET", "POST"})
     */
    public function add(Request $request): Response
    {
        return $this->editEntity($request, new GlobalMargin());
    }

    /**
     * List the global margins.
     *
     * @Route("", name="globalmargin_list", methods={"GET"})
     */
    public function card(Request $request): Response
    {
        return $this->renderCard($request, 'globalmargin/globalmargin_card.html.twig', 'minimum');
    }

    /**
     * Delete a global margin.
     *
     * @Route("/delete/{id}", name="globalmargin_delete", requirements={"id": "\d+" })
     */
    public function delete(Request $request, GlobalMargin $item): Response
    {
        $parameters = [
            'title' => 'globalmargin.delete.title',
            'message' => 'globalmargin.delete.message',
            'success' => 'globalmargin.delete.success',
            'failure' => 'globalmargin.delete.failure',
        ];

        return $this->deleteEntity($request, $item, $parameters);
    }

    /**
     * Edit a global margin.
     *
     * @Route("/edit/{id}", name="globalmargin_edit", requirements={"id": "\d+" }, methods={"GET", "POST"})
     */
    public function edit(Request $request, GlobalMargin $item): Response
    {
        return $this->editEntity($request, $item);
    }

    /**
     * Export the global margins to a PDF document.
     *
     * @Route("/pdf", name="globalmargin_pdf")
     */
    public function pdf(): PdfResponse
    {
        // get categories
        $margins = $this->getRepository()->findAll();
        if (empty($margins)) {
            $message = $this->trans('globalmargin.list.empty');

            throw $this->createNotFoundException($message);
        }

        // create and render report
        $report = new GlobalMarginsReport($this);
        $report->setGlobalMargins($margins);

        return $this->renderDocument($report);
    }

    /**
     * Show properties of a global margin.
     *
     * @Route("/show/{id}", name="globalmargin_show", requirements={"id": "\d+" }, methods={"GET", "POST"})
     */
    public function show(GlobalMargin $item): Response
    {
        $parameters = [
            'template' => 'globalmargin/globalmargin_show.html.twig',
        ];

        return $this->showEntity($item, $parameters);
    }

    /**
     * Render the table view.
     *
     * @Route("/table", name="globalmargin_table", methods={"GET", "POST"})
     */
    public function table(Request $request, GlobalMarginDataTable $table): Response
    {
        return $this->renderTable($request, $table, 'globalmargin/globalmargin_table.html.twig');
    }

    /**
     * {@inheritdoc}
     *
     * @param GlobalMargin $item
     */
    protected function editEntity(Request $request, EntityInterface $item, array $parameters = []): Response
    {
        // update parameters
        $parameters['type'] = GlobalMarginType::class;
        $parameters['route'] = $this->getDefaultRoute();
        $parameters['template'] = self::TEMPLATE_EDIT;
        $parameters['success'] = $item->isNew() ? 'globalmargin.add.success' : 'globalmargin.edit.success';

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
}
