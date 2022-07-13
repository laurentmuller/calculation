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

use App\Entity\Task;
use App\Enums\TableView;
use App\Interfaces\StrengthInterface;
use App\Interfaces\TableInterface;
use App\Repository\AbstractRepository;
use App\Repository\CalculationRepository;
use App\Repository\CustomerRepository;
use App\Repository\ProductRepository;
use App\Repository\TaskRepository;
use App\Repository\UserRepository;
use App\Service\CalculationService;
use App\Service\CaptchaImageService;
use App\Service\FakerService;
use App\Service\SwissPostService;
use App\Service\TaskService;
use App\Traits\CookieTrait;
use App\Traits\MathTrait;
use App\Traits\StrengthTranslatorTrait;
use App\Translator\TranslatorFactory;
use App\Translator\TranslatorServiceInterface;
use App\Util\Utils;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use ReCaptcha\ReCaptcha;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;
use ZxcvbnPhp\Zxcvbn;

/**
 * Controller for all XMLHttpRequest (Ajax) calls.
 */
#[AsController]
#[Route(path: '/ajax')]
class AjaxController extends AbstractController
{
    use CookieTrait;
    use MathTrait;
    use StrengthTranslatorTrait;

    /**
     * Returns a new captcha image.
     *
     * @throws \Psr\Container\ContainerExceptionInterface
     */
    #[IsGranted('PUBLIC_ACCESS')]
    #[Route(path: '/captcha/image', name: 'ajax_captcha_image')]
    public function captchaImage(CaptchaImageService $service): JsonResponse
    {
        if ($data = $service->generateImage(true)) {
            return $this->jsonTrue([
                'data' => $data,
            ]);
        }

        return $this->jsonFalse([
            'message' => $this->trans('captcha.generate', [], 'validators'),
        ]);
    }

    /**
     * Validate a captcha image.
     *
     * @throws \Psr\Container\ContainerExceptionInterface
     */
    #[IsGranted('PUBLIC_ACCESS')]
    #[Route(path: '/captcha/validate', name: 'ajax_captcha_validate')]
    public function captchaValidate(Request $request, CaptchaImageService $service): JsonResponse
    {
        if (!$service->validateTimeout()) {
            $response = $this->trans('captcha.timeout', [], 'validators');
        } elseif (!$service->validateToken($this->getRequestString($request, 'captcha'))) {
            $response = $this->trans('captcha.invalid', [], 'validators');
        } else {
            $response = true;
        }

        return $this->json($response);
    }

    /**
     * Check if the given reCaptcha response (if any) is valid.
     */
    #[IsGranted('ROLE_USER')]
    #[Route(path: '/checkrecaptcha', name: 'ajax_check_recaptcha')]
    public function checkRecaptcha(Request $request): JsonResponse
    {
        // get values
        $remoteIp = $request->getClientIp();
        $response = (string) $this->getRequestString($request, 'g-recaptcha-response', $this->getRequestString($request, 'response'));
        $secret = $this->getStringParameter('recaptcha_secret');
        // verify
        $recaptcha = new ReCaptcha($secret);
        $result = $recaptcha->verify($response, $remoteIp);
        // ok?
        if ($result->isSuccess()) {
            return $this->json(true);
        }
        $errorCodes = \array_map(fn (mixed $code): string => $this->trans("recaptcha.$code", [], 'validators'), $result->getErrorCodes());
        if (empty($errorCodes)) {
            $errorCodes[] = $this->trans('recaptcha.unknown-error', [], 'validators');
        }
        $message = \implode(' ', $errorCodes);

        return $this->json($message);
    }

    /**
     * Check if a username or e-mail exist.
     */
    #[IsGranted('PUBLIC_ACCESS')]
    #[Route(path: '/checkuser', name: 'ajax_check_user')]
    public function checkUser(Request $request, UserRepository $repository): JsonResponse
    {
        // find username
        $usernameOrEmail = $this->getRequestString($request, 'user');
        if (null !== $usernameOrEmail && null !== $repository->findByUsernameOrEmail($usernameOrEmail)) {
            return $this->json(true);
        }

        return $this->json($this->trans('username.not_found', [], 'validators'));
    }

