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
use App\Entity\Task;
use App\Form\Task\TaskServiceType;
use App\Interfaces\EntityInterface;
use App\Interfaces\RoleInterface;
use App\Model\TaskComputeQuery;
use App\Report\TasksReport;
use App\Repository\TaskRepository;
use App\Resolver\DataQueryValueResolver;
use App\Response\PdfResponse;
use App\Response\SpreadsheetResponse;
use App\Service\TaskService;
use App\Spreadsheet\TasksDocument;
use App\Table\DataQuery;
use App\Table\TaskTable;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Attribute\ValueResolver;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * The controller for task entities.
 *
 * @template-extends AbstractEntityController<Task, TaskRepository>
 */
#[AsController]
#[Route(path: '/task', name: 'task_')]
#[IsGranted(RoleInterface::ROLE_USER)]
class TaskController extends AbstractEntityController
{
    public function __construct(TaskRepository $repository)
    {
        parent::__construct($repository);
    }

    /**
     * Edit a copy (cloned) task.
     */
    #[GetPost(path: self::CLONE_PATH, name: self::CLONE_NAME, requirements: self::ID_REQUIREMENT)]
    public function clone(Request $request, Task $item): Response
    {
        $name = $this->trans('common.clone_description', ['%description%' => $item->getName()]);
        $clone = $item->clone($name);
        $parameters = [
            'title' => 'task.clone.title',
            'params' => ['id' => $item->getId()],
        ];

        return $this->editEntity($request, $clone, $parameters);
    }

    /**
     * Display the page to compute a task.
     */
    #[Get(path: '/compute/{id?}', name: 'compute', requirements: self::ID_REQUIREMENT)]
    public function compute(Request $request, TaskService $service, ?Task $task = null): Response
    {
        [$tasks, $task] = $this->getTasks($service, $task);
        if (null === $task) {
            $this->warningTrans('task.list.empty');

            return $this->redirectToDefaultRoute($request);
        }

        $query = TaskComputeQuery::instance($task);
        $result = $service->computeQuery($query);
        $simple_widget = 1 === \count($tasks);
        $form = $this->createForm(TaskServiceType::class, $result, ['simple_widget' => $simple_widget]);
        $parameters = [
            'form' => $form,
            'tasks' => $tasks,
        ];
        $this->updateQueryParameters($request, $parameters, $task);

        return $this->render('task/task_compute.html.twig', $parameters);
    }

    /**
     * Delete a task.
     */
    #[GetDelete(path: self::DELETE_PATH, name: self::DELETE_NAME, requirements: self::ID_REQUIREMENT)]
    public function delete(Request $request, Task $item, LoggerInterface $logger): Response
    {
        return $this->deleteEntity($request, $item, $logger);
    }

    /**
     * Add or edit a task.
     */
    #[GetPost(path: self::ADD_PATH, name: self::ADD_NAME)]
    #[GetPost(path: self::EDIT_PATH, name: self::EDIT_NAME, requirements: self::ID_REQUIREMENT)]
    public function edit(Request $request, ?Task $item): Response
    {
        return $this->editEntity($request, $item ?? $this->createTask());
    }

    /**
     * Export tasks to a Spreadsheet document.
     */
    #[Get(path: self::EXCEL_PATH, name: self::EXCEL_NAME)]
    public function excel(): SpreadsheetResponse
    {
        $entities = $this->getEntities('name');
        if ([] === $entities) {
            throw $this->createTranslatedNotFoundException('task.list.empty');
        }
        $doc = new TasksDocument($this, $entities);

        return $this->renderSpreadsheetDocument($doc);
    }

    /**
     * Render the table view.
     */
    #[Get(path: self::INDEX_PATH, name: self::INDEX_NAME)]
    public function index(
        TaskTable $table,
        LoggerInterface $logger,
        #[ValueResolver(DataQueryValueResolver::class)]
        DataQuery $query = new DataQuery()
    ): Response {
        return $this->handleTableRequest($table, $logger, $query, 'task/task_table.html.twig');
    }

    /**
     * Export tasks to a PDF document.
     */
    #[Get(path: self::PDF_PATH, name: self::PDF_NAME)]
    public function pdf(): PdfResponse
    {
        $entities = $this->getEntities('name');
        if ([] === $entities) {
            throw $this->createTranslatedNotFoundException('task.list.empty');
        }
        $doc = new TasksReport($this, $entities);

        return $this->renderPdfDocument($doc);
    }

    /**
     * Show properties of a task.
     */
    #[Get(path: self::SHOW_PATH, name: self::SHOW_NAME, requirements: self::ID_REQUIREMENT)]
    public function show(Task $item): Response
    {
        return $this->showEntity($item);
    }

    /**
     * @param Task $item
     */
    #[\Override]
    protected function editEntity(Request $request, EntityInterface $item, array $parameters = []): Response
    {
        $parameters['item_index'] = $item->count();
        $parameters['margin_index'] = $item->countMargins();

        return parent::editEntity($request, $item, $parameters);
    }

    private function createTask(): Task
    {
        $task = new Task();
        $category = $this->getApplicationService()->getDefaultCategory();
        if ($category instanceof Category) {
            $task->setCategory($category);
        }

        return $task;
    }

    /**
     * @return array{0: Task[], 1: Task|null}
     */
    private function getTasks(TaskService $service, ?Task $task): array
    {
        if ($task instanceof Task && !$task->isEmpty()) {
            return [[$task], $task];
        }

        $tasks = $service->getSortedTasks();
        if ([] === $tasks) {
            return [[], null];
        }

        return [$tasks, $tasks[0]];
    }
}
