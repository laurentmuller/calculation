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
use App\Interfaces\EntityInterface;
use App\Interfaces\RoleInterface;
use App\Report\CategoriesReport;
use App\Repository\CalculationCategoryRepository;
use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;
use App\Repository\TaskRepository;
use App\Resolver\DataQueryValueResolver;
use App\Response\PdfResponse;
use App\Response\SpreadsheetResponse;
use App\Spreadsheet\CategoriesDocument;
use App\Table\CategoryTable;
use App\Table\DataQuery;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Attribute\ValueResolver;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * The controller for category entities.
 *
 * @template-extends AbstractEntityController<Category, CategoryRepository>
 */
#[AsController]
#[Route(path: '/category', name: 'category_')]
#[IsGranted(RoleInterface::ROLE_USER)]
class CategoryController extends AbstractEntityController
{
    public function __construct(CategoryRepository $repository)
    {
        parent::__construct($repository);
    }

    /**
     * Clone (copy) a category.
     */
    #[CloneEntityRoute]
    public function clone(Request $request, Category $item): Response
    {
        $code = $this->trans('common.clone_description', ['%description%' => $item->getCode()]);
        $clone = $item->clone($code);
        $parameters = [
            'title' => 'category.clone.title',
            'params' => ['id' => $item->getId()],
        ];

        return $this->editEntity($request, $clone, $parameters);
    }

    /**
     * Delete a category.
     */
    #[DeleteEntityRoute]
    public function delete(
        Request $request,
        Category $item,
        TaskRepository $taskRepository,
        ProductRepository $productRepository,
        CalculationCategoryRepository $categoryRepository,
        LoggerInterface $logger
    ): Response {
        $tasks = $taskRepository->countCategoryReferences($item);
        $products = $productRepository->countCategoryReferences($item);
        $calculations = $categoryRepository->countCategoryReferences($item);
        if (0 !== $tasks || 0 !== $products || 0 !== $calculations) {
            $items = [];
            if (0 !== $calculations) {
                $items[] = $this->trans('counters.calculations', ['count' => $calculations]);
            }
            if (0 !== $products) {
                $items[] = $this->trans('counters.products', ['count' => $products]);
            }
            if (0 !== $tasks) {
                $items[] = $this->trans('counters.tasks', ['count' => $tasks]);
            }
            $message = $this->trans('category.delete.failure', ['%name%' => $item->getDisplay()]);
            $parameters = [
                'title' => 'category.delete.title',
                'message' => $message,
                'item' => $item,
                'items' => $items,
                'back_page' => $this->getDefaultRoute(),
                'back_text' => 'common.button_back_list',
            ];
            $this->updateQueryParameters($request, $parameters, $item);

            return $this->render('cards/card_warning.html.twig', $parameters);
        }

        return $this->deleteEntity($request, $item, $logger);
    }

    /**
     * Add or edit a category.
     */
    #[AddEntityRoute]
    #[EditEntityRoute]
    public function edit(Request $request, ?Category $item): Response
    {
        return $this->editEntity($request, $item ?? new Category());
    }

    /**
     * Export the categories to a Spreadsheet document.
     */
    #[ExcelRoute]
    public function excel(): SpreadsheetResponse
    {
        $entities = $this->getEntities('code');
        if ([] === $entities) {
            throw $this->createTranslatedNotFoundException('category.list.empty');
        }
        $doc = new CategoriesDocument($this, $entities);

        return $this->renderSpreadsheetDocument($doc);
    }

    /**
     * Render the table view.
     */
    #[IndexRoute]
    public function index(
        CategoryTable $table,
        LoggerInterface $logger,
        #[ValueResolver(DataQueryValueResolver::class)]
        DataQuery $query = new DataQuery()
    ): Response {
        return $this->handleTableRequest($table, $logger, $query, 'category/category_table.html.twig');
    }

    /**
     * Export the categories to a PDF document.
     */
    #[PdfRoute]
    public function pdf(): PdfResponse
    {
        $entities = $this->getEntities('code');
        if ([] === $entities) {
            throw $this->createTranslatedNotFoundException('category.list.empty');
        }
        $doc = new CategoriesReport($this, $entities);

        return $this->renderPdfDocument($doc);
    }

    /**
     * Show properties of a category.
     */
    #[ShowEntityRoute]
    public function show(Category $item): Response
    {
        return $this->showEntity($item);
    }

    /**
     * @phpstan-param Category $item
     */
    #[\Override]
    protected function deleteFromDatabase(EntityInterface $item): void
    {
        $this->getApplicationService()->updateDeletedCategory($item);
        parent::deleteFromDatabase($item);
    }
}
