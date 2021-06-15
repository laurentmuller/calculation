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
use App\Util\DatabaseInfo;
use App\Util\FileUtils;
use App\Util\FormatUtils;
use App\Util\SymfonyUtils;
use App\Util\Utils;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use ReCaptcha\ReCaptcha;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Intl\Locales;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;
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
     * Render the licence informations content.
     *
     * @Route("/licence", name="ajax_licence")
     * @IsGranted("ROLE_USER")
     */
    public function aboutLicence(): JsonResponse
    {
        $content = $this->renderView('about/licence_content.html.twig');

        return $this->jsonTrue([
            'content' => $content,
        ]);
    }

    /**
     * Render the MySql informations content.
     *
     * @Route("/mysql", name="ajax_mysql")
     * @IsGranted("ROLE_ADMIN")
     */
    public function aboutMySql(DatabaseInfo $info): JsonResponse
    {
        $parameters = [
            'version' => $info->getVersion(),
            'database' => $info->getDatabase(),
            'configuration' => $info->getConfiguration(),
        ];
        $content = $this->renderView('about/mysql_content.html.twig', $parameters);

        return $this->jsonTrue([
            'content' => $content,
        ]);
    }

    /**
     * Render the PHP informations content.
     *
     * @Route("/php", name="ajax_php")
     * @IsGranted("ROLE_ADMIN")
     */
    public function aboutPhp(Request $request): JsonResponse
    {
        $parameters = [
            'phpInfo' => SymfonyUtils::getPhpInfoHtml(),
            'cache' => $this->getCacheClass(),
            'extensions' => $this->getLoadedExtensions(),
            'apache' => $this->getApacheVersion($request),
        ];
        $content = $this->renderView('about/php_content.html.twig', $parameters);

        return $this->jsonTrue([
            'content' => $content,
        ]);
    }

    /**
     * Render the policy informations content.
     *
     * @Route("/policy", name="ajax_policy")
     * @IsGranted("ROLE_USER")
     */
    public function aboutPolicy(): JsonResponse
    {
        $content = $this->renderView('about/policy_content.html.twig');

        return $this->jsonTrue([
            'content' => $content,
        ]);
    }

    /**
     * Render Symfony informations content.
     *
     * @Route("/symfony", name="ajax_symfony")
     * @IsGranted("ROLE_ADMIN")
     */
    public function aboutSymfony(KernelInterface $kernel, RouterInterface $router): JsonResponse
    {
        $bundles = SymfonyUtils::getBundles($kernel);
        $packages = SymfonyUtils::getPackages($kernel);
        $routes = SymfonyUtils::getRoutes($router);

        $projectDir = \str_replace('\\', '/', $kernel->getProjectDir());
        $cacheDir = SymfonyUtils::formatPath($kernel->getCacheDir(), $projectDir) . ' (' . SymfonyUtils::formatFileSize($kernel->getCacheDir()) . ')';
        $logDir = SymfonyUtils::formatPath($kernel->getLogDir(), $projectDir) . ' (' . SymfonyUtils::formatFileSize($kernel->getLogDir()) . ')';
        $endOfMaintenance = SymfonyUtils::formatExpired(Kernel::END_OF_MAINTENANCE) . ' (' . SymfonyUtils::daysBeforeExpiration(Kernel::END_OF_MAINTENANCE) . ')';
        $endOfLife = SymfonyUtils::formatExpired(Kernel::END_OF_LIFE) . ' (' . SymfonyUtils::daysBeforeExpiration(Kernel::END_OF_LIFE) . ')';

        $locale = \Locale::getDefault();
        $localeName = Locales::getName($locale, 'en');
        $xdebug_enabled = \extension_loaded('xdebug');
        $apcu_enabled = \extension_loaded('apcu') && \filter_var(\ini_get('apc.enabled'), \FILTER_VALIDATE_BOOLEAN);
        $zend_opcache_enabled = \extension_loaded('Zend OPcache') && \filter_var(\ini_get('opcache.enable'), \FILTER_VALIDATE_BOOLEAN);

        $parameters = [
            'kernel' => $kernel,
            'version' => Kernel::VERSION,
            'bundles' => $bundles,
            'runtimePackages' => $packages['runtime'] ?? [],
            'debugPackages' => $packages['debug'] ?? [],
            'runtimeRoutes' => $routes['runtime'] ?? [],
            'debugRoutes' => $routes['debug'] ?? [],
            'timezone' => \date_default_timezone_get(),
            'projectDir' => $projectDir,
            'cacheDir' => $cacheDir,
            'logDir' => $logDir,
            'endOfMaintenance' => $endOfMaintenance,
            'endOfLife' => $endOfLife,
            'locale' => $localeName . ' - ' . $locale,
            'xdebug_enabled' => \json_encode($xdebug_enabled),
            'apcu_enabled' => \json_encode($apcu_enabled),
            'zend_opcache_enabled' => \json_encode($zend_opcache_enabled),
        ];
        $content = $this->renderView('about/symfony_content.html.twig', $parameters);

        return $this->jsonTrue([
            'content' => $content,
        ]);
    }

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
    public function checkCaptcha(Request $request, CaptchaImageService $service): JsonResponse
    {
        if (!$service->validateTimeout()) {
            $response = $this->trans('captcha.timeout', [], 'validators');
        } elseif (!$service->validateToken($request->get('_captcha'))) {
            $response = $this->trans('captcha.invalid', [], 'validators');
        } else {
            $response = true;
        }

        return $this->json($response);
    }

    /**
     * Check if an user's e-mail already exists.
     *
     * @Route("/checkemail", name="ajax_check_email")
     * @IsGranted("ROLE_USER")
     */
    public function checkEmail(Request $request, UserRepository $repository): JsonResponse
    {
        // get values
        $id = (int) $request->get('id', 0);
        $email = $request->get('email', null);

        // check length
        if (null === $email) {
            $response = $this->trans('email.blank', [], 'validators');
        } elseif (\strlen($email) < 2) {
            $response = $this->trans('email.short', [], 'validators');
        } elseif (\strlen($email) > 180) {
            $response = $this->trans('email.long', [], 'validators');
        } else {
            // find user and check if same
            $user = $repository->findByEmail($email);
            if (null !== $user && $id !== $user->getId()) {
                $response = $this->trans('email.already_used', [], 'validators');
            } else {
                $response = true;
            }
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
        $response = $request->get('g-recaptcha-response', $request->get('response'));
        $secret = $this->getStringParameter('recaptcha_secret');

        // verify
        $recaptcha = new ReCaptcha($secret);
        $result = $recaptcha->verify($response, $remoteIp);

        // ok?
        if ($result->isSuccess()) {
            return $this->json(true);
        }

        // translate errors
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
     * Check if an user's name already exists.
     *
     * @Route("/checkname", name="ajax_check_name")
     * @IsGranted("ROLE_USER")
     */
    public function checkUsername(Request $request, UserRepository $repository): JsonResponse
    {
        // get values
        $id = (int) $request->get('id', 0);
        $username = $request->get('username', null);

        // check length
        if (null === $username) {
            $response = $this->trans('username.blank', [], 'validators');
        } elseif (\strlen($username) < 2) {
            $response = $this->trans('username.short', [], 'validators');
        } elseif (\strlen($username) > 180) {
            $response = $this->trans('username.long', [], 'validators');
        } else {
            // find user and check if same
            $user = $repository->findByUsername($username);
            if (null !== $user && $id !== $user->getId()) {
                $response = $this->trans('username.already_used', [], 'validators');
            } else {
                $response = true;
            }
        }

        return $this->json($response);
    }

    /**
     * Check if an user's name or an user's e-mail exists.
     *
     * @Route("/checkexist", name="ajax_check_exist")
     * @IsGranted("PUBLIC_ACCESS")
     */
    public function checkUsernameOrEmail(Request $request, UserRepository $repository): JsonResponse
    {
        // find user name
        $usernameOrEmail = $request->get('username');
        if (null !== $usernameOrEmail && null !== $repository->findByUsernameOrEmail($usernameOrEmail)) {
            $response = true;
        } else {
            $response = $this->trans('username.not_found', [], 'validators');
        }

        return $this->json($response);
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
        $id = (int) $request->get('id', 0);
        $quantity = (float) $request->get('quantity', 0.0);

        // validate
        if ($quantity <= 0) {
            return $this->jsonFalse([
                'message' => $this->trans('taskcompute.error.quantity'),
            ]);
        }

        /** @var ?\App\Entity\Task $task */
        $task = $repository->find($id);
        if (null === $task) {
            return $this->jsonFalse([
                'message' => $this->trans('taskcompute.error.task'),
            ]);
        }

        // update service and compute
        $service->setTask($task)
            ->setQuantity($quantity)
            ->compute($request);

        $data = \array_merge($service->jsonSerialize(), [
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
    public function language(KernelInterface $kernel, AdapterInterface $cache): JsonResponse
    {
        // check if cached
        if (!$kernel->isDebug()) {
            $item = $cache->getItem(self::KEY_LANGUAGE);
            if ($item->isHit()) {
                return JsonResponse::fromJsonString($item->get());
            }
        }

        if ($file = $this->getDatatablesLang($kernel)) {
            // load localized file name
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
        if (isset($item)) {
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
        $class = $request->get('service', TranslatorFactory::DEFAULT_SERVICE);
        $service = $factory->getService($class);
        if ($languages = $service->getLanguages()) {
            return $this->jsonTrue([
                'languages' => $languages,
            ]);
        }

        // error
        $message = $this->trans('translator.languages_error');
        if ($error = $service->getLastError()) {
            // translate message
            $id = $service->getName() . '.' . $error['code'];
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
        $maxNbChars = (int) $request->get('maxNbChars', 145);
        $indexSize = (int) $request->get('indexSize', 2);

        /** @var \Faker\Generator $faker */
        $faker = $service->getFaker();
        $text = $faker->realText($maxNbChars, $indexSize);

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
        $name = $request->get('name');
        $value = $request->get('value');
        if (null !== $name && null !== $value) {
            $this->setSessionValue($name, \json_decode($value));
            $result = true;
        }

        return new JsonResponse($result);
    }

    /**
     * Search streets, zip codes or cities.
     *
     * @Route("/search/address", name="ajax_search_address")
     * @IsGranted("ROLE_USER")
     */
    public function searchAddress(Request $request, SwissPostService $service): JsonResponse
    {
        $zip = $request->get('zip');
        $city = $request->get('city');
        $street = $request->get('street');
        $limit = (int) $request->get('limit', 25);

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
        $className = 'App\\Entity\\' . $request->get('entity', '');
        if (!\class_exists($className)) {
            return $this->jsonFalse([
                'values' => [],
            ]);
        }

        // field
        $field = (string) $request->get('field');
        if (!Utils::isString($field)) {
            return $this->jsonFalse([
                'values' => [],
            ]);
        }

        try {
            /** @psalm-var AbstractRepository<\App\Entity\AbstractEntity> $repository */
            $repository = $manager->getRepository($className);

            return $this->getDistinctValues($request, $repository, $field);
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
            $search = (string) $request->get('query', '');
            if (Utils::isString($search)) {
                $maxResults = (int) $request->get('limit', 15);
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
     * Search distinct product's suppliers in existing products.
     *
     * @Route("/search/supplier", name="ajax_search_supplier")
     * @IsGranted("ROLE_USER")
     */
    public function searchSupplier(Request $request, ProductRepository $repository): JsonResponse
    {
        return $this->getDistinctValues($request, $repository, 'supplier');
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
        $search = (string) $request->get('query', '');
        if (Utils::isString($search)) {
            $limit = (int) $request->get('limit', 15);
            $productUnits = $productRepository->getDistinctValues('unit', $search);
            $taskUnits = $taskRepository->getDistinctValues('unit', $search);
            $values = \array_unique(\array_merge($productUnits, $taskUnits));
            \sort($values, \SORT_NATURAL);
            $values = \array_slice($values, 0, $limit);
            if (!empty($values)) {
                return $this->json($values);
            }
        }

        // empty
        return $this->jsonFalse([
            'values' => [],
        ]);
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
        if ($response = $this->checkAjaxCall($request)) {
            return $response;
        }

        // get parameters
        $to = $request->get('to', '');
        $from = $request->get('from');
        $text = $request->get('text', '');
        $class = $request->get('service', TranslatorFactory::DEFAULT_SERVICE);
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
                $id = $service->getName() . '.' . $error['code'];
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
        if ($response = $this->checkAjaxCall($request)) {
            return $response;
        }

        // parameters
        $source = $request->get('calculation');
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
            if ($request->get('adjust', false) && $parameters['overall_below']) {
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
     * Gets the Apache version.
     *
     * @return string|bool the Apache version on success or <code>false</code> on failure
     */
    private function getApacheVersion(Request $request)
    {
        //$request->server
        $matches = [];
        $regex = '/Apache\/(?P<version>[1-9][0-9]*\.[0-9][^\s]*)/i';

        if (\function_exists('apache_get_version')) {
            if (($version = apache_get_version()) && \preg_match($regex, $version, $matches)) {
                return $matches['version'];
            }
        }

        $server = $request->server;
        $software = $server->get('SERVER_SOFTWARE', false);
        if ($software && false !== \stripos($software, 'apache')) {
            if (\preg_match($regex, $software, $matches)) {
                return $matches['version'];
            }
        }

        return false;
    }

    /**
     * Gets the cache adapter class.
     */
    private function getCacheClass(): string
    {
        return $this->getApplication()->getCacheClass();
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
     * @return JsonResponse the found values
     *
     * @template T of \App\Entity\AbstractEntity
     * @psalm-param AbstractRepository<T> $repository
     */
    private function getDistinctValues(Request $request, AbstractRepository $repository, string $field): JsonResponse
    {
        try {
            $search = (string) $request->get('query', '');
            if (Utils::isString($search)) {
                $limit = (int) $request->get('limit', 15);
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
     * Returns a string with the names of all modules compiled and loaded.
     *
     * @return string all the modules names
     */
    private function getLoadedExtensions(): string
    {
        $extensions = \array_map('strtolower', \get_loaded_extensions());
        \sort($extensions);

        return \implode(', ', $extensions);
    }

    /**
     * Returns a Json response with false as result.
     *
     * @param array $data the data to merge within the response
     */
    private function jsonFalse(array $data = []): JsonResponse
    {
        return $this->json(\array_merge_recursive(['result' => false], $data));
    }

    /**
     * Returns a Json response with true as result.
     *
     * @param array $data the data to merge within the response
     */
    private function jsonTrue(array $data = []): JsonResponse
    {
        return $this->json(\array_merge_recursive(['result' => true], $data));
    }
}
