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

use App\Entity\AbstractEntity;
use App\Entity\Category;
use App\Form\Category\CategoryType;
use App\Report\CategoriesReport;
use App\Repository\CalculationCategoryRepository;
use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;
use App\Repository\TaskRepository;
use App\Response\PdfResponse;
use App\Response\SpreadsheetResponse;
use App\Spreadsheet\CategoriesDocument;
use App\Table\CategoryTable;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use SlopeIt\BreadcrumbBundle\Annotation\Breadcrumb;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

/**
 * The controller for category entities.
 *
 * @author Laurent Muller
 *
 * @Route("/category")
 * @IsGranted("ROLE_USER")
 * @Breadcrumb({
 *     {"label" = "index.title", "route" = "homepage"}
 * })
 * @template-extends AbstractEntityController<Category>
 */
class CategoryController extends AbstractEntityController
{
    /**
     * Constructor.
     */
    public function __construct(CategoryRepository $repository)
    {
        parent::__construct($repository);
    }

    /**
     * Add a category.
     *
     * @Route("/add", name="category_add")
     * @Breadcrumb({
     *     {"label" = "category.list.title", "route" = "category_table"},
     *     {"label" = "category.add.title"}
     * })
     */
    public function add(Request $request): Response
    {
        return $this->editEntity($request, new Category());
    }

    /**
     * Clone (copy) a category.
     *
     * @Route("/clone/{id}", name="category_clone", requirements={"id" = "\d+"})
     * @Breadcrumb({
     *     {"label" = "category.list.title", "route" = "category_table"},
     *     {"label" = "breadcrumb.clone"}
     * })
     */
    public function clone(Request $request, Category $item): Response
    {
        $code = $this->trans('common.clone_description', ['%description%' => $item->getCode()]);
        $clone = $item->clone($code);
        $parameters = [
            'params' => ['id' => $item->getId()],
        ];

        return $this->editEntity($request, $clone, $parameters);
    }

    /**
     * Delete a category.
     *
     * @Route("/delete/{id}", name="category_delete", requirements={"id" = "\d+"})
     * @Breadcrumb({
     *     {"label" = "category.list.title", "route" = "category_table"},
     *     {"label" = "breadcrumb.delete"},
     *     {"label" = "$item.display"}
     * })
     */
    public function delete(Request $request, Category $item, TaskRepository $taskRepository, ProductRepository $productRepository, CalculationCategoryRepository $categoryRepository, LoggerInterface $logger): Response
    {
        // external references?
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

            // parameters
            $parameters = [
                'item' => $item,
                'id' => $item->getId(),
                'title' => 'category.delete.title',
                'message' => $message,
                'items' => $items,
                'back_page' => $this->getDefaultRoute(),
                'back_text' => 'common.button_back_list',
            ];
            $this->updateQueryParameters($request, $parameters, $item->getId());

            return $this->renderForm('cards/card_warning.html.twig', $parameters);
        }

        $parameters = [
            'title' => 'category.delete.title',
            'message' => 'category.delete.message',
            'success' => 'category.delete.success',
            'failure' => 'category.delete.failure',
        ];

        return $this->deleteEntity($request, $item, $logger, $parameters);
    }

    /**
     * Edit a category.
     *
     * @Route("/edit/{id}", name="category_edit", requirements={"id" = "\d+"})
     * @Breadcrumb({
     *     {"label" = "category.list.title", "route" = "category_table"},
     *     {"label" = "breadcrumb.edit"},
     *     {"label" = "$item.display"}
     * })
     */
    public function edit(Request $request, Category $item): Response
    {
        return $this->editEntity($request, $item);
    }

    /**
     * Export the categories to a Spreadsheet document.
     *
     * @Route("/excel", name="category_excel")
     *
     * @throws NotFoundHttpException if no category is found
     */
    public function excel(): SpreadsheetResponse
    {
        $entities = $this->getEntities('code');
        if (empty($entities)) {
            $message = $this->trans('category.list.empty');
            throw new NotFoundHttpException($message);
        }

        $doc = new CategoriesDocument($this, $entities);

        return $this->renderSpreadsheetDocument($doc);
    }

    /**
     * Export the categories to a PDF document.
     *
     * @Route("/pdf", name="category_pdf")
     *
     * @throws NotFoundHttpException if no category is found
     */
    public function pdf(): PdfResponse
    {
        $entities = $this->getEntities('code');
        if (empty($entities)) {
            $message = $this->trans('category.list.empty');
            throw new NotFoundHttpException($message);
        }

        $doc = new CategoriesReport($this, $entities);

        return $this->renderPdfDocument($doc);
    }

    /**
     * Show properties of a category.
     *
     * @Route("/show/{id}", name="category_show", requirements={"id" = "\d+"})
     * @Breadcrumb({
     *     {"label" = "category.list.title", "route" = "category_table"},
     *     {"label" = "breadcrumb.property"},
     *     {"label" = "$item.display"}
     * })
     */
    public function show(Category $item): Response
    {
        return $this->showEntity($item);
    }

    /**
     * Render the table view.
     *
     * @Route("", name="category_table")
     * @Breadcrumb({
     *     {"label" = "category.list.title"}
     * })
     */
    public function table(Request $request, CategoryTable $table): Response
    {
        return $this->handleTableRequest($request, $table, 'category/category_table.html.twig');
    }

    /**
     * {@inheritdoc}
     */
    protected function editEntity(Request $request, AbstractEntity $item, array $parameters = []): Response
    {
        // update parameters
        $parameters['success'] = $item->isNew() ? 'category.add.success' : 'category.edit.success';

        return parent::editEntity($request, $item, $parameters);
    }

    /**
     * {@inheritdoc}
     */
    protected function getEditFormType(): string
    {
        return CategoryType::class;
    }
}
