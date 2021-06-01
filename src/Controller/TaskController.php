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

use App\DataTable\TaskDataTable;
use App\Entity\AbstractEntity;
use App\Entity\Category;
use App\Entity\Task;
use App\Excel\ExcelResponse;
use App\Form\Task\TaskServiceType;
use App\Form\Task\TaskType;
use App\Pdf\PdfResponse;
use App\Report\TasksReport;
use App\Repository\CategoryRepository;
use App\Repository\TaskRepository;
use App\Service\TaskService;
use App\Spreadsheet\TaskDocument;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use SlopeIt\BreadcrumbBundle\Annotation\Breadcrumb;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

/**
 * The controller for task entities.
 *
 * @author Laurent Muller
 *
 * @Route("/task")
 * @IsGranted("ROLE_USER")
 * @Breadcrumb({
 *     {"label" = "index.title", "route" = "homepage" },
 *     {"label" = "task.list.title", "route" = "table_task", "params" = {
 *         "id" = "$params.[id]",
 *         "search" = "$params.[search]",
 *         "sort" = "$params.[sort]",
 *         "order" = "$params.[order]",
 *         "offset" = "$params.[offset]",
 *         "limit" = "$params.[limit]",
 *         "view" = "$params.[view]"
 *     }}
 * })
 * @template-extends AbstractEntityController<Task>
 */
class TaskController extends AbstractEntityController
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct(Task::class);
    }

    /**
     * Add a task.
     *
     * @Route("/add", name="task_add")
     * @Breadcrumb({
     *     {"label" = "breadcrumb.add"}
     * })
     */
    public function add(Request $request): Response
    {
        // create
        $item = new Task();
        if ($category = $this->getApplication()->getDefaultCategory()) {
            $item->setCategory($category);
        }

        return $this->editEntity($request, $item);
    }

    /**
     * List the taks.
     *
     * @Route("/card", name="task_card")
     */
    public function card(Request $request): Response
    {
        return $this->renderCard($request, 'name');
    }

    /**
     * Edit a copy (cloned) task.
     *
     * @Route("/clone/{id}", name="task_clone", requirements={"id" = "\d+" })
     * @Breadcrumb({
     *     {"label" = "breadcrumb.clone" }
     * })
     */
    public function clone(Request $request, Task $item): Response
    {
        // clone
        $category = $this->getApplication()->getDefaultCategory();
        $name = $this->trans('common.clone_description', ['%description%' => $item->getName()]);
        $clone = $item->clone($name, $category);
        $parameters = [
            'params' => ['id' => $item->getId()],
        ];

        return $this->editEntity($request, $clone, $parameters);
    }

    /**
     * Display the form to compute a task.
     *
     * @Route("/compute/{id}", name="task_compute", requirements={"id" = "\d+" })
     * @Breadcrumb({
     *     {"label" = "taskcompute.title" }
     * })
     */
    public function compute(Request $request, Task $task = null, TaskService $service, TaskRepository $repository): Response
    {
        // get tasks
        $tasks = $repository->getSortedBuilder(false)
            ->getQuery()
            ->getResult();

        // set task
        if (null === $task || $task->isEmpty()) {
            $task = $tasks[0];
        }
        $service->setTask($task, true)
            ->compute();

        $form = $this->createForm(TaskServiceType::class, $service);
        if ($this->handleRequestForm($request, $form)) {
            $service->compute($request);
        }

        $parameters = [
            'form' => $form->createView(),
            'tasks' => $tasks,
        ];
        $this->updateQueryParameters($request, $parameters, (int) $task->getId());

        return $this->render('task/task_compute.html.twig', $parameters);
    }

    /**
     * Delete a task.
     *
     * @Route("/delete/{id}", name="task_delete", requirements={"id" = "\d+" })
     * @Breadcrumb({
     *     {"label" = "$item.display" },
     *     {"label" = "breadcrumb.delete" }
     * })
     */
    public function delete(Request $request, Task $item): Response
    {
        $parameters = [
            'title' => 'task.delete.title',
            'message' => 'task.delete.message',
            'success' => 'task.delete.success',
            'failure' => 'task.delete.failure',
        ];

        return $this->deleteEntity($request, $item, $parameters);
    }

    /**
     * Edit a task.
     *
     * @Route("/edit/{id}", name="task_edit", requirements={"id" = "\d+" })
     * @Breadcrumb({
     *     {"label" = "$item.display" },
     *     {"label" = "breadcrumb.edit" }
     * })
     */
    public function edit(Request $request, Task $item): Response
    {
        return $this->editEntity($request, $item);
    }

    /**
     * Export the tasks to an Excel document.
     *
     * @Route("/excel", name="task_excel")
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException if no category is found
     */
    public function excel(): ExcelResponse
    {
        /** @var Category[] $entities */
        $entities = $this->getEntities('name');
        if (empty($entities)) {
            $message = $this->trans('task.list.empty');
            throw new NotFoundHttpException($message);
        }

        $doc = new TaskDocument($this, $entities);

        return $this->renderExcelDocument($doc);
    }

    /**
     * Export the tasks to a PDF document.
     *
     * @Route("/pdf", name="task_pdf")
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException if no category is found
     */
    public function pdf(): PdfResponse
    {
        /** @var Category[] $entities */
        $entities = $this->getEntities('name');
        if (empty($entities)) {
            $message = $this->trans('task.list.empty');
            throw new NotFoundHttpException($message);
        }

        $doc = new TasksReport($this, $entities);

        return $this->renderPdfDocument($doc);
    }

    /**
     * Show properties of a task.
     *
     * @Route("/show/{id}", name="task_show", requirements={"id" = "\d+" })
     * @Breadcrumb({
     *     {"label" = "$item.display" },
     *     {"label" = "breadcrumb.property" }
     * })
     */
    public function show(Task $item): Response
    {
        return $this->showEntity($item);
    }

    /**
     * Render the table view.
     *
     * @Route("", name="task_table")
     */
    public function table(Request $request, TaskDataTable $table, CategoryRepository $repository): Response
    {
        $parameters = [];
        if (!$request->isXmlHttpRequest()) {
            $categories = $repository->getListCountTasks();
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
     *
     * @param Task $item
     */
    protected function editEntity(Request $request, AbstractEntity $item, array $parameters = []): Response
    {
        /** @var Task $item */
        $parameters = [
            'item_index' => $item->count(),
            'margin_index' => $item->countMargins(),
            'success' => $item->isNew() ? 'task.add.success' : 'task.edit.success',
        ];

        return parent::editEntity($request, $item, $parameters);
    }

    /**
     * {@inheritdoc}
     */
    protected function getEditFormType(): string
    {
        return TaskType::class;
    }
}
