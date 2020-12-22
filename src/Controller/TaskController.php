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
use App\Repository\TaskRepository;
use App\Service\TaskService;
use App\Spreadsheet\TaskDocument;
use Doctrine\Common\Collections\Criteria;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

/**
 * The controller for task entities.
 *
 * @Route("/task")
 * @IsGranted("ROLE_USER")
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
     * @Route("/add", name="task_add", methods={"GET", "POST"})
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
     * @Route("", name="task_list", methods={"GET"})
     */
    public function card(Request $request): Response
    {
        return $this->renderCard($request, 'name');
    }

    /**
     * Edit a copy (cloned) task.
     *
     * @Route("/clone/{id}", name="task_clone", requirements={"id": "\d+" }, methods={"GET", "POST"})
     */
    public function clone(Request $request, Task $item): Response
    {
        // clone
        $state = $this->getApplication()->getDefaultCategory();
        $clone = $item->clone($state);

        return $this->editEntity($request, $clone);
    }

    /**
     * Display the form to compute a task.
     *
     * @Route("/compute/{id}", name="task_compute", requirements={"id": "\d+" }, methods={"GET", "POST"})
     */
    public function compute(Request $request, Task $task = null, TaskService $service, TaskRepository $repository): Response
    {
        // task?
        if (null !== $task) {
            $service->setTask($task, true)
                ->compute($request);
        }

        // select first task if none
        if (null === $service->getTask()) {
            $task = $repository->findOneBy([], ['name' => Criteria::ASC]);
            $service->setTask($task, true)
                ->compute($request);
        }

        $form = $this->createForm(TaskServiceType::class, $service);
        if ($this->handleRequestForm($request, $form)) {
            $service->compute($request);
        }

        $tasks = $repository->getSortedBuilder(false)
            ->getQuery()
            ->getResult();

        return $this->render('task/task_compute.html.twig', [
            'form' => $form->createView(),
            'tasks' => $tasks,
        ]);
    }

    /**
     * Delete a task.
     *
     * @Route("/delete/{id}", name="task_delete", requirements={"id": "\d+" })
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
     * @Route("/edit/{id}", name="task_edit", requirements={"id": "\d+" }, methods={"GET", "POST"})
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
     * @Route("/show/{id}", name="task_show", requirements={"id": "\d+" }, methods={"GET", "POST"})
     */
    public function show(Task $item): Response
    {
        return $this->showEntity($item);
    }

    /**
     * Render the table view.
     *
     * @Route("/table", name="task_table", methods={"GET", "POST"})
     */
    public function table(Request $request, TaskDataTable $table): Response
    {
        return $this->renderTable($request, $table);
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