    /**
     * Check if a user e-mail already exists.
     */
    #[IsGranted('ROLE_USER')]
    #[Route(path: '/checkuseremail', name: 'ajax_check_user_email')]
    public function checkUserEmail(Request $request, UserRepository $repository): JsonResponse
    {
        // get values
        $id = $this->getRequestInt($request, 'id');
        $email = $this->getRequestString($request, 'email');
        // check
        $message = null;
        if (empty($email)) {
            $message = 'email.blank';
        } elseif (\strlen($email) < 2) {
            $message = 'email.short';
        } elseif (\strlen($email) > 180) {
            $message = 'email.long';
        } else {
            $user = $repository->findByEmail($email);
            if (null !== $user && $id !== $user->getId()) {
                $message = 'email.already_used';
            }
        }
        if (null !== $message) {
            return $this->json($this->trans($message, [], 'validators'));
        }

        return $this->json(true);
    }

    /**
     * Check if a username already exists.
     */
    #[IsGranted('ROLE_USER')]
    #[Route(path: '/checkusername', name: 'ajax_check_user_name')]
    public function checkUsername(Request $request, UserRepository $repository): JsonResponse
    {
        // get values
        $id = $this->getRequestInt($request, 'id');
        $username = $this->getRequestString($request, 'username');
        // check
        $message = null;
        if (empty($username)) {
            $message = 'username.blank';
        } elseif (\strlen($username) < 2) {
            $message = 'username.short';
        } elseif (\strlen($username) > 180) {
            $message = 'username.long';
        } else {
            $user = $repository->findByUsername($username);
            if (null !== $user && $id !== $user->getId()) {
                $message = 'username.already_used';
            }
        }
        if (null !== $message) {
            return $this->json($this->trans($message, [], 'validators'));
        }

        return $this->json(true);
    }

    /**
     * Compute a task.
     */
    #[IsGranted('ROLE_USER')]
    #[Route(path: '/task', name: 'ajax_task')]
    public function computeTask(Request $request, TaskService $service, TaskRepository $repository): JsonResponse
    {
        // get values
        $id = $this->getRequestInt($request, 'id');
        $quantity = $this->getRequestFloat($request, 'quantity');
        $task = $repository->find($id);
        if (!$task instanceof Task) {
            return $this->jsonFalse([
                'message' => $this->trans('taskcompute.error.task'),
            ]);
        }
        // update service and compute
        $service->setTask($task)
            ->setQuantity($quantity)
            ->compute($request);
        /** @psalm-var array $data */
        $data = \array_merge($service->jsonSerialize(), [
            'message' => $this->trans('taskcompute.success'),
        ]);

        return $this->jsonTrue($data);
    }

