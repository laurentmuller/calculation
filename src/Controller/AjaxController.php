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

use App\Enums\StrengthLevel;
use App\Enums\TableView;
use App\Interfaces\RoleInterface;
use App\Interfaces\TableInterface;
use App\Model\HttpClientError;
use App\Model\TaskComputeQuery;
use App\Service\FakerService;
use App\Service\PasswordService;
use App\Service\TaskService;
use App\Traits\CookieTrait;
use App\Traits\MathTrait;
use App\Translator\TranslatorFactory;
use App\Translator\TranslatorServiceInterface;
use App\Utils\StringUtils;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
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
    #[Route(path: '/task', name: 'ajax_task')]
    public function computeTask(Request $request, TaskService $service): JsonResponse
    {
        if (!($query = $service->createQuery($request)) instanceof TaskComputeQuery) {
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
     * Identifies the language of a piece of text.
     *
     * @throws \Psr\Container\ContainerExceptionInterface if the service is not found
     */
    #[IsGranted(RoleInterface::ROLE_USER)]
    #[Route(path: '/detect', name: 'ajax_detect')]
    public function detect(Request $request, TranslatorFactory $factory): JsonResponse
    {
        $text = $this->getRequestString($request, 'text', '');
        $class = $this->getRequestString($request, 'service', TranslatorFactory::DEFAULT_SERVICE);
        $service = $factory->getService($class);
        if (!StringUtils::isString($text)) {
            return $this->jsonFalse([
                'message' => $this->trans('translator.text_error'),
            ]);
        }

        try {
            if ($result = $service->detect($text)) {
                return $this->jsonTrue([
                    'service' => $service::getName(),
                    'data' => $result,
                ]);
            }

            return $this->handleTranslationError($service, 'translator.detect_error');
        } catch (\Exception $e) {
            return $this->jsonException($e, $this->trans('translator.detect_error'));
        }
    }

    /**
     * Gets the list of translate languages.
     *
     * @throws \Psr\Container\ContainerExceptionInterface if the service is not found
     */
    #[IsGranted(RoleInterface::ROLE_USER)]
    #[Route(path: '/languages', name: 'ajax_languages')]
    public function languages(Request $request, TranslatorFactory $factory): JsonResponse
    {
        $class = $this->getRequestString($request, 'service', TranslatorFactory::DEFAULT_SERVICE);
        $service = $factory->getService($class);
        if ($languages = $service->getLanguages()) {
            return $this->jsonTrue([
                'languages' => $languages,
            ]);
        }
        // error
        return $this->handleTranslationError($service, 'translator.languages_error');
    }

    /**
     * Validate a strength password.
     */
    #[IsGranted(RoleInterface::ROLE_USER)]
    #[Route(path: '/password', name: 'ajax_password')]
    public function password(Request $request, PasswordService $service, #[Autowire('%kernel.debug')] bool $debug): JsonResponse
    {
        $password = $this->getRequestString($request, 'password', '');
        $strength = $this->getRequestInt($request, 'strength', StrengthLevel::NONE->value);
        $email = $this->getRequestString($request, 'email');
        $user = $this->getRequestString($request, 'user');
        $results = $service->validate($password, $strength, $email, $user);
        if ($debug) {
            \ksort($results);
        }

        return $this->json($results);
    }

    /**
     * Gets random text used to display notifications.
     */
    #[IsGranted(RoleInterface::ROLE_USER)]
    #[Route(path: '/random/text', name: 'ajax_random_text')]
    public function randomText(Request $request, FakerService $service): JsonResponse
    {
        $maxNbChars = $this->getRequestInt($request, 'maxNbChars', 145);
        $indexSize = $this->getRequestInt($request, 'indexSize', 2);
        $generator = $service->getGenerator();
        $text = $generator->realText($maxNbChars, $indexSize);

        return $this->jsonTrue([
            'content' => $text,
        ]);
    }

    /**
     * Save the state of the sidebar.
     */
    #[IsGranted(RoleInterface::ROLE_USER)]
    #[Route(path: '/navigation', name: 'ajax_save_navigation')]
    public function saveNavigationState(Request $request): JsonResponse
    {
        if ($request->hasSession()) {
            $session = $request->getSession();
            /** @psalm-var array<string, string> $menus */
            $menus = $request->request->all();
            $menus = \array_filter($menus, static fn (string $key): bool => \str_starts_with($key, 'menu_'), \ARRAY_FILTER_USE_KEY);
            foreach ($menus as $key => &$menu) {
                $menu = \filter_var($menu, \FILTER_VALIDATE_BOOLEAN);
                $session->set($key, $menu);
            }
            $response = $this->json(true);
            $path = $this->getCookiePath();
            $isHidden = $menus['menu_sidebar_hide'] ?? true;
            $this->updateCookie($response, 'SIDEBAR_HIDE', $isHidden ? 1 : 0, '', $path);

            return $response;
        }

        return $this->json(false);
    }

    /**
     * Sets a session attribute.
     *
     * The request must contains 'name' and 'value' parameters.
     */
    #[IsGranted(RoleInterface::ROLE_USER)]
    #[Route(path: '/session/set', name: 'ajax_session_set')]
    public function saveSession(Request $request): JsonResponse
    {
        $result = false;
        $name = $this->getRequestString($request, 'name');
        $value = $this->getRequestString($request, 'value');
        if (null !== $name && null !== $value) {
            $this->setSessionValue($name, \json_decode($value));
            $result = true;
        }

        return $this->json($result);
    }

    /**
     * Save table parameters.
     */
    #[IsGranted(RoleInterface::ROLE_USER)]
    #[Route(path: '/save', name: 'ajax_save_table')]
    public function saveTable(Request $request): JsonResponse
    {
        $view = $this->getRequestEnum($request, TableInterface::PARAM_VIEW, TableView::class, TableView::TABLE);
        $response = $this->json(true);
        $this->updateCookie($response, TableInterface::PARAM_VIEW, $view->value, '', $this->getCookiePath());

        return $response;
    }

    /**
     * Translate a text.
     *
     * @throws \Psr\Container\ContainerExceptionInterface if the service is not found
     */
    #[IsGranted(RoleInterface::ROLE_USER)]
    #[Route(path: '/translate', name: 'ajax_translate')]
    public function translate(Request $request, TranslatorFactory $factory): JsonResponse
    {
        if (($response = $this->checkAjaxCall($request)) instanceof JsonResponse) {
            return $response;
        }
        $to = $this->getRequestString($request, 'to', '');
        $from = $this->getRequestString($request, 'from');
        $text = $this->getRequestString($request, 'text', '');
        $class = $this->getRequestString($request, 'service', TranslatorFactory::DEFAULT_SERVICE);
        $service = $factory->getService($class);
        if (!StringUtils::isString($text)) {
            return $this->jsonFalse([
                'message' => $this->trans('translator.text_error'),
            ]);
        }
        if (!StringUtils::isString($to)) {
            return $this->jsonFalse([
                'message' => $this->trans('translator.to_error'),
            ]);
        }

        try {
            if ($result = $service->translate($text, $to, $from)) {
                return $this->jsonTrue([
                    'service' => $service::getName(),
                    'data' => $result,
                ]);
            }

            return $this->handleTranslationError($service, 'translator.translate_error');
        } catch (\Exception $e) {
            return $this->jsonException($e, $this->trans('translator.translate_error'));
        }
    }

    /**
     * Checks if the given request is a XMLHttpRequest (ajax) call.
     *
     * @return JsonResponse|null null if the request is a XMLHttpRequest call, a JSON error response otherwise
     */
    private function checkAjaxCall(Request $request): ?JsonResponse
    {
        if (!$request->isXmlHttpRequest()) {
            return $this->jsonFalse([
                'message' => $this->trans('errors.invalid_request'),
            ]);
        }

        return null;
    }

    private function handleTranslationError(TranslatorServiceInterface $service, string $message): JsonResponse
    {
        if (($error = $service->getLastError()) instanceof HttpClientError) {
            $id = \sprintf('%s.%s', $service->getName(), $error->getCode());
            if ($this->isTransDefined($id, 'translator')) {
                $error->setMessage($this->trans($id, [], 'translator'));
            }

            return $this->jsonFalse([
                'message' => $this->trans($message),
                'exception' => $error,
            ]);
        }

        return $this->jsonFalse([
            'message' => $this->trans($message),
        ]);
    }
}
