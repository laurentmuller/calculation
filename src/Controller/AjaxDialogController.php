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
use App\Attribute\Post;
use App\Form\Dialog\EditItemDialogType;
use App\Form\Dialog\EditTaskDialogType;
use App\Interfaces\RoleInterface;
use App\Repository\TaskRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Controller to render dialog views within an XMLHttpRequest (Ajax) call.
 */
#[AsController]
#[Route(path: '/ajax', name: 'ajax_')]
class AjaxDialogController extends AbstractController
{
    /**
     * Render the edit item dialog template.
     *
     * @psalm-api
     */
    #[Get(path: '/dialog/item', name: 'dialog_item')]
    public function dialogItem(): JsonResponse
    {
        $parameters = [
            'form' => $this->createForm(EditItemDialogType::class),
        ];

        return $this->renderDialog('dialog/dialog_edit_item.html.twig', $parameters);
    }

    /**
     * Render the page selection dialog for the data table.
     *
     * @psalm-api
     */
    #[IsGranted(RoleInterface::ROLE_USER)]
    #[Get(path: '/dialog/page', name: 'dialog_page')]
    public function dialogPage(): JsonResponse
    {
        return $this->renderDialog('dialog/dialog_table_page.html.twig');
    }

    /**
     * Render the sort dialog for data table.
     *
     * @psalm-api
     */
    #[IsGranted(RoleInterface::ROLE_USER)]
    #[Post(path: '/dialog/sort', name: 'dialog_sort')]
    public function dialogSort(Request $request): JsonResponse
    {
        return $this->renderDialog('dialog/dialog_table_sort.html.twig', ['columns' => $request->toArray()]);
    }

    /**
     * Render the edit task dialog template.
     *
     * @psalm-api
     */
    #[Get(path: '/dialog/task', name: 'dialog_task')]
    public function dialogTask(TaskRepository $repository): JsonResponse
    {
        $parameters = [
            'form' => $this->createForm(EditTaskDialogType::class),
            'tasks' => $repository->getSortedTask(false),
        ];

        return $this->renderDialog('dialog/dialog_edit_task.html.twig', $parameters);
    }

    private function renderDialog(string $view, array $parameters = []): JsonResponse
    {
        return $this->json($this->renderView($view, $parameters));
    }
}