    /**
     * Gets the list of translate languages.
     *
     * @throws \Psr\Container\ContainerExceptionInterface
     */
    #[IsGranted('ROLE_USER')]
    #[Route(path: '/languages', name: 'ajax_languages')]
    public function languages(Request $request, TranslatorFactory $factory): JsonResponse
    {
        $class = $this->getRequestString($request, 'service', TranslatorFactory::DEFAULT_SERVICE);
        $service = $factory->getService((string) $class);
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
    #[IsGranted('ROLE_USER')]
    #[Route(path: '/password', name: 'ajax_password')]
    public function password(Request $request): JsonResponse
    {
        $inputBag = Utils::getRequestInputBag($request);
        if (!$password = $inputBag->get('password')) {
            return $this->jsonFalse([
                'message' => 'No password is defined.',
            ]);
        }
        $minimum = (int) $inputBag->get('strength', -1);
        if (!\in_array($minimum, StrengthInterface::ALLOWED_LEVELS, true)) {
            $values = \implode(', ', StrengthInterface::ALLOWED_LEVELS);
            $message = \sprintf('The minimum strength parameter %d is invalid. Allowed values: [%s].', $minimum, $values);

            return $this->jsonFalse(
                [
                    'minimum' => $minimum,
                    'message' => $message,
                ]
            );
        }
        if (StrengthInterface::LEVEL_NONE === $minimum) {
            return $this->jsonFalse([
                'minimum' => $minimum,
                'minimum_text' => $this->translateLevel($minimum),
                'message' => 'The strength level is disabled.',
            ]);
        }

        $inputs = [];
        if ($userField = $inputBag->get('user')) {
            $inputs[] = $userField;
        }
        if ($emailField = $inputBag->get('email')) {
            $inputs[] = $emailField;
        }

        $service = new Zxcvbn();
        $result = $service->passwordStrength((string) $password, $inputs);
        $score = (int) $result['score'];
        if ($score < $minimum) {
            return $this->jsonFalse([
                'score' => $score,
                'score_text' => $this->translateLevel($score),
                'minimum' => $minimum,
                'minimum_text' => $this->translateLevel($minimum),
                'message' => $this->translateStrength($minimum, $score),
            ]);
        }

        return $this->jsonTrue([
            'score' => $score,
            'score_text' => $this->translateLevel($score),
            'minimum' => $minimum,
            'minimum_text' => $this->translateLevel($minimum),
        ]);
    }

    /**
     * Gets random text used to display notifications.
     */
    #[IsGranted('ROLE_USER')]
    #[Route(path: '/random/text', name: 'ajax_random_text')]
    public function randomText(Request $request, FakerService $service): JsonResponse
    {
        // get parameters
        $maxNbChars = $this->getRequestInt($request, 'maxNbChars', 145);
        $indexSize = $this->getRequestInt($request, 'indexSize', 2);
        $generator = $service->getGenerator();
        $text = $generator->realText($maxNbChars, $indexSize);

        return $this->jsonTrue([
            'content' => $text,
        ]);
    }

    /**
     * Save horizontal navigation state.
     */
    #[IsGranted('ROLE_USER')]
    #[Route(path: '/navigation', name: 'ajax_save_navigation')]
    public function saveNavigationState(Request $request): JsonResponse
    {
        if ($request->hasSession()) {
            $session = $request->getSession();
            /** @psalm-var array<string, string> $menus */
            $menus = $request->request->all();
            foreach ($menus as $key => $menu) {
                $session->set($key, \filter_var($menu, \FILTER_VALIDATE_BOOLEAN));
            }

            return $this->json(true);
        }

        return $this->json(false);
    }

    /**
     * Sets a session attribute.
     *
     * The request must contains 'name' and 'value' parameters.
     *
     * @throws \Psr\Container\ContainerExceptionInterface
     */
    #[IsGranted('ROLE_USER')]
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
    #[IsGranted('ROLE_USER')]
    #[Route(path: '/save', name: 'ajax_save_table')]
    public function saveTable(Request $request): JsonResponse
    {
        $bag = Utils::getRequestInputBag($request);
        $requestView = (string) $bag->get(TableInterface::PARAM_VIEW, TableView::TABLE->value);
        $view = TableView::tryFrom($requestView) ?? TableView::TABLE;
        $requestLimit = $bag->getInt(TableInterface::PARAM_LIMIT, $view->getPageSize());

        $key = $view->value;
        $response = $this->json(true);
        $this->setCookie($response, TableInterface::PARAM_VIEW, $key);
        $this->setCookie($response, TableInterface::PARAM_LIMIT, $requestLimit, $key);

        return $response;
    }

    /**
     * Search streets, zip codes or cities.
     */
    #[IsGranted('ROLE_USER')]
    #[Route(path: '/search/address', name: 'ajax_search_address')]
    public function searchAddress(Request $request, SwissPostService $service): JsonResponse
    {
        $zip = $this->getRequestString($request, 'zip');
        $city = $this->getRequestString($request, 'city');
        $street = $this->getRequestString($request, 'street');
        $limit = $this->getRequestInt($request, 'limit', 25);
        if ($zip) {
            $rows = $service->findZip($zip, $limit);
        } elseif ($city) {
            $rows = $service->findCity($city, $limit);
        } elseif ($street) {
            $rows = $service->findStreet($street, $limit);
        } else {
            $rows = [];
        }

        return $this->json($rows);
    }

    /**
     * Search distinct calculation's customers in existing calculations.
     *
     * @throws \ReflectionException
     */
    #[IsGranted('ROLE_USER')]
    #[Route(path: '/search/customer', name: 'ajax_search_customer')]
    public function searchCustomer(Request $request, CalculationRepository $repository): JsonResponse
    {
        return $this->getDistinctValues($request, $repository, 'customer');
    }

    /**
     * Gets sorted, distinct and not null values.
     *
     * The request must have the following fields:
     * <ul>
     * <li><code>entity</code>: the entity class name without the namespace.</li>
     * <li><code>field</code>: the field name (column) to get values for.</li>
     * <li><code>query</code>: the value to search.</li>
     * <li><code>limit</code>: the number of results to retrieve (default = 15).</li>
     * </ul>
     *
     * @throws \ReflectionException
     */
    #[IsGranted('ROLE_USER')]
    #[Route(path: '/search/distinct', name: 'ajax_search_distinct')]
    public function searchDistinct(Request $request, EntityManagerInterface $manager): JsonResponse
    {
        $className = 'App\\Entity\\' . \ucfirst((string) $this->getRequestString($request, 'entity', ''));
        if (!\class_exists($className)) {
            return $this->jsonFalse([
                'values' => [],
            ]);
        }
        // field
        $field = $this->getRequestString($request, 'field');
        if (!Utils::isString($field)) {
            return $this->jsonFalse([
                'values' => [],
            ]);
        }

        try {
            /** @psalm-var AbstractRepository<\App\Entity\AbstractEntity> $repository */
            $repository = $manager->getRepository($className);

            return $this->getDistinctValues($request, $repository, (string) $field);
        } catch (\Exception $e) {
            return $this->jsonException($e);
        }
    }

    /**
     * Search products.
     *
     * @throws \ReflectionException
     */
    #[IsGranted('ROLE_USER')]
    #[Route(path: '/search/product', name: 'ajax_search_product')]
    public function searchProduct(Request $request, ProductRepository $repository): JsonResponse
    {
        try {
            $search = (string) $this->getRequestString($request, 'query', '');
            if (Utils::isString($search)) {
                $maxResults = $this->getRequestInt($request, 'limit', 15);
                $products = $repository->search($search, $maxResults);
                if (!empty($products)) {
                    return $this->json($products);
                }
            }

            // empty
            return $this->jsonFalse([
                'products' => [],
            ]);
        } catch (\Exception $e) {
            return $this->jsonException($e);
        }
    }

    /**
     * Search distinct product and task suppliers.
     *
     * @throws \ReflectionException
     */
    #[IsGranted('ROLE_USER')]
    #[Route(path: '/search/supplier', name: 'ajax_search_supplier')]
    public function searchSupplier(Request $request, ProductRepository $productRepository, TaskRepository $taskRepository): JsonResponse
    {
        return $this->getDistinctValuesForCategoryItem($request, $productRepository, $taskRepository, 'supplier');
    }

    /**
     * Search distinct customer's titles.
     *
     * @throws \ReflectionException
     */
    #[IsGranted('ROLE_USER')]
    #[Route(path: '/search/title', name: 'ajax_search_title')]
    public function searchTitle(Request $request, CustomerRepository $repository): JsonResponse
    {
        return $this->getDistinctValues($request, $repository, 'title');
    }

    /**
     * Search distinct units from products and tasks.
     *
     * @throws \ReflectionException
     */
    #[IsGranted('ROLE_USER')]
    #[Route(path: '/search/unit', name: 'ajax_search_unit')]
    public function searchUnit(Request $request, ProductRepository $productRepository, TaskRepository $taskRepository): JsonResponse
    {
        return $this->getDistinctValuesForCategoryItem($request, $productRepository, $taskRepository, 'unit');
    }

    /**
     * Translate a text.
     *
     * @throws \ReflectionException
     * @throws \Psr\Container\ContainerExceptionInterface
     */
    #[IsGranted('ROLE_USER')]
    #[Route(path: '/translate', name: 'ajax_translate')]
    public function translate(Request $request, TranslatorFactory $factory): JsonResponse
    {
        // ajax call ?
        if (null !== ($response = $this->checkAjaxCall($request))) {
            return $response;
        }
        // get parameters
        $to = (string) $this->getRequestString($request, 'to', '');
        $from = $this->getRequestString($request, 'from');
        $text = (string) $this->getRequestString($request, 'text', '');
        $class = (string) $this->getRequestString($request, 'service', TranslatorFactory::DEFAULT_SERVICE);
        $service = $factory->getService($class);
        // check parameters
        if (!Utils::isString($text)) {
            return $this->jsonFalse([
                'message' => $this->trans('translator.text_error'),
            ]);
        }
        if (!Utils::isString($to)) {
            return $this->jsonFalse([
                'message' => $this->trans('translator.to_error'),
            ]);
        }

        try {
            // translate
            if ($result = $service->translate($text, $to, $from)) {
                return $this->jsonTrue([
                    'data' => $result,
                ]);
            }

            // error
            return $this->handleTranslationError($service, 'translator.translate_error');
        } catch (\Exception $e) {
            return $this->jsonException($e, $this->trans('translator.translate_error'));
        }
    }

    /**
     * Update the calculation's totals.
     *
     * @throws \ReflectionException
     * @throws \Psr\Cache\InvalidArgumentException
     */
    #[IsGranted('ROLE_USER')]
    #[Route(path: '/update', name: 'ajax_update')]
    public function updateCalculation(Request $request, CalculationService $service, LoggerInterface $logger): JsonResponse
    {
        // ajax call ?
        if (null !== ($response = $this->checkAjaxCall($request))) {
            return $response;
        }

        try {
            // source
            $source = Utils::getRequestInputBag($request)->all('calculation');

            // compute
            $parameters = $service->createGroupsFromData($source);

            // OK?
            if (false === $parameters['result']) {
                return $this->json($parameters);
            }

            // adjust user margin?
            $parameters['min_margin'] = $service->getMinMargin();
            if ($this->getRequestBoolean($request, 'adjust') && $parameters['overall_below']) {
                $service->adjustUserMargin($parameters);
            }

            // render table
            $body = $this->renderView('calculation/calculation_ajax_totals.html.twig', $parameters);

            // ok
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
            // log
            $message = $this->trans('calculation.edit.error.update_total');
            $context = Utils::getExceptionContext($e);
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
        // ajax call ?
        if (!$request->isXmlHttpRequest()) {
            return $this->jsonFalse([
                'message' => $this->trans('errors.invalid_request'),
            ]);
        }

        return null;
    }

    /**
     * Search distinct values.
     *
     * @param Request            $request    the request to get search parameters
     * @param AbstractRepository $repository the repository to search in
     * @param string             $field      the field name to search for
     *
     * @template T of \App\Entity\AbstractEntity
     * @psalm-param AbstractRepository<T> $repository
     *
     * @throws \ReflectionException
     */
    private function getDistinctValues(Request $request, AbstractRepository $repository, string $field): JsonResponse
    {
        try {
            $search = $this->getRequestString($request, 'query', '');
            if (Utils::isString($search)) {
                $limit = $this->getRequestInt($request, 'limit', 15);
                $values = $repository->getDistinctValues($field, $search, $limit);
                if (!empty($values)) {
                    return $this->json($values);
                }
            }

            // empty
            return $this->jsonFalse([
                'values' => [],
            ]);
        } catch (\Exception $e) {
            return $this->jsonException($e);
        }
    }

    /**
     * Search distinct values from products and tasks.
     *
     * @param Request           $request           the request to get search parameters
     * @param ProductRepository $productRepository the product repository to search in
     * @param TaskRepository    $taskRepository    the task repository to search in
     * @param string            $field             the field name to search for
     *
     * @throws \ReflectionException
     */
    private function getDistinctValuesForCategoryItem(Request $request, ProductRepository $productRepository, TaskRepository $taskRepository, string $field): JsonResponse
    {
        try {
            $search = $this->getRequestString($request, 'query', '');
            if (Utils::isString($search)) {
                $limit = $this->getRequestInt($request, 'limit', 15);
                $productValues = $productRepository->getDistinctValues($field, $search);
                $taskValues = $taskRepository->getDistinctValues($field, $search);
                $values = \array_unique(\array_merge($productValues, $taskValues));
                \sort($values, \SORT_LOCALE_STRING);
                $values = \array_slice($values, 0, $limit);
                if (!empty($values)) {
                    return $this->json($values);
                }
            }

            // empty
            return $this->jsonFalse([
                'values' => [],
            ]);
        } catch (\Exception $e) {
            return $this->jsonException($e);
        }
    }

    private function handleTranslationError(TranslatorServiceInterface $service, string $message): JsonResponse
    {
        if ($error = $service->getLastError()) {
            // translate message
            $id = $service->getDefaultIndexName() . '.' . $error['code'];
            if ($this->isTransDefined($id, 'translator')) {
                $error['message'] = $this->trans($id, [], 'translator');
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
