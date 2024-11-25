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
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
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
     * Add a category.
     */
    #[GetPost(path: '/add', name: 'add')]
    public function add(Request $request): Response
    {
        return $this->editEntity($request, new Category());
    }

    /**
     * Clone (copy) a category.
     */
    #[GetPost(path: '/clone/{id}', name: 'clone', requirements: self::ID_REQUIREMENT)]
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
     *
     * @throws \Doctrine\ORM\Exception\ORMException
     */
    #[GetDelete(path: '/delete/{id}', name: 'delete', requirements: self::ID_REQUIREMENT)]
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
     * Edit a category.
     */
    #[GetPost(path: '/edit/{id}', name: 'edit', requirements: self::ID_REQUIREMENT)]
    public function edit(Request $request, Category $item): Response
    {
        return $this->editEntity($request, $item);
    }

    /**
     * Export the categories to a Spreadsheet document.
     *
     * @throws NotFoundHttpException
     * @throws \Doctrine\ORM\Exception\ORMException
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    #[Get(path: '/excel', name: 'excel')]
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
    #[Get(path: '', name: 'index')]
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
     *
     * @throws NotFoundHttpException                if no category is found
     * @throws \Doctrine\ORM\Exception\ORMException
     */
    #[Get(path: '/pdf', name: 'pdf')]
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
    #[Get(path: '/show/{id}', name: 'show', requirements: self::ID_REQUIREMENT)]
    public function show(Category $item): Response
    {
        return $this->showEntity($item);
    }

    /**
     * @psalm-param Category $item
     */
    protected function deleteFromDatabase(EntityInterface $item): void
    {
        $this->getApplicationService()->updateDeletedCategory($item);
        parent::deleteFromDatabase($item);
    }
}
