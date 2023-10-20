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

use App\Enums\TableView;
use App\Interfaces\PropertyServiceInterface;
use App\Interfaces\RoleInterface;
use App\Interfaces\TableInterface;
use App\Model\PasswordQuery;
use App\Model\SessionQuery;
use App\Model\TaskComputeQuery;
use App\Service\FakerService;
use App\Service\PasswordService;
use App\Service\TaskService;
use App\Service\UserService;
use App\Traits\CookieTrait;
use App\Traits\MathTrait;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Controller for all XMLHttpRequest (Ajax) calls.
 */
#[AsController]
#[Route(path: '/ajax')]
class AjaxController extends AbstractController
{
    use CookieTrait;
    use MathTrait;

    /**
     * Compute a task.
     */
    #[IsGranted(RoleInterface::ROLE_USER)]
    #[Route(path: '/task', name: 'ajax_task', methods: Request::METHOD_POST)]
    public function computeTask(Request $request, TaskService $service): JsonResponse
    {
        $query = $service->createQuery($request);
        if (!$query instanceof TaskComputeQuery) {
            return $this->jsonFalse([
                'message' => $this->trans('task_compute.error.task'),
            ]);
        }
        $result = $service->computeQuery($query);
        $data = \array_merge($result->jsonSerialize(), [
            'message' => $this->trans('task_compute.success'),
        ]);

        return $this->jsonTrue($data);
    }

    /**
     * Validate a strength password.
     */
    #[IsGranted(RoleInterface::ROLE_USER)]
    #[Route(path: '/password', name: 'ajax_password', methods: Request::METHOD_POST)]
    public function password(#[MapRequestPayload] PasswordQuery $query, PasswordService $service): JsonResponse
    {
        $results = $service->validate($query);

        return $this->json($results);
    }

    /**
     * Gets random text used to display notifications.
     */
    #[IsGranted(RoleInterface::ROLE_USER)]
    #[Route(path: '/random/text', name: 'ajax_random_text', methods: Request::METHOD_GET)]
    public function randomText(FakerService $service, #[MapQueryParameter] int $maxNbChars = 150): JsonResponse
    {
        $generator = $service->getGenerator();
        $content = $generator->realText(\max($maxNbChars, 50));

        return $this->jsonTrue([
            'content' => $content,
        ]);
    }

    /**
     * Render the page selection dialog for data table.
     */
    #[IsGranted(RoleInterface::ROLE_USER)]
    #[Route(path: '/dialog/page', name: 'ajax_dialog_page', methods: Request::METHOD_GET)]
    public function renderDialogPage(): JsonResponse
    {
        return $this->renderDialog('dialog/dialog_table_page.html.twig');
    }

    /**
     * Render the sort dialog for data table.
     */
    #[IsGranted(RoleInterface::ROLE_USER)]
    #[Route(path: '/dialog/sort', name: 'ajax_dialog_sort', methods: Request::METHOD_POST)]
    public function renderDialogSort(Request $request): JsonResponse
    {
        $parameters = [
            'columns' => $request->toArray(),
        ];

        return $this->renderDialog('dialog/dialog_table_sort.html.twig', $parameters);
    }

    /**
     * Save the state of the sidebar to cookies.
     */
    #[IsGranted(RoleInterface::ROLE_USER)]
    #[Route(path: '/navigation', name: 'ajax_save_navigation', methods: Request::METHOD_POST)]
    public function saveNavigationState(Request $request): JsonResponse
    {
        $input = $request->request;
        $response = $this->json(true);
        $path = $this->getCookiePath();

        /** @psalm-var string $key */
        foreach ($input->keys() as $key) {
            $this->updateCookie($response, $key, $input->getBoolean($key), path: $path);
        }

        return $response;
    }

    /**
     * Sets a session attribute.
     */
    #[IsGranted(RoleInterface::ROLE_USER)]
    #[Route(path: '/session/set', name: 'ajax_session_set', methods: Request::METHOD_POST)]
    public function saveSession(#[MapRequestPayload] SessionQuery $query): JsonResponse
    {
        try {
            $this->setSessionValue($query->name, $query->decode());

            return $this->json(true);
        } catch (\JsonException $e) {
            return $this->jsonException($e);
        }
    }

    /**
     * Save table parameters.
     */
    #[IsGranted(RoleInterface::ROLE_USER)]
    #[Route(path: '/save', name: 'ajax_save_table', methods: Request::METHOD_POST)]
    public function saveTable(Request $request, UserService $service): JsonResponse
    {
        $default = $service->getDisplayMode();
        $view = $request->request->getEnum(TableInterface::PARAM_VIEW, TableView::class, $default);
        $response = $this->json(true);
        $path = $this->getCookiePath();
        $this->updateCookie($response, TableInterface::PARAM_VIEW, $view, path: $path);
        $service->setProperty(PropertyServiceInterface::P_DISPLAY_MODE, $view);

        return $response;
    }

    private function renderDialog(string $view, array $parameters = []): JsonResponse
    {
        return $this->json($this->renderView($view, $parameters));
    }
}
