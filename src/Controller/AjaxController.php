<?php
/*
 * This file is part of the Calculation package.
 *
 * (c) bibi.nu. <bibi@bibi.nu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Task;
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
use App\Traits\MathTrait;
use App\Translator\TranslatorFactory;
use App\Util\FileUtils;
use App\Util\FormatUtils;
use App\Util\Utils;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use ReCaptcha\ReCaptcha;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Yaml\Yaml;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Controller for all XMLHttpRequest (Ajax) calls.
 *
 * @author Laurent Muller
 *
 * @Route("/ajax")
 */
class AjaxController extends AbstractController
{
    use MathTrait;

    /**
     * The cache timeout (60 minutes).
     */
    private const CACHE_TIMEOUT = 60 * 60;

    /**
     * The cache key.
     */
    private const KEY_LANGUAGE = 'LanguageService';

    /**
     * Returns a new captcha image.
     *
     * @Route("/captcha/image", name="ajax_captcha_image")
     * @IsGranted("PUBLIC_ACCESS")
     */
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
     * @Route("/captcha/validate", name="ajax_captcha_validate")
     * @IsGranted("PUBLIC_ACCESS")
     */
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
     *
     * @Route("/checkrecaptcha", name="ajax_check_recaptcha")
     * @IsGranted("ROLE_USER")
     */
    public function checkRecaptcha(Request $request, TranslatorInterface $translator): JsonResponse
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

        // @phpstan-ignore-next-line
        $errorCodes = \array_map(function ($code) use ($translator): string {
            return $translator->trans("recaptcha.{$code}", [], 'validators');
        }, $result->getErrorCodes());
        if (empty($errorCodes)) {
            $errorCodes[] = $translator->trans('recaptcha.unknown-error', [], 'validators');
        }
        $message = \implode(' ', $errorCodes);

