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
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * The controller for task entities.
 *
 * @template-extends AbstractEntityController<Task, TaskRepository>
 */
#[AsController]
#[Route(path: '/task')]
#[IsGranted(RoleInterface::ROLE_USER)]
class TaskController extends AbstractEntityController
{
    public function __construct(TaskRepository $repository)
    {
        parent::__construct($repository);
    }

    /**
     * Add a task.
     */
    #[GetPost(path: '/add', name: 'task_add')]
    public function add(Request $request): Response
    {
        $item = new Task();
        $category = $this->getApplication()->getDefaultCategory();
        if ($category instanceof Category) {
            $item->setCategory($category);
        }

        return $this->editEntity($request, $item);
    }

    /**
     * Edit a copy (cloned) task.
     */
    #[GetPost(path: '/clone/{id}', name: 'task_clone', requirements: ['id' => Requirement::DIGITS])]
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
    #[Get(path: '/compute/{id?}', name: 'task_compute', requirements: ['id' => Requirement::DIGITS])]
    public function compute(Request $request, TaskService $service, Task $task = null): Response
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
    #[GetDelete(path: '/delete/{id}', name: 'task_delete', requirements: ['id' => Requirement::DIGITS])]
    public function delete(Request $request, Task $item, LoggerInterface $logger): Response
    {
        return $this->deleteEntity($request, $item, $logger);
    }

    /**
     * Edit a task.
     */
    #[GetPost(path: '/edit/{id}', name: 'task_edit', requirements: ['id' => Requirement::DIGITS])]
    public function edit(Request $request, Task $item): Response
    {
        return $this->editEntity($request, $item);
    }

    /**
     * Export tasks to a Spreadsheet document.
     *
     * @throws NotFoundHttpException                if no category is found
     * @throws \Doctrine\ORM\Exception\ORMException
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    #[Get(path: '/excel', name: 'task_excel')]
    public function excel(): SpreadsheetResponse
    {
        $entities = $this->getEntities('name');
        if ([] === $entities) {
            $message = $this->trans('task.list.empty');
            throw $this->createNotFoundException($message);
        }
        $doc = new TasksDocument($this, $entities);

        return $this->renderSpreadsheetDocument($doc);
    }

    /**
     * Export tasks to a PDF document.
     *
     * @throws NotFoundHttpException                if no category is found
     * @throws \Doctrine\ORM\Exception\ORMException
     */
    #[Get(path: '/pdf', name: 'task_pdf')]
    public function pdf(): PdfResponse
    {
        $entities = $this->getEntities('name');
        if ([] === $entities) {
            $message = $this->trans('task.list.empty');
            throw $this->createNotFoundException($message);
        }
        $doc = new TasksReport($this, $entities);

        return $this->renderPdfDocument($doc);
    }

    /**
     * Show properties of a task.
     */
    #[Get(path: '/show/{id}', name: 'task_show', requirements: ['id' => Requirement::DIGITS])]
    public function show(Task $item): Response
    {
        return $this->showEntity($item);
    }

    /**
     * Render the table view.
     */
    #[Get(path: '', name: 'task_table')]
    public function table(
        TaskTable $table,
        LoggerInterface $logger,
        #[MapQueryString]
        DataQuery $query = new DataQuery()
    ): Response {
        return $this->handleTableRequest($table, $logger, $query, 'task/task_table.html.twig');
    }

    /**
     * @param Task $item
     */
    protected function editEntity(Request $request, EntityInterface $item, array $parameters = []): Response
    {
        $parameters['item_index'] = $item->count();
        $parameters['margin_index'] = $item->countMargins();

        return parent::editEntity($request, $item, $parameters);
    }

    /**
     * @return array{0: Task[], 1: Task|null}
     */
    private function getTasks(TaskService $service, ?Task $task): array
    {
        if (!$task instanceof Task || $task->isEmpty()) {
            $tasks = $service->getSortedTasks();
            if ([] === $tasks) {
                return [[], null];
            }
            $task = $tasks[0];
        } else {
            $tasks = [$task];
        }

        return [$tasks, $task];
    }
}
