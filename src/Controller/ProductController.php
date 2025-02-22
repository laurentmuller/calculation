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
     * Add a product.
     */
    #[GetPost(path: '/add', name: 'add')]
    public function add(Request $request): Response
    {
        $item = new Product();
        $category = $this->getApplicationService()->getDefaultCategory();
        if ($category instanceof Category) {
            $item->setCategory($category);
        }

        return $this->editEntity($request, $item);
    }

    /**
     * Clone (copy) a product.
     */
    #[GetPost(path: '/clone/{id}', name: 'clone', requirements: self::ID_REQUIREMENT)]
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
    #[GetDelete(path: '/delete/{id}', name: 'delete', requirements: self::ID_REQUIREMENT)]
    public function delete(Request $request, Product $item, LoggerInterface $logger): Response
    {
        return $this->deleteEntity($request, $item, $logger);
    }

    /**
     * Edit a product.
     */
    #[GetPost(path: '/edit/{id}', name: 'edit', requirements: self::ID_REQUIREMENT)]
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
    #[Get(path: '/excel', name: 'excel')]
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
    #[Get(path: '', name: 'index')]
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
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException if no product is found
     */
    #[Get(path: '/pdf', name: 'pdf')]
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
    #[Get(path: '/show/{id}', name: 'show', requirements: self::ID_REQUIREMENT)]
    public function show(Product $item): Response
    {
        return $this->showEntity($item);
    }

    /**
     * @psalm-param Product $item
     */
    #[\Override]
    protected function deleteFromDatabase(EntityInterface $item): void
    {
        $this->getApplicationService()->updateDeletedProduct($item);
        parent::deleteFromDatabase($item);
    }
}
