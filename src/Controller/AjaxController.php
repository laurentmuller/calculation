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
use App\Enums\TableView;
use App\Interfaces\PropertyServiceInterface;
use App\Interfaces\RoleInterface;
use App\Interfaces\TableInterface;
use App\Model\PasswordQuery;
use App\Model\SessionQuery;
use App\Model\TaskComputeQuery;
use App\Model\TaskComputeResult;
use App\Resolver\TaskComputeQueryValueResolver;
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
use Symfony\Component\HttpKernel\Attribute\ValueResolver;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Controller for all XMLHttpRequest (Ajax) calls.
 */
#[AsController]
#[Route(path: '/ajax', name: 'ajax_')]
class AjaxController extends AbstractController
{
    use CookieTrait;
    use MathTrait;

    /**
     * Compute a task.
     */
    #[IsGranted(RoleInterface::ROLE_USER)]
    #[Post(path: '/task', name: 'task')]
    public function computeTask(
        #[ValueResolver(TaskComputeQueryValueResolver::class)]
        TaskComputeQuery $query,
        TaskService $service
    ): JsonResponse {
        $result = $service->computeQuery($query);
        if (!$result instanceof TaskComputeResult) {
            return $this->jsonFalse(
                ['message' => $this->trans('task_compute.error.task')]
            );
        }

        $data = \array_merge($result->jsonSerialize(), [
            'message' => $this->trans('task_compute.success'),
        ]);

        return $this->jsonTrue($data);
    }

    /**
     * Validate a strength password.
     */
    #[IsGranted(RoleInterface::ROLE_USER)]
    #[Post(path: '/password', name: 'password')]
    public function password(#[MapRequestPayload] PasswordQuery $query, PasswordService $service): JsonResponse
    {
        $results = $service->validate($query);

        return $this->json($results);
    }

    /**
     * Gets random text used to display notifications.
     */
    #[IsGranted(RoleInterface::ROLE_USER)]
    #[Get(path: '/random/text', name: 'random_text')]
    public function randomText(FakerService $service, #[MapQueryParameter] int $maxNbChars = 150): JsonResponse
    {
        $generator = $service->getGenerator();
        $content = $generator->realText(\max($maxNbChars, 50));

        return $this->jsonTrue([
            'content' => $content,
        ]);
    }

    /**
     * Sets a session attribute.
     */
    #[IsGranted(RoleInterface::ROLE_USER)]
    #[Post(path: '/session/set', name: 'session_set')]
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
     * Save table view parameter.
     */
    #[IsGranted(RoleInterface::ROLE_USER)]
    #[Post(path: '/save', name: 'save_table')]
    public function saveTable(
        Request $request,
        UserService $service
    ): JsonResponse {
        $response = $this->json(true);
        $view = $this->getRequestEnum($request, TableInterface::PARAM_VIEW, TableView::TABLE);
        $this->updateCookie($response, TableInterface::PARAM_VIEW, $view);
        $service->setProperty(PropertyServiceInterface::P_DISPLAY_MODE, $view);

        return $response;
    }
}
