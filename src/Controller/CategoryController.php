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

use App\DataTables\CategoryDataTable;
use App\Entity\Category;
use App\Form\CategoryType;
use App\Pdf\PdfResponse;
use App\Report\CategoriesReport;
use App\Repository\CalculationGroupRepository;
use App\Repository\ProductRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

/**
 * The controller for category entities.
 *
 * @Route("/category")
 * @IsGranted("ROLE_USER")
 */
class CategoryController extends EntityController
{
    /**
     * The delete route.
     */
    private const ROUTE_DELETE = 'category_delete';

    /**
     * The list route.
     */
    private const ROUTE_LIST = 'category_list';

    /**
     * The edit template.
     */
    private const TEMPLATE_EDIT = 'category/category_edit.html.twig';

    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct(Category::class);
    }

    /**
     * Add a category.
     *
     * @Route("/add", name="category_add", methods={"GET", "POST"})
     */
    public function add(Request $request): Response
    {
        return $this->editItem($request, ['item' => new Category()]);
    }

    /**
     * List the categories.
     *
     * @Route("", name="category_list", methods={"GET"})
     */
    public function card(Request $request): Response
    {
        return $this->renderCard($request, 'category/category_card.html.twig', 'code');
    }

    /**
     * Delete a category.
     *
     * @Route("/delete/{id}", name="category_delete", requirements={"id": "\d+" })
     */
    public function delete(Request $request, Category $item, ProductRepository $productRepository, CalculationGroupRepository $groupRepository): Response
    {
        // external references?
        if (0 !== $productRepository->countCategoryReferences($item) || 0 !== $groupRepository->countCategoryReferences($item)) {
            $display = $item->getDisplay();
            $description = $this->trans('category.delete.failure', ['%name%' => $display]);
            $parameters = [
                'id' => $item->getId(),
                'display_email' => false,
                'display_description' => false,
                'page_list' => self::ROUTE_LIST,
                'title' => 'category.delete.title',
                'description' => $description,
            ];

            return $this->render('@Twig/Exception/exception.html.twig', $parameters);
        }

        $parameters = [
            'item' => $item,
            'page_list' => self::ROUTE_LIST,
            'page_delete' => self::ROUTE_DELETE,
            'title' => 'category.delete.title',
            'message' => 'category.delete.message',
            'success' => 'category.delete.success',
            'failure' => 'category.delete.failure',
        ];

        return $this->deletItem($request, $parameters);
    }

    /**
     * Edit a category.
     *
     * @Route("/edit/{id}", name="category_edit", requirements={"id": "\d+" }, methods={"GET", "POST"})
     */
    public function edit(Request $request, Category $item): Response
    {
        return $this->editItem($request, ['item' => $item]);
    }

    /**
     * Export the categories to a PDF document.
     *
     * @Route("/pdf", name="category_pdf")
     */
    public function pdf(): PdfResponse
    {
        // get categories
        $categories = $this->getRepository()->findAll();
        if (empty($categories)) {
            $message = $this->trans('category.list.empty');
            throw new NotFoundHttpException($message);
        }

        // create and render report
        $report = new CategoriesReport($this);
        $report->setCategories($categories);

        return $this->renderDocument($report);
    }

    /**
     * Show properties of a category.
     *
     * @Route("/show/{id}", name="category_show", requirements={"id": "\d+" }, methods={"GET", "POST"})
     */
    public function show(Category $item): Response
    {
        return $this->showItem('category/category_show.html.twig', $item);
    }

    /**
     * Render the table view.
     *
     * @Route("/table", name="category_table", methods={"GET", "POST"})
     */
    public function table(Request $request, CategoryDataTable $table): Response
    {
        // callback?
        $attributes = [];
        if (!$request->isXmlHttpRequest()) {
            $attributes = [
                'link_href' => $this->generateUrl('product_table'),
                'link_title' => $this->trans('category.list.product_title'),
            ];
        }

        return $this->showTable($request, $table, 'category/category_table.html.twig', $attributes);
    }

    /**
     * {@inheritdoc}
     */
    protected function editItem(Request $request, array $parameters): Response
    {
        /** @var Category $item */
        $item = $parameters['item'];

        // update parameters
        $parameters['type'] = CategoryType::class;
        $parameters['template'] = self::TEMPLATE_EDIT;
        $parameters['route'] = self::ROUTE_LIST;
        $parameters['success'] = $item->isNew() ? 'category.add.success' : 'category.edit.success';

        return parent::editItem($request, $parameters);
    }
}
