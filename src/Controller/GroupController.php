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

use App\DataTable\GroupDataTable;
use App\Entity\AbstractEntity;
use App\Entity\Category;
use App\Excel\ExcelResponse;
use App\Form\Group\GroupType;
use App\Pdf\PdfResponse;
use App\Report\GroupsReport;
use App\Repository\AbstractRepository;
use App\Repository\CategoryRepository;
use App\Spreadsheet\GroupDocument;
use Doctrine\Common\Collections\Criteria;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

/**
 * The controller for root category (group) entities.
 *
 * @Route("/group")
 * @IsGranted("ROLE_USER")
 */
class GroupController extends AbstractEntityController
{
    /**
     * The list route.
     */
    private const ROUTE_LIST = 'group_list';

    /**
     * The table route.
     */
    private const ROUTE_TABLE = 'groupe_table';

    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct(Category::class);
    }

    /**
     * Add a group.
     *
     * @Route("/add", name="group_add", methods={"GET", "POST"})
     */
    public function add(Request $request): Response
    {
        return $this->editEntity($request, new Category());
    }

    /**
     * List the groups.
     *
     * @Route("", name="group_list", methods={"GET"})
     */
    public function card(Request $request): Response
    {
        return $this->renderCard($request, 'code');
    }

    /**
     * Delete a group.
     *
     * @Route("/delete/{id}", name="group_delete", requirements={"id": "\d+" })
     */
    public function delete(Request $request, Category $item, CategoryRepository $repository): Response
    {
        // external references?
        $count = $repository->countGroupReferences($item);
        if (0 !== $count) {
            $display = $item->getDisplay();
            $countText = $this->trans('counters.categories_lower', ['count' => $count]);
            $message = $this->trans('group.delete.failure', [
                '%name%' => $display,
                '%categories%' => $countText,
            ]);
            $parameters = [
                'id' => $item->getId(),
                'title' => 'group.delete.title',
                'message' => $message,
                'back_page' => $this->getDefaultRoute(),
                'back_text' => 'common.button_back_list',
            ];

            return $this->render('cards/card_warning.html.twig', $parameters);
        }

        $parameters = [
            'title' => 'group.delete.title',
            'message' => 'group.delete.message',
            'success' => 'group.delete.success',
            'failure' => 'group.delete.failure',
        ];

        return $this->deleteEntity($request, $item, $parameters);
    }

    /**
     * Edit a group.
     *
     * @Route("/edit/{id}", name="group_edit", requirements={"id": "\d+" }, methods={"GET", "POST"})
     */
    public function edit(Request $request, Category $item): Response
    {
        return $this->editEntity($request, $item);
    }

    /**
     * Export the groups to an Excel document.
     *
     * @Route("/excel", name="group_excel")
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException if no group is found
     */
    public function excel(): ExcelResponse
    {
        /** @var Category[] $groups */
        $groups = $this->getEntities('code');
        if (empty($groups)) {
            $message = $this->trans('group.list.empty');
            throw new NotFoundHttpException($message);
        }

        $doc = new GroupDocument($this, $groups);

        return $this->renderExcelDocument($doc);
    }

    /**
     * Export the groups to a PDF document.
     *
     * @Route("/pdf", name="group_pdf")
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException if no group is found
     */
    public function pdf(): PdfResponse
    {
        /** @var Category[] $groups */
        $groups = $this->getEntities('code');
        if (empty($groups)) {
            $message = $this->trans('group.list.empty');
            throw new NotFoundHttpException($message);
        }

        $doc = new GroupsReport($this, $groups);

        return $this->renderPdfDocument($doc);
    }

    /**
     * Show properties of a group.
     *
     * @Route("/show/{id}", name="group_show", requirements={"id": "\d+" }, methods={"GET", "POST"})
     */
    public function show(Category $item): Response
    {
        return $this->showEntity($item);
    }

    /**
     * Render the table view.
     *
     * @Route("/table", name="group_table", methods={"GET", "POST"})
     */
    public function table(Request $request, GroupDataTable $table): Response
    {
        return $this->renderTable($request, $table);
    }

    /**
     * {@inheritdoc}
     *
     * @param \App\Entity\Category $item
     */
    protected function editEntity(Request $request, AbstractEntity $item, array $parameters = []): Response
    {
        // update parameters
        $parameters['success'] = $item->isNew() ? 'group.add.success' : 'group.edit.success';

        return parent::editEntity($request, $item, $parameters);
    }

    /**
     * {@inheritdoc}
     */
    protected function getCardTemplate(): string
    {
        return 'group/group_card.html.twig';
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
        return GroupType::class;
    }

    /**
     * {@inheritdoc}
     */
    protected function getEditTemplate(): string
    {
        return 'group/group_edit.html.twig';
    }

    /**
     * {@inheritdoc}
     */
    protected function getEntities(string $field = null, string $mode = Criteria::ASC, array $criteria = [], string $alias = AbstractRepository::DEFAULT_ALIAS): array
    {
        $criterias[] = CategoryRepository::getGroupPredicate($alias);

        return parent::getEntities($field, $mode, $criterias, $alias);
    }

    /**
     * {@inheritdoc}
     */
    protected function getShowTemplate(): string
    {
        return 'group/group_show.html.twig';
    }

    /**
     * {@inheritdoc}
     */
    protected function getTableTemplate(): string
    {
        return 'group/group_table.html.twig';
    }
}
