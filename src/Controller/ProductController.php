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

use App\DataTable\ProductDataTable;
use App\Entity\AbstractEntity;
use App\Entity\Product;
use App\Excel\ExcelDocument;
use App\Excel\ExcelResponse;
use App\Form\Product\ProductType;
use App\Pdf\PdfResponse;
use App\Report\ProductsReport;
use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;
use Doctrine\Common\Collections\Criteria;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * The controller for product entities.
 *
 * @Route("/product")
 * @IsGranted("ROLE_USER")
 */
class ProductController extends AbstractEntityController
{
    /**
     * The list route.
     */
    private const ROUTE_LIST = 'product_list';

    /**
     * The table route.
     */
    private const ROUTE_TABLE = 'product_table';

    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct(Product::class);
    }

    /**
     * Add a product.
     *
     * @Route("/add", name="product_add", methods={"GET", "POST"})
     */
    public function add(Request $request): Response
    {
        return $this->editEntity($request, new Product());
    }

    /**
     * List the products.
     *
     * @Route("", name="product_list", methods={"GET"})
     */
    public function card(Request $request): Response
    {
        $sortedFields = [
            ['name' => 'description', 'label' => 'product.fields.description'],
            ['name' => 'price', 'label' => 'product.fields.price', 'numeric' => true],
            ['name' => 'category.code', 'label' => 'product.fields.category'],
        ];

        return $this->renderCard($request, 'description', Criteria::ASC, $sortedFields);
    }

    /**
     * Clone (copy) a product.
     *
     * @Route("/clone/{id}", name="product_clone", requirements={"id": "\d+" }, methods={"GET", "POST"})
     */
    public function clone(Request $request, Product $item): Response
    {
        $description = $this->trans('product.add.clone', ['%description%' => $item->getDescription()]);
        $item = $item->clone($description);

        return $this->editEntity($request, $item);
    }

    /**
     * Delete a product.
     *
     * @Route("/delete/{id}", name="product_delete", requirements={"id": "\d+" })
     */
    public function delete(Request $request, Product $item): Response
    {
        $parameters = [
            'title' => 'product.delete.title',
            'message' => 'product.delete.message',
            'success' => 'product.delete.success',
            'failure' => 'product.delete.failure',
        ];

        return $this->deleteEntity($request, $item, $parameters);
    }

    /**
     * Edit a product.
     *
     * @Route("/edit/{id}", name="product_edit", requirements={"id": "\d+" }, methods={"GET", "POST"})
     */
    public function edit(Request $request, Product $item): Response
    {
        return $this->editEntity($request, $item);
    }

    /**
     * Export the products to an Excel document.
     *
     * @Route("/excel", name="product_excel")
     */
    public function excel(ProductRepository $repository): ExcelResponse
    {
        $doc = new ExcelDocument($this->getTranslator());
        $doc->initialize($this, 'product.list.title');

        // headers
        $doc->setHeaderValues([
            'product.fields.group' => Alignment::HORIZONTAL_GENERAL,
            'product.fields.category' => Alignment::HORIZONTAL_GENERAL,
            'product.fields.description' => Alignment::HORIZONTAL_GENERAL,
            'product.fields.price' => Alignment::HORIZONTAL_RIGHT,
            'product.fields.unit' => Alignment::HORIZONTAL_GENERAL,
            'product.fields.supplier' => Alignment::HORIZONTAL_GENERAL,
        ]);

        // formats
        $doc->setFormatAmount(3);

        /** @var Product[] $products */
        $products = $repository->findAllByGroup();

        // rows
        $row = 2;
        foreach ($products as $product) {
            $doc->setRowValues($row++, [
                $product->getParentCode(),
                $product->getCategoryCode(),
                $product->getDescription(),
                $product->getPrice(),
                $product->getUnit(),
                $product->getSupplier(),
                ]);
        }
        $doc->setSelectedCell('A2');

        return $this->renderExcelDocument($doc);
    }

    /**
     * Export the products to a PDF document.
     *
     * @Route("/pdf", name="product_pdf")
     */
    public function pdf(ProductRepository $repository): PdfResponse
    {
        // get products
        $products = $repository->findAllByGroup();
        if (empty($products)) {
            $message = $this->trans('product.list.empty');

            throw $this->createNotFoundException($message);
        }

        // create and render report
        $report = new ProductsReport($this);
        $report->setProducts($products);

        return $this->renderPdfDocument($report);
    }

    /**
     * Show properties of a product.
     *
     * @Route("/show/{id}", name="product_show", requirements={"id": "\d+" }, methods={"GET", "POST"})
     */
    public function show(Product $item): Response
    {
        return $this->showEntity($item);
    }

    /**
     * Render the table view.
     *
     * @Route("/table", name="product_table", methods={"GET", "POST"})
     */
    public function table(Request $request, ProductDataTable $table, CategoryRepository $repository): Response
    {
        $parameters = [];
        if (!$request->isXmlHttpRequest()) {
            $categories = $repository->getListCount();
            $total = \array_sum(\array_column($categories, 'count'));
            $parameters = [
                'categories' => $categories,
                'total' => $total,
            ];
        }

        return $this->renderTable($request, $table, [], $parameters);
    }

    /**
     * {@inheritdoc}
     *
     * @param Product $item
     */
    protected function editEntity(Request $request, AbstractEntity $item, array $parameters = []): Response
    {
        // update parameters
        $parameters['success'] = $item->isNew() ? 'product.add.success' : 'product.edit.success';

        return parent::editEntity($request, $item, $parameters);
    }

    /**
     * {@inheritdoc}
     */
    protected function getCardTemplate(): string
    {
        return 'product/product_card.html.twig';
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
        return ProductType::class;
    }

    /**
     * {@inheritdoc}
     */
    protected function getEditTemplate(): string
    {
        return 'product/product_edit.html.twig';
    }

    /**
     * {@inheritdoc}
     */
    protected function getShowTemplate(): string
    {
        return 'product/product_show.html.twig';
    }

    /**
     * {@inheritdoc}
     */
    protected function getTableTemplate(): string
    {
        return 'product/product_table.html.twig';
    }
}
