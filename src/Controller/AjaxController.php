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
use App\Enums\StrengthLevel;
use App\Enums\TableView;
use App\Interfaces\RoleInterface;
use App\Interfaces\TableInterface;
use App\Model\HttpClientError;
use App\Model\TaskComputeQuery;
use App\Repository\AbstractRepository;
use App\Repository\CalculationRepository;
use App\Repository\CustomerRepository;
use App\Repository\ProductRepository;
use App\Service\CalculationService;
use App\Service\FakerService;
use App\Service\PasswordService;
use App\Service\SwissPostService;
use App\Service\TaskService;
use App\Traits\CookieTrait;
use App\Traits\MathTrait;
use App\Translator\TranslatorFactory;
use App\Translator\TranslatorServiceInterface;
use App\Utils\StringUtils;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\ResultSetMapping;
use Psr\Log\LoggerInterface;
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
     * Search address.
     */
    #[IsGranted(RoleInterface::ROLE_USER)]
    #[Route(path: '/search/address', name: 'ajax_search_address')]
    public function searchAddress(Request $request, SwissPostService $service): JsonResponse
    {
        $limit = $this->getRequestInt($request, 'limit', 25);
        if (null !== $zip = $this->getRequestString($request, 'zip')) {
            return $this->json($service->findZip($zip, $limit));
        }
        if (null !== $city = $this->getRequestString($request, 'city')) {
            return $this->json($service->findCity($city, $limit));
        }
        if (null !== $street = $this->getRequestString($request, 'street')) {
            return $this->json($service->findStreet($street, $limit));
        }

        return $this->json([]);
    }

    /**
     * Search distinct calculation's customers in existing calculations.
     */
    #[IsGranted(RoleInterface::ROLE_USER)]
    #[Route(path: '/search/customer', name: 'ajax_search_customer')]
    public function searchCustomer(Request $request, CalculationRepository $repository): JsonResponse
    {
        return $this->getDistinctValuesFromRepository($request, $repository, 'customer');
    }

    /**
     * Search products.
     */
    #[IsGranted(RoleInterface::ROLE_USER)]
    #[Route(path: '/search/product', name: 'ajax_search_product')]
    public function searchProduct(Request $request, ProductRepository $repository): JsonResponse
    {
        try {
            $search = $this->getRequestString($request, 'query', '');
            if (StringUtils::isString($search)) {
                $maxResults = $this->getRequestInt($request, 'limit', 15);
                $products = $repository->search($search, $maxResults);
                if ([] !== $products) {
                    return $this->json($products);
                }
            }

            return $this->jsonFalse([
                'products' => [],
            ]);
        } catch (\Exception $e) {
            return $this->jsonException($e);
        }
    }

    /**
     * Search distinct product and task suppliers.
     */
    #[IsGranted(RoleInterface::ROLE_USER)]
    #[Route(path: '/search/supplier', name: 'ajax_search_supplier')]
    public function searchSupplier(Request $request, EntityManagerInterface $manager): JsonResponse
    {
        return $this->getDistinctValuesFromManager($request, $manager, 'supplier');
    }

    /**
     * Search distinct customer's titles.
     */
    #[IsGranted(RoleInterface::ROLE_USER)]
    #[Route(path: '/search/title', name: 'ajax_search_title')]
    public function searchTitle(Request $request, CustomerRepository $repository): JsonResponse
    {
        return $this->getDistinctValuesFromRepository($request, $repository, 'title');
    }

    /**
     * Search distinct units from products and tasks.
     */
    #[IsGranted(RoleInterface::ROLE_USER)]
    #[Route(path: '/search/unit', name: 'ajax_search_unit')]
    public function searchUnit(Request $request, EntityManagerInterface $manager): JsonResponse
    {
        return $this->getDistinctValuesFromManager($request, $manager, 'unit');
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
     * Update the calculation's totals.
     */
    #[IsGranted(RoleInterface::ROLE_USER)]
    #[Route(path: '/update', name: 'ajax_update')]
    public function updateCalculation(Request $request, CalculationService $service, LoggerInterface $logger): JsonResponse
    {
        if (($response = $this->checkAjaxCall($request)) instanceof JsonResponse) {
            return $response;
        }

        try {
            $source = $this->getRequestAll($request, 'calculation');
            $parameters = $service->createGroupsFromData($source);
            if (!$parameters['result']) {
                return $this->json($parameters);
            }
            $parameters['min_margin'] = $service->getMinMargin();
            if ($this->getRequestBoolean($request, 'adjust') && $parameters['overall_below']) {
                $service->adjustUserMargin($parameters);
            }
            $body = $this->renderView('calculation/calculation_ajax_totals.html.twig', $parameters);
            $result = [
                'result' => true,
                'body' => $body,
                'user_margin' => $parameters['user_margin'] ?? 0,
                'overall_margin' => $parameters['overall_margin'],
                'overall_total' => $parameters['overall_total'],
                'overall_below' => $parameters['overall_below'],
            ];

            return $this->json($result);
        } catch (\Exception $e) {
            $message = $this->trans('calculation.edit.error.update_total');
            $context = $this->getExceptionContext($e);
            $logger->error($message, $context);

            return $this->jsonException($e, $message);
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

    private function getDistinctSql(string $field, string $query, int $limit): string
    {
        return <<<SQL
                SELECT DISTINCT
                    p.$field
                FROM
                    sy_Product as p
                WHERE
                    p.$field LIKE '%$query%'
                UNION
                SELECT DISTINCT
                    t.$field
                FROM
                    sy_Task as t
                WHERE
                    t.$field LIKE '%$query%'
                ORDER BY
                    $field
                LIMIT $limit
            SQL;
    }

    /**
     * Search distinct values from products and tasks.
     */
    private function getDistinctValuesFromManager(Request $request, EntityManagerInterface $manager, string $field): JsonResponse
    {
        return $this->getDistinctValuesFromRequest($request, function (string $query, int $limit) use ($manager, $field): array {
            $sql = $this->getDistinctSql($field, $query, $limit);

            return $manager->createNativeQuery($sql, new ResultSetMapping())
                ->getSingleColumnResult();
        });
    }

    /**
     * Search distinct values.
     *
     * @template T of AbstractEntity
     *
     * @param Request               $request    the request to get query value
     * @param AbstractRepository<T> $repository the entity repository to search from
     * @param string                $field      the field name (column) to get values for
     */
    private function getDistinctValuesFromRepository(Request $request, AbstractRepository $repository, string $field): JsonResponse
    {
        return $this->getDistinctValuesFromRequest($request, fn (string $query, int $limit): array => $repository->getDistinctValues($field, $query, $limit));
    }

    /**
     * @psalm-param callable(string, int): array $callback
     */
    private function getDistinctValuesFromRequest(Request $request, callable $callback): JsonResponse
    {
        $query = \trim($this->getRequestString($request, 'query', ''));
        if ('' === $query) {
            return $this->jsonFalse(['values' => []]);
        }

        try {
            $limit = $this->getRequestInt($request, 'limit', 15);
            $values = $callback($query, $limit);
            if ([] !== $values) {
                return $this->json($values);
            }

            return $this->jsonFalse(['values' => []]);
        } catch (\Exception $e) {
            return $this->jsonException($e);
        }
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
