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
use App\Utils\FileUtils;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpKernel\Attribute\ValueResolver;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Twig\Extra\Markdown\MarkdownInterface;

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
     * Gets the license content.
     */
    #[IsGranted(RoleInterface::ROLE_ADMIN)]
    #[Get(path: '/license', name: 'license')]
    public function license(
        #[MapQueryParameter]
        string $file,
        MarkdownInterface $markdown,
        #[Autowire('%kernel.project_dir%')]
        string $projectDir,
    ): JsonResponse {
        $file = FileUtils::buildPath($projectDir, $file);
        if (!FileUtils::exists($file)) {
            return $this->jsonFalse(['message' => $this->trans('about.dialog.not_found')]);
        }
        $content = FileUtils::readFile($file);
        if ('' === $content) {
            return $this->jsonFalse(['message' => $this->trans('about.dialog.not_loaded')]);
        }

        return $this->jsonTrue([
            'content' => $markdown->convert($content),
        ]);
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
     *
     * @psalm-api
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
     * Render the page selection dialog for data table.
     *
     * @psalm-api
     */
    #[IsGranted(RoleInterface::ROLE_USER)]
    #[Get(path: '/dialog/page', name: 'dialog_page')]
    public function renderDialogPage(): JsonResponse
    {
        return $this->renderTemplate('dialog/dialog_table_page.html.twig');
    }

    /**
     * Render the sort dialog for data table.
     *
     * @psalm-api
     */
    #[IsGranted(RoleInterface::ROLE_USER)]
    #[Post(path: '/dialog/sort', name: 'dialog_sort')]
    public function renderDialogSort(Request $request): JsonResponse
    {
        return $this->renderTemplate('dialog/dialog_table_sort.html.twig', ['columns' => $request->toArray()]);
    }

    /**
     * Sets a session attribute.
     *
     * @psalm-api
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
     * Save table parameters.
     *
     * @psalm-api
     */
    #[IsGranted(RoleInterface::ROLE_USER)]
    #[Post(path: '/save', name: 'save_table')]
    public function saveTable(
        Request $request,
        UserService $service
    ): JsonResponse {
        $value = $request->request->getString(TableInterface::PARAM_VIEW);
        $view = TableView::tryFrom($value) ?? TableView::TABLE;
        $response = $this->json(true);
        $path = $this->getCookiePath();
        $this->updateCookie($response, TableInterface::PARAM_VIEW, $view, path: $path);
        $service->setProperty(PropertyServiceInterface::P_DISPLAY_MODE, $view);

        return $response;
    }

    private function renderTemplate(string $view, array $parameters = []): JsonResponse
    {
        return $this->json($this->renderView($view, $parameters));
    }
}
