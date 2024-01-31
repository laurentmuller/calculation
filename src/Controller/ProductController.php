<?php
/*
 * This file is part of the Calculation package.
 *
 * (c) bibi.nu <bibi@bibi.nu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace App\Controller;

use App\Attribute\Get;
use App\Attribute\GetDelete;
use App\Attribute\GetPost;
use App\Entity\Category;
use App\Entity\Product;
use App\Interfaces\EntityInterface;
use App\Interfaces\RoleInterface;
use App\Report\ProductsReport;
use App\Repository\ProductRepository;
use App\Response\PdfResponse;
use App\Response\SpreadsheetResponse;
use App\Spreadsheet\ProductsDocument;
use App\Table\DataQuery;
use App\Table\ProductTable;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * The controller for product entities.
 *
 * @template-extends AbstractEntityController<Product, ProductRepository>
 */
#[AsController]
#[Route(path: '/product')]
#[IsGranted(RoleInterface::ROLE_USER)]
class ProductController extends AbstractEntityController
{
    public function __construct(ProductRepository $repository)
    {
        parent::__construct($repository);
    }

    /**
     * Add a product.
     */
    #[GetPost(path: '/add', name: 'product_add')]
    public function add(Request $request): Response
    {
        $item = new Product();
        $category = $this->getApplication()->getDefaultCategory();
        if ($category instanceof Category) {
            $item->setCategory($category);
        }

        return $this->editEntity($request, $item);
    }

    /**
     * Clone (copy) a product.
     */
    #[GetPost(path: '/clone/{id}', name: 'product_clone', requirements: ['id' => Requirement::DIGITS])]
    public function clone(Request $request, Product $item): Response
    {
        $description = $this->trans('common.clone_description', ['%description%' => $item->getDescription()]);
        $clone = $item->clone($description);
        $parameters = [
            'title' => 'product.clone.title',
            'params' => ['id' => $item->getId()],
        ];

        return $this->editEntity($request, $clone, $parameters);
    }

    /**
     * Delete a product.
     */
    #[GetDelete(path: '/delete/{id}', name: 'product_delete', requirements: ['id' => Requirement::DIGITS])]
    public function delete(Request $request, Product $item, LoggerInterface $logger): Response
    {
        return $this->deleteEntity($request, $item, $logger);
    }

    /**
     * Edit a product.
     */
    #[GetPost(path: '/edit/{id}', name: 'product_edit', requirements: ['id' => Requirement::DIGITS])]
    public function edit(Request $request, Product $item): Response
    {
        return $this->editEntity($request, $item);
    }

    /**
     * Export the products to a Spreadsheet document.
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    #[Get(path: '/excel', name: 'product_excel')]
    public function excel(ProductRepository $repository): SpreadsheetResponse
    {
        $entities = $repository->findByDescription();
        if ([] === $entities) {
            $message = $this->trans('product.list.empty');
            throw $this->createNotFoundException($message);
        }
        $doc = new ProductsDocument($this, $entities);

        return $this->renderSpreadsheetDocument($doc);
    }

    /**
     * Export the products to a PDF document.
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException if no product is found
     */
    #[Get(path: '/pdf', name: 'product_pdf')]
    public function pdf(ProductRepository $repository): PdfResponse
    {
        $entities = $repository->findByGroup();
        if ([] === $entities) {
            $message = $this->trans('product.list.empty');
            throw $this->createNotFoundException($message);
        }
        $doc = new ProductsReport($this, $entities);

        return $this->renderPdfDocument($doc);
    }

    /**
     * Show properties of a product.
     */
    #[Get(path: '/show/{id}', name: 'product_show', requirements: ['id' => Requirement::DIGITS])]
    public function show(Product $item): Response
    {
        return $this->showEntity($item);
    }

    /**
     * Render the table view.
     */
    #[Get(path: '', name: 'product_table')]
    public function table(
        ProductTable $table,
        LoggerInterface $logger,
        #[MapQueryString]
        DataQuery $query = new DataQuery()
    ): Response {
        return $this->handleTableRequest($table, $logger, $query, 'product/product_table.html.twig');
    }

    /**
     * @psalm-param Product $item
     */
    protected function deleteFromDatabase(EntityInterface $item): void
    {
        $this->getApplication()->updateDeletedProduct($item);
        parent::deleteFromDatabase($item);
    }
}
