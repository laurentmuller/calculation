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
use App\Entity\CalculationCategory;
use App\Entity\Category;
use App\Entity\Product;
use App\Entity\Task;
use App\Interfaces\EntityInterface;
use App\Interfaces\RoleInterface;
use App\Report\CategoriesReport;
use App\Repository\CategoryRepository;
use App\Resolver\DataQueryValueResolver;
use App\Response\PdfResponse;
use App\Response\SpreadsheetResponse;
use App\Spreadsheet\CategoriesDocument;
use App\Table\CategoryTable;
use App\Table\DataQuery;
use App\Traits\ArrayTrait;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\ValueResolver;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * The controller for category entities.
 *
 * @extends AbstractEntityController<Category, CategoryRepository>
 */
#[Route(path: '/category', name: 'category_')]
#[IsGranted(RoleInterface::ROLE_USER)]
class CategoryController extends AbstractEntityController
{
    use ArrayTrait;

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
        EntityManagerInterface $entityManager,
        LoggerInterface $logger
    ): Response {
        $references = $this->countReferences($entityManager, $item);
        if ([] !== $references) {
            return $this->showDeleteWarning($item, $references);
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

        return $this->renderSpreadsheetDocument(new CategoriesDocument($this, $entities));
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

        return $this->renderPdfDocument(new CategoriesReport($this, $entities));
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

    /**
     * @return array<string, int>
     */
    private function countReferences(EntityManagerInterface $entityManager, Category $item): array
    {
        return \array_filter([
            'counters.products' => $entityManager->getRepository(Product::class)
                ->countCategoryReferences($item),
            'counters.tasks' => $entityManager->getRepository(Task::class)
                ->countCategoryReferences($item),
            'counters.calculations' => $entityManager->getRepository(CalculationCategory::class)
                ->countCategoryReferences($item),
        ]);
    }

    /**
     * @param array<string, int> $references
     */
    private function showDeleteWarning(Category $item, array $references): Response
    {
        $message = $this->trans('category.delete.failure', ['%name%' => $item]);
        $items = $this->mapKeyAndValue(
            $references,
            fn (string $id, int $count): string => $this->trans($id, ['count' => $count])
        );
        $parameters = [
            'title' => 'category.delete.title',
            'message' => $message,
            'item' => $item,
            'items' => $items,
            'back_page' => $this->getDefaultRoute(),
            'back_text' => 'common.button_back_list',
        ];

        return $this->render('cards/card_warning.html.twig', $parameters);
    }
}
