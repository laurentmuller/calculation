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
use App\Interfaces\RoleInterface;
use App\Model\TaskComputeQuery;
use App\Report\TasksReport;
use App\Repository\TaskRepository;
use App\Response\PdfResponse;
use App\Response\SpreadsheetResponse;
use App\Service\TaskService;
use App\Spreadsheet\TasksDocument;
use App\Table\TaskTable;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * The controller for task entities.
 *
 * @template-extends AbstractEntityController<Task>
 */
#[AsController]
#[Route(path: '/task')]
#[IsGranted(RoleInterface::ROLE_USER)]
class TaskController extends AbstractEntityController
{
    /**
     * Constructor.
     *
     * @throws \ReflectionException
     */
    public function __construct(TaskRepository $repository)
    {
        parent::__construct($repository);
    }

    /**
     * Add a task.
     *
     * @throws \Psr\Cache\InvalidArgumentException
     * @throws \Psr\Container\ContainerExceptionInterface
     */
    #[Route(path: '/add', name: 'task_add')]
    public function add(Request $request): Response
    {
        $item = new Task();
        if (null !== $category = $this->getApplication()->getDefaultCategory()) {
            $item->setCategory($category);
        }

        return $this->editEntity($request, $item);
    }

    /**
     * Edit a copy (cloned) task.
     *
     * @throws \Psr\Container\ContainerExceptionInterface
     */
    #[Route(path: '/clone/{id}', name: 'task_clone', requirements: ['id' => Requirement::DIGITS])]
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
     * Display the page to compute a task.
     */
    #[Route(path: '/compute/{id?}', name: 'task_compute', requirements: ['id' => Requirement::DIGITS])]
    public function compute(Request $request, TaskService $service, Task $task = null): Response
    {
        /** @var Task[] $tasks */
        $tasks = $service->getSortedTasks();
        if (null === $task || $task->isEmpty()) {
            $task = $tasks[0];
        } else {
            $tasks = [$task];
        }

        $query = new TaskComputeQuery($task, true);
        $result = $service->computeQuery($query);

        $simple_widget = 1 === \count($tasks);
        $form = $this->createForm(TaskServiceType::class, $result, ['simple_widget' => $simple_widget]);
        $parameters = [
            'form' => $form,
            'tasks' => $tasks,
        ];
        $this->updateQueryParameters($request, $parameters, $task->getId());

        return $this->render('task/task_compute.html.twig', $parameters);
    }

    /**
     * Delete a task.
     *
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \ReflectionException
     */
    #[Route(path: '/delete/{id}', name: 'task_delete', requirements: ['id' => Requirement::DIGITS])]
    public function delete(Request $request, Task $item, LoggerInterface $logger): Response
    {
        return $this->deleteEntity($request, $item, $logger);
    }

    /**
     * Edit a task.
     *
     * @throws \Psr\Container\ContainerExceptionInterface
     */
    #[Route(path: '/edit/{id}', name: 'task_edit', requirements: ['id' => Requirement::DIGITS])]
    public function edit(Request $request, Task $item): Response
    {
        return $this->editEntity($request, $item);
    }

    /**
     * Export tasks to a Spreadsheet document.
     *
     * @throws NotFoundHttpException                      if no category is found
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \Doctrine\ORM\Exception\ORMException
     * @throws \Psr\Container\ContainerExceptionInterface
     */
    #[Route(path: '/excel', name: 'task_excel')]
    public function excel(): SpreadsheetResponse
    {
        $entities = $this->getEntities('name');
        if (empty($entities)) {
            $message = $this->trans('task.list.empty');
            throw $this->createNotFoundException($message);
        }
        $doc = new TasksDocument($this, $entities);

        return $this->renderSpreadsheetDocument($doc);
    }

    /**
     * Export tasks to a PDF document.
     *
     * @throws NotFoundHttpException                      if no category is found
     * @throws \Doctrine\ORM\Exception\ORMException
     * @throws \Psr\Cache\InvalidArgumentException
     * @throws \Psr\Container\ContainerExceptionInterface
     */
    #[Route(path: '/pdf', name: 'task_pdf')]
    public function pdf(): PdfResponse
    {
        $entities = $this->getEntities('name');
        if (empty($entities)) {
            $message = $this->trans('task.list.empty');
            throw $this->createNotFoundException($message);
        }
        $doc = new TasksReport($this, $entities);

        return $this->renderPdfDocument($doc);
    }

    /**
     * Show properties of a task.
     */
    #[Route(path: '/show/{id}', name: 'task_show', requirements: ['id' => Requirement::DIGITS])]
    public function show(Task $item): Response
    {
        return $this->showEntity($item);
    }

    /**
     * Render the table view.
     *
     * @throws \ReflectionException
     */
    #[Route(path: '', name: 'task_table')]
    public function table(Request $request, TaskTable $table, LoggerInterface $logger): Response
    {
        return $this->handleTableRequest($request, $table, 'task/task_table.html.twig', $logger);
    }

    /**
     * {@inheritdoc}
     *
     * @param Task $item
     *
     * @throws \Psr\Container\ContainerExceptionInterface
     */
    protected function editEntity(Request $request, AbstractEntity $item, array $parameters = []): Response
    {
        $parameters['item_index'] = $item->count();
        $parameters['margin_index'] = $item->countMargins();

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
