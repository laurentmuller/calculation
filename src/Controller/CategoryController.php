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

use App\DataTable\CategoryDataTable;
use App\Entity\AbstractEntity;
use App\Entity\Category;
use App\Excel\ExcelResponse;
use App\Form\Category\CategoryType;
use App\Pdf\PdfResponse;
use App\Report\CategoriesReport;
use App\Repository\CalculationCategoryRepository;
use App\Repository\ProductRepository;
use App\Repository\TaskRepository;
use App\Spreadsheet\CategoryDocument;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
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
 */
class CategoryController extends AbstractEntityController
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct(Category::class);
    }

    /**
     * Add a category.
     *
     * @Route("/add", name="category_add")
     */
    public function add(Request $request): Response
    {
        return $this->editEntity($request, new Category());
    }

    /**
     * List the categories.
     *
     * @Route("/card", name="category_card")
     */
    public function card(Request $request): Response
    {
        return $this->renderCard($request, 'code');
    }

    /**
     * Delete a category.
     *
     * @Route("/delete/{id}", name="category_delete", requirements={"id": "\d+" })
     */
    public function delete(Request $request, Category $item, TaskRepository $taskRepository, ProductRepository $productRepository, CalculationCategoryRepository $categoryRepository): Response
    {
        // external references?
        $tasks = $taskRepository->countCategoryReferences($item);
        $products = $productRepository->countCategoryReferences($item);
        $calculations = $categoryRepository->countCategoryReferences($item);

        if (0 !== $tasks || 0 !== $products || 0 !== $calculations) {
            $items = [];
            if (0 !== $tasks) {
                $items[] = $this->trans('counters.tasks', ['count' => $tasks]);
            }
            if (0 !== $products) {
                $items[] = $this->trans('counters.products', ['count' => $products]);
            }
            if (0 !== $calculations) {
                $items[] = $this->trans('counters.calculations', ['count' => $calculations]);
            }
            $message = $this->trans('category.delete.failure', ['%name%' => $item->getDisplay()]);

            $parameters = [
                'id' => $item->getId(),
                'title' => 'category.delete.title',
                'message' => $message,
                'items' => $items,
                'back_page' => $this->getDefaultRoute(),
                'back_text' => 'common.button_back_list',
            ];

            return $this->render('cards/card_warning.html.twig', $parameters);
        }

        $parameters = [
            'title' => 'category.delete.title',
            'message' => 'category.delete.message',
            'success' => 'category.delete.success',
            'failure' => 'category.delete.failure',
        ];

        return $this->deleteEntity($request, $item, $parameters);
    }

    /**
     * Edit a category.
     *
     * @Route("/edit/{id}", name="category_edit", requirements={"id": "\d+" })
     */
    public function edit(Request $request, Category $item): Response
    {
        return $this->editEntity($request, $item);
    }

    /**
     * Export the categories to an Excel document.
     *
     * @Route("/excel", name="category_excel")
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException if no category is found
     */
    public function excel(): ExcelResponse
    {
        /** @var Category[] $entities */
        $entities = $this->getEntities('code');
        if (empty($entities)) {
            $message = $this->trans('category.list.empty');
            throw new NotFoundHttpException($message);
        }

        $doc = new CategoryDocument($this, $entities);

        return $this->renderExcelDocument($doc);
    }

    /**
     * Export the categories to a PDF document.
     *
     * @Route("/pdf", name="category_pdf")
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException if no category is found
     */
    public function pdf(): PdfResponse
    {
        /** @var Category[] $entities */
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
     * @Route("/show/{id}", name="category_show", requirements={"id": "\d+" })
     */
    public function show(Category $item): Response
    {
        return $this->showEntity($item);
    }

    /**
     * Render the table view.
     *
     * @Route("", name="category_table")
     */
    public function table(Request $request, CategoryDataTable $table): Response
    {
        // callback?
        $attributes = [];
        if (!$request->isXmlHttpRequest()) {
            $attributes = [
                'link_href' => $this->generateUrl('product_table'),
                'link_title' => $this->trans('category.list.product_title'),
            ];
        }

        return $this->renderTable($request, $table, $attributes);
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
