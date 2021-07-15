<?php
/*
 * This file is part of the Calculation package.
 *
 * (c) bibi.nu. <bibi@bibi.nu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace App\Controller;

use App\DataTable\ProductDataTable;
use App\Entity\AbstractEntity;
use App\Entity\Product;
use App\Excel\ExcelResponse;
use App\Form\Product\ProductType;
use App\Pdf\PdfResponse;
use App\Report\ProductsReport;
use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;
use App\Spreadsheet\ProductsDocument;
use Doctrine\Common\Collections\Criteria;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use SlopeIt\BreadcrumbBundle\Annotation\Breadcrumb;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * The controller for product entities.
 *
 * @author Laurent Muller
 *
 * @Route("/product")
 * @IsGranted("ROLE_USER")
 * @Breadcrumb({
 *     {"label" = "index.title", "route" = "homepage" },
 *     {"label" = "product.list.title", "route" = "table_product", "params" = {
 *         "id" = "$params.[id]",
 *         "search" = "$params.[search]",
 *         "sort" = "$params.[sort]",
 *         "order" = "$params.[order]",
 *         "offset" = "$params.[offset]",
 *         "limit" = "$params.[limit]",
 *         "view" = "$params.[view]"
 *     }}
 * })
 * @template-extends AbstractEntityController<Product>
 */
class ProductController extends AbstractEntityController
{
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
     * @Route("/add", name="product_add")
     * @Breadcrumb({
     *     {"label" = "breadcrumb.add"}
     * })
     */
    public function add(Request $request): Response
    {
        return $this->editEntity($request, new Product());
    }

    /**
     * List the products.
     *
     * @Route("/card", name="product_card")
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
     * @Route("/clone/{id}", name="product_clone", requirements={"id" = "\d+" })
     * @Breadcrumb({
     *     {"label" = "breadcrumb.clone" }
     * })
     */
    public function clone(Request $request, Product $item): Response
    {
        $description = $this->trans('common.clone_description', ['%description%' => $item->getDescription()]);
        $clone = $item->clone($description);
        $parameters = [
            'params' => ['id' => $item->getId()],
        ];

        return $this->editEntity($request, $clone, $parameters);
    }

    /**
     * Delete a product.
     *
     * @Route("/delete/{id}", name="product_delete", requirements={"id" = "\d+" })
     * @Breadcrumb({
     *     {"label" = "$item.display" },
     *     {"label" = "breadcrumb.delete" }
     * })
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
     * @Route("/edit/{id}", name="product_edit", requirements={"id" = "\d+" })
     * @Breadcrumb({
     *     {"label" = "$item.display" },
     *     {"label" = "breadcrumb.edit" }
     * })
     */
    public function edit(Request $request, Product $item): Response
    {
        return $this->editEntity($request, $item);
    }

    /**
     * Export the products to an Excel document.
     *
     * @Route("/excel", name="product_excel")
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException if no product is found
     */
    public function excel(ProductRepository $repository): ExcelResponse
    {
        /** @var Product[] $entities */
        $entities = $repository->findAllByGroup();
        if (empty($entities)) {
            $message = $this->trans('product.list.empty');
            throw $this->createNotFoundException($message);
        }

        $doc = new ProductsDocument($this, $entities);

        return $this->renderExcelDocument($doc);
    }

    /**
     * Export the products to a PDF document.
     *
     * @Route("/pdf", name="product_pdf")
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException if no product is found
     */
    public function pdf(ProductRepository $repository): PdfResponse
    {
        /** @var Product[] $entities */
        $entities = $repository->findAllByGroup();
        if (empty($entities)) {
            $message = $this->trans('product.list.empty');
            throw $this->createNotFoundException($message);
        }

        $doc = new ProductsReport($this, $entities);

        return $this->renderPdfDocument($doc);
    }

    /**
     * Show properties of a product.
     *
     * @Route("/show/{id}", name="product_show", requirements={"id" = "\d+" })
     * @Breadcrumb({
     *     {"label" = "$item.display" },
     *     {"label" = "breadcrumb.property" }
     * })
     */
    public function show(Product $item): Response
    {
        return $this->showEntity($item);
    }

    /**
     * Render the table view.
     *
     * @Route("", name="product_table")
     */
    public function table(Request $request, ProductDataTable $table, CategoryRepository $repository): Response
    {
        $parameters = [];
        if (!$request->isXmlHttpRequest()) {
            $categories = $repository->getListCountProducts();
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
    protected function getEditFormType(): string
    {
        return ProductType::class;
    }
}