        return $this->json($message);
    }

    /**
     * Check if an user name or e-mail exist.
     *
     * @Route("/checkuser", name="ajax_check_user")
     * @IsGranted("PUBLIC_ACCESS")
     */
    public function checkUser(Request $request, UserRepository $repository): JsonResponse
    {
        // find user name
        $usernameOrEmail = $this->getRequestString($request, 'user');
        if (null !== $usernameOrEmail && null !== $repository->findByUsernameOrEmail($usernameOrEmail)) {
            return $this->json(true);
        }

        return $this->json($this->trans('username.not_found', [], 'validators'));
    }

    /**
     * Check if an user e-mail already exists.
     *
     * @Route("/checkuseremail", name="ajax_check_user_email")
     * @IsGranted("ROLE_USER")
     */
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
     * Check if an user name already exists.
     *
     * @Route("/checkusername", name="ajax_check_user_name")
     * @IsGranted("ROLE_USER")
     */
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
     *
     * @Route("/task", name="ajax_task")
     * @IsGranted("ROLE_USER")
     */
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
        $data = \array_merge((array) $service->jsonSerialize(), [
            'message' => $this->trans('taskcompute.success'),
        ]);

        return $this->jsonTrue($data);
    }

    /**
     * Gets the datatables translations.
     *
     * @Route("/language", name="ajax_language")
     * @IsGranted("ROLE_USER")
     */
    public function language(KernelInterface $kernel, CacheItemPoolInterface $cache): JsonResponse
    {
        // check if cached
        if (!$kernel->isDebug()) {
            $item = $cache->getItem(self::KEY_LANGUAGE);
            if ($item->isHit()) {
                return JsonResponse::fromJsonString((string) $item->get());
            }
        }

        if ($file = $this->getDatatablesLang($kernel)) {
            // load localized file name
            /** @psalm-var array $lang */
            $lang = Yaml::parseFile($file);
        } else {
            // default behavior
            $keys = [
                // common
                'search',
                'processing',
                'lengthMenu',
                'info',
                'infoEmpty',
                'infoFiltered',
                'infoPostFix',
                'loadingRecords',
                'zeroRecords',
                'emptyTable',

                // paginate
                'paginate.first',
                'paginate.previous',
                'paginate.next',
                'paginate.last',

                // aria
                'aria.sortAscending',
                'aria.sortDescending',
                'aria.paginate.first',
                'aria.paginate.previous',
                'aria.paginate.next',
                'aria.paginate.last',

                //select
                'select.rows.0',
                'select.rows.1',
                'select.rows._',
            ];

            $lang = [];
            $domain = 'datatables';
            foreach ($keys as $key) {
                $current = &$lang;
                $paths = \explode('.', $key);
                foreach ($paths as $path) {
                    if (!isset($current[$path])) { // @phpstan-ignore-line
                        $current[$path] = [];
                    }
                    $current = &$current[$path];
                }
                $current = $this->trans($key, [], $domain);
            }
        }

        // format
        $lang['decimal'] = FormatUtils::getDecimal();
        $lang['thousands'] = FormatUtils::getGrouping();

        // encode
        $json = (string) \json_encode($lang);

        // save
        if (isset($item) && $item instanceof CacheItemInterface) {
            $item->set($json)
                ->expiresAfter(self::CACHE_TIMEOUT);
            $cache->save($item);
        }

        return JsonResponse::fromJsonString($json);
    }

    /**
     * Gets the list of translate languages.
     *
     * @Route("/languages", name="ajax_languages")
     * @IsGranted("ROLE_USER")
     */
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
        $message = $this->trans('translator.languages_error');
        if ($error = $service->getLastError()) {
            // translate message
            $id = $service->getDefaultIndexName() . '.' . (string) $error['code'];
            if ($this->isTransDefined($id, 'translator')) {
                $error['message'] = $this->trans($id, [], 'translator');
            }

            return $this->jsonFalse([
                'message' => $message,
                'exception' => $error,
            ]);
        }

        return $this->jsonFalse([
            'message' => $message,
        ]);
    }

    /**
     * Gets random text used to display notifications.
     *
     * @Route("/random/text", name="ajax_random_text")
     * @IsGranted("ROLE_USER")
     */
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
     * Sets a session attribute.
     *
     * The request must contains 'name' and 'value' parameters.
     *
     * @Route("/session/set", name="ajax_session_set")
     * @IsGranted("ROLE_USER")
     */
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
     * Search streets, zip codes or cities.
     *
     * @Route("/search/address", name="ajax_search_address")
     * @IsGranted("ROLE_USER")
     */
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
     * @Route("/search/customer", name="ajax_search_customer")
     * @IsGranted("ROLE_USER")
     */
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
     * @Route("/search/distinct", name="ajax_search_distinct")
     * @IsGranted("ROLE_USER")
     */
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
     * @Route("/search/product", name="ajax_search_product")
     * @IsGranted("ROLE_USER")
     */
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
     * @Route("/search/supplier", name="ajax_search_supplier")
     * @IsGranted("ROLE_USER")
     */
    public function searchSupplier(Request $request, ProductRepository $productRepository, TaskRepository $taskRepository): JsonResponse
    {
        return $this->getDistincValuesForCategoryItem($request, $productRepository, $taskRepository, 'supplier');
    }

    /**
     * Search distinct customer's titles.
     *
     * @Route("/search/title", name="ajax_search_title")
     * @IsGranted("ROLE_USER")
     */
    public function searchTitle(Request $request, CustomerRepository $repository): JsonResponse
    {
        return $this->getDistinctValues($request, $repository, 'title');
    }

    /**
     * Search distinct units from products and tasks.
     *
     * @Route("/search/unit", name="ajax_search_unit")
     * @IsGranted("ROLE_USER")
     */
    public function searchUnit(Request $request, ProductRepository $productRepository, TaskRepository $taskRepository): JsonResponse
    {
        return $this->getDistincValuesForCategoryItem($request, $productRepository, $taskRepository, 'unit');
    }

    /**
     * Translate a text.
     *
     * @Route("/translate", name="ajax_translate")
     * @IsGranted("ROLE_USER")
     */
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
            $message = $this->trans('translator.translate_error');
            if ($error = $service->getLastError()) {
                // translate message
                $id = $service->getDefaultIndexName() . '.' . (string) $error['code'];
                if ($this->isTransDefined($id, 'translator')) {
                    $error['message'] = $this->trans($id, [], 'translator');
                }

                return $this->jsonFalse([
                    'message' => $message,
                    'exception' => $error,
                ]);
            }

            return $this->jsonFalse([
                'message' => $message,
            ]);
        } catch (\Exception $e) {
            return $this->jsonException($e, $this->trans('translator.translate_error'));
        }
    }

    /**
     * Update the calculation's totals.
     *
     * @Route("/update", name="ajax_update")
     * @IsGranted("ROLE_USER")
     */
    public function updateCalculation(Request $request, CalculationService $service, LoggerInterface $logger): JsonResponse
    {
        // ajax call ?
        if (null !== ($response = $this->checkAjaxCall($request))) {
            return $response;
        }

        // parameters
        /** @psalm-var array|null $source */
        $source = Utils::getRequestInputBag($request)->get('calculation');
        if (null === $source) {
            return $this->jsonFalse([
                'message' => $this->trans('calculation.edit.error.update_total'),
            ]);
        }

        try {
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
     * @return JsonResponse null if the request is a XMLHttpRequest call, a JSON error response otherwise
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
     * Gets the datatables translations file for the current locale.
     *
     * @param KernelInterface $kernel the kernel to get root directory
     *
     * @return string|null the language file, if exists; null otherwise
     */
    private function getDatatablesLang(KernelInterface $kernel): ?string
    {
        $dir = $kernel->getProjectDir();
        $locale = \Locale::getDefault();
        $file = "$dir/translations/datatables.$locale.yaml";

        return FileUtils::exists($file) ? $file : null;
    }

    /**
     * Search distinct values.
     *
     * @param Request            $request    the request to get search parameters
     * @param AbstractRepository $repository the respository to search in
     * @param string             $field      the field name to search for
     *
     * @template T of \App\Entity\AbstractEntity
     * @psalm-param AbstractRepository<T> $repository
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
     * @param ProductRepository $productRepository the product respository to search in
     * @param TaskRepository    $taskRepository    the task respository to search in
     * @param string            $field             the field name to search for
     */
    private function getDistincValuesForCategoryItem(Request $request, ProductRepository $productRepository, TaskRepository $taskRepository, string $field): JsonResponse
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
}
