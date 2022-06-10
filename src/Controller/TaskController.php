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

use App\Entity\AbstractEntity;
use App\Entity\Task;
use App\Form\Task\TaskServiceType;
use App\Form\Task\TaskType;
use App\Report\TasksReport;
use App\Repository\TaskRepository;
use App\Response\PdfResponse;
use App\Response\SpreadsheetResponse;
use App\Service\TaskService;
use App\Spreadsheet\TasksDocument;
use App\Table\TaskTable;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * The controller for task entities.
 *
 * @template-extends AbstractEntityController<Task>
 */
#[AsController]
#[IsGranted('ROLE_USER')]
#[Route(path: '/task')]
class TaskController extends AbstractEntityController
{
    /**
     * Constructor.
     */
    public function __construct(TranslatorInterface $translator, TaskRepository $repository)
    {
        parent::__construct($translator, $repository);
    }

    /**
     * Add a task.
     */
    #[Route(path: '/add', name: 'task_add')]
    public function add(Request $request): Response
    {
        $item = new Task();
        if (null !== ($category = $this->getApplication()->getDefaultCategory())) {
            $item->setCategory($category);
        }

        return $this->editEntity($request, $item);
    }

    /**
     * Edit a copy (cloned) task.
     */
    #[Route(path: '/clone/{id}', name: 'task_clone', requirements: ['id' => self::DIGITS])]
    public function clone(Request $request, Task $item): Response
    {
        $name = $this->trans('common.clone_description', ['%description%' => $item->getName()]);
        $clone = $item->clone($name);
        $parameters = [
            'params' => ['id' => $item->getId()],
        ];

        return $this->editEntity($request, $clone, $parameters);
    }

    /**
     * Compute a task.
     */
    #[Route(path: '/compute/{id}', name: 'task_compute', requirements: ['id' => self::DIGITS])]
    public function compute(Request $request, TaskService $service, TaskRepository $repository, Task $task = null): Response
    {
        // get tasks
        /** @var Task[] $tasks */
        $tasks = $repository->getSortedBuilder(false)
            ->getQuery()
            ->getResult();

        // set task
        if (null === $task || $task->isEmpty()) {
            $task = $tasks[0];
        } else {
            $tasks = [$task];
        }
        $service->setTask($task, true)
            ->compute();

        $form = $this->createForm(TaskServiceType::class, $service, ['simple_widget' => 1 === \count($tasks)]);
        if ($this->handleRequestForm($request, $form)) {
            $service->compute($request);
        }
        $parameters = [
            'form' => $form,
            'tasks' => $tasks,
        ];
        $this->updateQueryParameters($request, $parameters, (int) $task->getId());

        return $this->renderForm('task/task_compute.html.twig', $parameters);
    }

    /**
     * Delete a task.
     */
    #[Route(path: '/delete/{id}', name: 'task_delete', requirements: ['id' => self::DIGITS])]
    public function delete(Request $request, Task $item, LoggerInterface $logger): Response
    {
        $parameters = [
            'title' => 'task.delete.title',
            'message' => 'task.delete.message',
            'success' => 'task.delete.success',
            'failure' => 'task.delete.failure',
        ];

        return $this->deleteEntity($request, $item, $logger, $parameters);
    }

    /**
     * Edit a task.
     */
    #[Route(path: '/edit/{id}', name: 'task_edit', requirements: ['id' => self::DIGITS])]
    public function edit(Request $request, Task $item): Response
    {
        return $this->editEntity($request, $item);
    }

    /**
     * Export tasks to a Spreadsheet document.
     *
     * @throws NotFoundHttpException               if no category is found
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    #[Route(path: '/excel', name: 'task_excel')]
    public function excel(): SpreadsheetResponse
    {
        $entities = $this->getEntities('name');
        if (empty($entities)) {
            $message = $this->trans('task.list.empty');
            throw new NotFoundHttpException($message);
        }
        $doc = new TasksDocument($this, $entities);

        return $this->renderSpreadsheetDocument($doc);
    }

    /**
     * Export tasks to a PDF document.
     *
     * @throws NotFoundHttpException if no category is found
     */
    #[Route(path: '/pdf', name: 'task_pdf')]
    public function pdf(): PdfResponse
    {
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
     */
    #[Route(path: '/show/{id}', name: 'task_show', requirements: ['id' => self::DIGITS])]
    public function show(Task $item): Response
    {
        return $this->showEntity($item);
    }

    /**
     * Render the table view.
     */
    #[Route(path: '', name: 'task_table')]
    public function table(Request $request, TaskTable $table): Response
    {
        return $this->handleTableRequest($request, $table, 'task/task_table.html.twig');
    }

    /**
     * {@inheritdoc}
     *
     * @param Task $item
     */
    protected function editEntity(Request $request, AbstractEntity $item, array $parameters = []): Response
    {
        $parameters['item_index'] = $item->count();
        $parameters['margin_index'] = $item->countMargins();
        $parameters['success'] = $item->isNew() ? 'task.add.success' : 'task.edit.success';

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
