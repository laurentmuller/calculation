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

use App\DataTable\CategoryDataTable;
use App\Entity\AbstractEntity;
use App\Entity\Category;
use App\Excel\ExcelDocument;
use App\Excel\ExcelResponse;
use App\Form\Category\CategoryType;
use App\Pdf\PdfResponse;
use App\Report\CategoriesReport;
use App\Repository\CalculationGroupRepository;
use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
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
class CategoryController extends AbstractEntityController
{
    /**
     * The list route.
     */
    private const ROUTE_LIST = 'category_list';

    /**
     * The table route.
     */
    private const ROUTE_TABLE = 'category_table';

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
        return $this->editEntity($request, new Category());
    }

    /**
     * List the categories.
     *
     * @Route("", name="category_list", methods={"GET"})
     */
    public function card(Request $request): Response
    {
        return $this->renderCard($request, 'code');
    }

    /**
     * Delete a category.
     *
     * @Route("/delete/{id}", name="category_delete", requirements={"id": "\d+" })
     */
    public function delete(Request $request, Category $item, ProductRepository $productRepository, CalculationGroupRepository $groupRepository): Response
    {
        // external references?
        $products = $productRepository->countCategoryReferences($item);
        $calculations = $groupRepository->countCategoryReferences($item);
        if (0 !== $products || 0 !== $calculations) {
            $display = $item->getDisplay();
            $productsText = $this->trans('counters.products_lower', ['count' => $products]);
            $calculationsText = $this->trans('counters.calculations_lower', ['count' => $calculations]);
            $message = $this->trans('category.delete.failure', [
                '%name%' => $display,
                '%products%' => $productsText,
                '%calculations%' => $calculationsText,
            ]);
            $parameters = [
                'id' => $item->getId(),
                'title' => 'category.delete.title',
                'message' => $message,
                'back_page' => $this->getDefaultRoute(),
                'back_text' => 'common.button_back_list',
            ];

            return $this->render('cards/card_warning.html.twig', $parameters);
        }

        $parameters = [
            'title' => 'category.delete.title',
            'message' => 'category.delete.message',
            'success' => 'category.delete.success',
            'failure' => 'category.delete.failure',
        ];

        return $this->deleteEntity($request, $item, $parameters);
    }

    /**
     * Edit a category.
     *
     * @Route("/edit/{id}", name="category_edit", requirements={"id": "\d+" }, methods={"GET", "POST"})
     */
    public function edit(Request $request, Category $item): Response
    {
        return $this->editEntity($request, $item);
    }

    /**
     * Export the categories to an Excel document.
     *
     * @Route("/excel", name="category_excel")
     */
    public function excel(CategoryRepository $repository): ExcelResponse
    {
        $doc = new ExcelDocument($this->getTranslator());
        $doc->initialize($this, 'category.list.title');

        // headers
        $doc->setHeaderValues([
            'category.fields.code' => Alignment::HORIZONTAL_GENERAL,
            'category.fields.description' => Alignment::HORIZONTAL_GENERAL,
            'category.fields.margins' => Alignment::HORIZONTAL_RIGHT,
            'category.fields.products' => Alignment::HORIZONTAL_RIGHT,
        ]);

        // formats
        $doc->setColumnFormatInt(3)
            ->setColumnFormatInt(4);

        /** @var Category[] $categories */
        $categories = $repository->findAllByCode();

        // rows
        $row = 2;
        foreach ($categories as $category) {
            $doc->setRowValues($row++, [
                $category->getCode(),
                $category->getDescription(),
                $category->countMargins(),
                $category->countProducts(),
            ]);
        }
        $doc->setSelectedCell('A2');

        return $this->renderExcelDocument($doc);
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

        return $this->renderPdfDocument($report);
    }

    /**
     * Show properties of a category.
     *
     * @Route("/show/{id}", name="category_show", requirements={"id": "\d+" }, methods={"GET", "POST"})
     */
    public function show(Category $item): Response
    {
        return $this->showEntity($item);
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

        return $this->renderTable($request, $table, $attributes);
    }

    /**
     * {@inheritdoc}
     *
     * @param Category $item
     */
    protected function editEntity(Request $request, AbstractEntity $item, array $parameters = []): Response
    {
        // update parameters
        $parameters['success'] = $item->isNew() ? 'category.add.success' : 'category.edit.success';

        return parent::editEntity($request, $item, $parameters);
    }

    /**
     * {@inheritdoc}
     */
    protected function getCardTemplate(): string
    {
        return 'category/category_card.html.twig';
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
        return CategoryType::class;
    }

    /**
     * {@inheritdoc}
     */
    protected function getEditTemplate(): string
    {
        return 'category/category_edit.html.twig';
    }

    /**
     * {@inheritdoc}
     */
    protected function getShowTemplate(): string
    {
        return 'category/category_show.html.twig';
    }

    /**
     * {@inheritdoc}
     */
    protected function getTableTemplate(): string
    {
        return 'category/category_table.html.twig';
    }
}
