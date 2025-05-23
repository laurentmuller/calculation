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
use App\Resolver\DataQueryValueResolver;
use App\Response\PdfResponse;
use App\Response\SpreadsheetResponse;
use App\Spreadsheet\ProductsDocument;
use App\Table\DataQuery;
use App\Table\ProductTable;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Attribute\ValueResolver;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * The controller for product entities.
 *
 * @template-extends AbstractEntityController<Product, ProductRepository>
 */
#[AsController]
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
    #[GetPost(path: self::CLONE_PATH, name: self::CLONE_NAME, requirements: self::ID_REQUIREMENT)]
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
    #[GetDelete(path: self::DELETE_PATH, name: self::DELETE_NAME, requirements: self::ID_REQUIREMENT)]
    public function delete(Request $request, Product $item, LoggerInterface $logger): Response
    {
        return $this->deleteEntity($request, $item, $logger);
    }

    /**
     * Add or edit a product.
     */
    #[GetPost(path: self::ADD_PATH, name: self::ADD_NAME)]
    #[GetPost(path: self::EDIT_PATH, name: self::EDIT_NAME, requirements: self::ID_REQUIREMENT)]
    public function edit(Request $request, ?Product $item): Response
    {
        return $this->editEntity($request, $item ?? $this->createProduct());
    }

    /**
     * Export the products to a Spreadsheet document.
     */
    #[Get(path: self::EXCEL_PATH, name: self::EXCEL_NAME)]
    public function excel(ProductRepository $repository): SpreadsheetResponse
    {
        $entities = $repository->findByDescription();
        if ([] === $entities) {
            throw $this->createTranslatedNotFoundException('product.list.empty');
        }
        $doc = new ProductsDocument($this, $entities);

        return $this->renderSpreadsheetDocument($doc);
    }

    /**
     * Render the table view.
     */
    #[Get(path: self::INDEX_PATH, name: self::INDEX_NAME)]
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
    #[Get(path: self::PDF_PATH, name: self::PDF_NAME)]
    public function pdf(ProductRepository $repository): PdfResponse
    {
        $entities = $repository->findByGroup();
        if ([] === $entities) {
            throw $this->createTranslatedNotFoundException('product.list.empty');
        }
        $doc = new ProductsReport($this, $entities);

        return $this->renderPdfDocument($doc);
    }

    /**
     * Show properties of a product.
     */
    #[Get(path: self::SHOW_PATH, name: self::SHOW_NAME, requirements: self::ID_REQUIREMENT)]
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
