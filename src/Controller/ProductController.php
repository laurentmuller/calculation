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

use App\DataTables\ProductDataTable;
use App\Entity\EntityInterface;
use App\Entity\Product;
use App\Form\ProductType;
use App\Pdf\PdfResponse;
use App\Report\ProductsReport;
use Doctrine\Common\Collections\Criteria;
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
class ProductController extends EntityController
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
     * The edit template.
     */
    private const TEMPLATE_EDIT = 'product/product_edit.html.twig';

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
        return $this->editEntity($request, Product());
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

        return $this->renderCard($request, 'product/product_card.html.twig', 'description', Criteria::ASC, $sortedFields);
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
     * Export the products to a PDF document.
     *
     * @Route("/pdf/{limit}/{offset}", name="product_pdf", requirements={"limit": "\d+", "offset": "\d+"})
     */
    public function pdf(Request $request, int $limit = -1, int $offset = 0): PdfResponse
    {
        // get products
        if (-1 === $limit) {
            $products = $this->getRepository()->findAll();
        } else {
            $products = $this->getRepository()->findBy([], ['description' => 'ASC'], $limit, $offset);
        }
        if (empty($products)) {
            $message = $this->trans('product.list.empty');

            throw $this->createNotFoundException($message);
        }

        // create and render report
        $report = new ProductsReport($this);
        $report->setProducts($products);
        $report->setGroupByCategory(-1 === $limit);

        return $this->renderDocument($report);
    }

    /**
     * Show properties of a product.
     *
     * @Route("/show/{id}", name="product_show", requirements={"id": "\d+" }, methods={"GET", "POST"})
     */
    public function show(Request $request, Product $item): Response
    {
        $parameters = [
            'template' => 'product/product_show.html.twig',
        ];

        return $this->showEntity($request, $item, $parameters);
    }

    /**
     * Render the table view.
     *
     * @Route("/table", name="product_table", methods={"GET", "POST"})
     */
    public function table(Request $request, ProductDataTable $table): Response
    {
        return $this->renderTable($request, $table, 'product/product_table.html.twig');
    }

    /**
     * {@inheritdoc}
     *
     * @param Product $item
     */
    protected function editEntity(Request $request, EntityInterface $item, array $parameters = []): Response
    {
        // update parameters
        $parameters['type'] = ProductType::class;
        $parameters['template'] = self::TEMPLATE_EDIT;
        $parameters['route'] = $this->getDefaultRoute();
        $parameters['success'] = $item->isNew() ? 'product.add.success' : 'product.edit.success';

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
