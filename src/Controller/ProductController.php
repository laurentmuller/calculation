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

use App\Attribute\AddEntityRoute;
use App\Attribute\CloneEntityRoute;
use App\Attribute\DeleteEntityRoute;
use App\Attribute\EditEntityRoute;
use App\Attribute\ExcelRoute;
use App\Attribute\IndexRoute;
use App\Attribute\PdfRoute;
use App\Attribute\ShowEntityRoute;
use App\Entity\Category;
use App\Entity\Product;
use App\Interfaces\EntityInterface;
use App\Interfaces\RoleInterface;
use App\Report\ProductsReport;
use App\Repository\ProductRepository;
use App\Resolver\DataQueryValueResolver;
use App\Response\PdfResponse;
use App\Response\SpreadsheetResponse;
use App\Spreadsheet\ProductsDocument;
use App\Table\DataQuery;
use App\Table\ProductTable;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\ValueResolver;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * The controller for product entities.
 *
 * @extends AbstractEntityController<Product, ProductRepository>
 */
#[Route(path: '/product', name: 'product_')]
#[IsGranted(RoleInterface::ROLE_USER)]
class ProductController extends AbstractEntityController
{
    public function __construct(ProductRepository $repository)
    {
        parent::__construct($repository);
    }

    /**
     * Clone (copy) a product.
     */
    #[CloneEntityRoute]
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
    #[DeleteEntityRoute]
    public function delete(Request $request, Product $item, LoggerInterface $logger): Response
    {
        return $this->deleteEntity($request, $item, $logger);
    }

    /**
     * Add or edit a product.
     */
    #[AddEntityRoute]
    #[EditEntityRoute]
    public function edit(Request $request, ?Product $item): Response
    {
        return $this->editEntity($request, $item ?? $this->createProduct());
    }

    /**
     * Export the products to a Spreadsheet document.
     */
    #[ExcelRoute]
    public function excel(ProductRepository $repository): SpreadsheetResponse
    {
        $entities = $repository->findByDescription();
        if ([] === $entities) {
            throw $this->createTranslatedNotFoundException('product.list.empty');
        }

        return $this->renderSpreadsheetDocument(new ProductsDocument($this, $entities));
    }

    /**
     * Render the table view.
     */
    #[IndexRoute]
    public function index(
        ProductTable $table,
        LoggerInterface $logger,
        #[ValueResolver(DataQueryValueResolver::class)]
        DataQuery $query = new DataQuery()
    ): Response {
        return $this->handleTableRequest($table, $logger, $query, 'product/product_table.html.twig');
    }

    /**
     * Export the products to a PDF document.
     */
    #[PdfRoute]
    public function pdf(ProductRepository $repository): PdfResponse
    {
        $entities = $repository->findByGroup();
        if ([] === $entities) {
            throw $this->createTranslatedNotFoundException('product.list.empty');
        }

        return $this->renderPdfDocument(new ProductsReport($this, $entities));
    }

    /**
     * Show properties of a product.
     */
    #[ShowEntityRoute]
    public function show(Product $item): Response
    {
        return $this->showEntity($item);
    }

    /**
     * @phpstan-param Product $item
     */
    #[\Override]
    protected function deleteFromDatabase(EntityInterface $item): void
    {
        $this->getApplicationService()->updateDeletedProduct($item);
        parent::deleteFromDatabase($item);
    }

    private function createProduct(): Product
    {
        $product = new Product();
        $category = $this->getApplicationService()->getDefaultCategory();
        if ($category instanceof Category) {
            $product->setCategory($category);
        }

        return $product;
    }
}
