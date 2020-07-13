<?php
/*
 * This file is part of the Calculation package.
 *
 * Copyright (c) 2019 bibi.nu. All rights reserved.
 *
 * This computer code is protected by copyright law and international
 * treaties. Unauthorised reproduction or distribution of this code, or
 * any portion of it, may result in severe civil and criminal penalties,
 * and will be prosecuted to the maximum extent possible under the law.
 */

declare(strict_types=1);

namespace App\Controller;

use App\Repository\BaseRepository;
use App\Repository\CalculationRepository;
use App\Repository\ProductRepository;
use App\Repository\UserRepository;
use App\Service\CalculationService;
use App\Service\CaptchaImageService;
use App\Service\FakerService;
use App\Service\SwissPostService;
use App\Traits\MathTrait;
use App\Translator\TranslatorFactory;
use App\Utils\DatabaseInfo;
use App\Utils\SymfonyUtils;
use App\Utils\Utils;
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
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Controller for all XMLHttpRequest (Ajax) calls.
 *
 * @Route("/ajax")
 */
class AjaxController extends BaseController
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

        return $this->json([
            'result' => true,
            'content' => $content,
        ]);
    }

    /**
     * Render the MySql informations content.
     *
     * @Route("/mysql", name="ajax_mysql")
     * @IsGranted("ROLE_USER")
     */
    public function aboutMySql(DatabaseInfo $info): JsonResponse
    {
        $parameters = [
            'version' => $info->getVersion(),
            'database' => $info->getDatabase(),
            'configuration' => $info->getConfiguration(),
        ];
        $content = $this->renderView('about/mysql_content.html.twig', $parameters);

        return $this->json([
            'result' => true,
            'content' => $content,
        ]);
    }

    /**
     * Render the PHP informations content.
     *
     * @Route("/php", name="ajax_php")
     * @IsGranted("ROLE_USER")
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

        return $this->json([
            'result' => true,
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

        return $this->json([
            'result' => true,
            'content' => $content,
        ]);
    }

    /**
     * Render Symfony informations content.
     *
     * @Route("/symfony", name="ajax_symfony")
     * @IsGranted("ROLE_USER")
     */
    public function aboutSymfony(KernelInterface $kernel, RouterInterface $router): JsonResponse
    {
        $bundles = SymfonyUtils::getBundles($kernel);
        $packages = SymfonyUtils::getPackages($kernel);
        $routes = SymfonyUtils::getRoutes($router);

        $projectDir = \str_replace('\\', '/', $kernel->getProjectDir());
        $cacheDir = SymfonyUtils::formatPath($kernel->getCacheDir(), $projectDir) . ' (' . SymfonyUtils::formatFileSize($kernel->getCacheDir()) . ')';
        $logDir = SymfonyUtils::formatPath($kernel->getLogDir(), $projectDir) . ' (' . SymfonyUtils::formatFileSize($kernel->getLogDir()) . ')';
        $endOfMaintenance = $this->formatExpired(Kernel::END_OF_MAINTENANCE);
        $endOfLife = $this->formatExpired(Kernel::END_OF_LIFE);

        $locale = \Locale::getDefault();
        $localeName = Locales::getName($locale, 'en');

        $parameters = [
            'kernel' => $kernel,
            'version' => Kernel::VERSION,
            'bundles' => $bundles,
            'packages' => $packages,
            'routes' => $routes,
            'timezone' => \date_default_timezone_get(),
            'projectDir' => $projectDir,
            'cacheDir' => $cacheDir,
            'logDir' => $logDir,
            'endOfMaintenance' => $endOfMaintenance,
            'endOfLife' => $endOfLife,
            'locale' => $localeName . ' - ' . $locale,
        ];
        $content = $this->renderView('about/symfony_content.html.twig', $parameters);

        return $this->json([
            'result' => true,
            'content' => $content,
        ]);
    }

    /**
     * Returns a new captcha image.
     *
     * @Route("/captcha/image", name="ajax_captcha_image", methods={"GET", "POST"})
     * @IsGranted("IS_AUTHENTICATED_ANONYMOUSLY")
     */
    public function captchaImage(CaptchaImageService $service): JsonResponse
    {
        if ($data = $service->generateImage(true)) {
            return $this->json([
                'result' => true,
                'data' => $data,
            ]);
        }

        return $this->json([
            'result' => false,
            'message' => $this->trans('captcha.generate', 'validators'),
        ]);
    }

    /**
     * Validate a captcha image.
     *
     * @Route("/captcha/validate", name="ajax_captcha_validate", methods={"GET", "POST"})
     * @IsGranted("IS_AUTHENTICATED_ANONYMOUSLY")
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
     * @Route("/checkemail", name="ajax_check_email", methods={"GET", "POST"})
     * @IsGranted("ROLE_USER")
     */
    public function checkEmail(Request $request, UserRepository $repository): JsonResponse
    {
        // get values
        $id = (int) $request->get('id', 0);
        $email = $request->get('email', null);

        // check length
        $message = null;
        if (null === $email) {
            $message = 'fos_user.email.blank';
        } elseif (\strlen($email) < 2) {
            $message = 'fos_user.email.short';
        } elseif (\strlen($email) > 180) {
            $message = 'fos_user.email.long';
        } else {
            // find user and check if same
            $user = $repository->findByEmail($email);
            if (null !== $user && $id !== $user->getId()) {
                $message = 'fos_user.email.already_used';
            }
        }

        if ($message) {
            $response = $this->trans($message, [], 'validators');
        } else {
            $response = true;
        }

        return $this->json($response);
    }

    /**
     * Check if the given reCaptcha response (if any) is valid.
     *
     * @Route("/checkrecaptcha", name="ajax_check_recaptcha", methods={"GET", "POST"})
     * @IsGranted("ROLE_USER")
     */
    public function checkRecaptcha(Request $request, TranslatorInterface $translator): JsonResponse
    {
        // get values
        $remoteIp = $request->getClientIp();
        $response = $request->get('g-recaptcha-response', $request->get('response'));
        $secret = $this->getParameter('recaptcha_secret');

        // verify
        $recaptcha = new ReCaptcha($secret);
        $result = $recaptcha->verify($response, $remoteIp);

        // ok?
        if ($result->isSuccess()) {
            return $this->json(true);
        }

        // translate errors
        $errorCodes = \array_map(function ($code) use ($translator) {
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
     * @Route("/checkname", name="ajax_check_name", methods={"GET", "POST"})
     * @IsGranted("ROLE_USER")
     */
    public function checkUsername(Request $request, UserRepository $repository): JsonResponse
    {
        // get values
        $id = (int) $request->get('id', 0);
        $username = $request->get('username', null);

        // check length
        if (null === $username) {
            $response = $this->trans('fos_user.username.blank', [], 'validators');
        } elseif (\strlen($username) < 2) {
            $response = $this->trans('fos_user.username.short', [], 'validators');
        } elseif (\strlen($username) > 180) {
            $response = $this->trans('fos_user.username.long', [], 'validators');
        } else {
            // find user and check if same
            $user = $repository->findByUsername($username);
            if (null !== $user && $id !== $user->getId()) {
                $response = $this->trans('fos_user.username.already_used', [], 'validators');
            } else {
                $response = true;
            }
        }

        return $this->json($response);
    }

    /**
     * Check if an user's name or an user's e-mail exists.
     *
     * @Route("/checkexist", name="ajax_check_exist", methods={"GET", "POST"})
     * @IsGranted("IS_AUTHENTICATED_ANONYMOUSLY")
     */
    public function checkUsernameOrEmail(Request $request, UserRepository $repository): JsonResponse
    {
        // find user name
        $usernameOrEmail = $request->get('username');
        if (null !== $usernameOrEmail && null !== $repository->findByUsernameOrEmail($usernameOrEmail)) {
            $response = true;
        } else {
            $response = $this->trans('fos_user.username.not_found', [], 'validators');
        }

        return $this->json($response);
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
                if (!isset($current[$path])) {
                    $current[$path] = [];
                }
                $current = &$current[$path];
            }
            $current = $this->trans($key, [], $domain);
        }

        // format
        $lang['decimal'] = $this->getDefaultDecimal();
        $lang['thousands'] = $this->getDefaultGrouping();

        // encode
        $json = \json_encode($lang);

        // save
        //if (!$kernel->isDebug()) {
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
        $languages = $service->getLanguages();

        return $this->json($languages);
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

        return $this->json([
            'result' => true,
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
        return $this->searchDistinct($request, $repository, 'customer');
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
            return $this->json([
                'result' => false,
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
        return $this->searchDistinct($request, $repository, 'supplier');
    }

    /**
     * Search distinct product's units in existing products.
     *
     * @Route("/search/unit", name="ajax_search_unit")
     * @IsGranted("ROLE_USER")
     */
    public function searchUnit(Request $request, ProductRepository $repository): JsonResponse
    {
        return $this->searchDistinct($request, $repository, 'unit');
    }

    /**
     * Translate a text.
     *
     * @Route("/translate", name="ajax_translate")
     * @IsGranted("ROLE_USER")
     */
    public function translate(Request $request, TranslatorFactory $factory): JsonResponse
    {
        // get parameters
        $to = $request->get('to', '');
        $from = $request->get('from');
        $text = $request->get('text', '');
        $class = $request->get('service', TranslatorFactory::DEFAULT_SERVICE);
        $service = $factory->getService($class);

        // check parameters
        if (!Utils::isString($text)) {
            return $this->json([
                'result' => false,
                'message' => $this->trans('translator.text_error'),
            ]);
        }
        if (!Utils::isString($to)) {
            return $this->json([
                'result' => false,
                'message' => $this->trans('translator.to_error'),
            ]);
        }

        try {
            // translate
            if ($result = $service->translate($text, $to, $from)) {
                return $this->json([
                    'result' => true,
                    'data' => $result,
                ]);
            }
            $message = $this->trans('translator.translate_error');
            if ($error = $service->getLastError()) {
                $errorCode = $error['code'];
                $errorMessage = $error['message'];
                $key = $service->getName() . '.' . $errorCode;
                if ($this->transDefined($key, 'translator')) {
                    $errorMessage = $this->trans($key, [], 'translator');
                }

                return $this->json([
                    'result' => false,
                    'message' => $message,
                    'exception' => [
                        'code' => $errorCode,
                        'message' => $errorMessage,
                    ],
                ]);
            }

            return $this->json([
                'result' => false,
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
            return $this->json([
                'result' => false,
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
            $minMargin = $service->getMinMargin();
            if ($request->get('adjust', false) && $parameters['overall_below']) {
                $this->adjustUserMargin($parameters, $minMargin);
            }
            $parameters['min_margin'] = $minMargin;

            // render table
            $body = $this->renderView('calculation/calculation_ajax_totals.html.twig', $parameters);

            // ok
            $result = [
                'result' => true,
                'body' => $body,
                'overall_margin' => $parameters['overall_margin'],
                'overall_total' => $parameters['overall_total'],
                'overall_below' => $parameters['overall_below'],
            ];

            return $this->json($result);
        } catch (\Exception $e) {
            $message = $this->trans('calculation.edit.error.update_total');
            $logger->error($message, ['exception' => $e]);

            return $this->jsonException($e, $message);
        }
    }

    /**
     * Finds a groups for the given identifier.
     *
     * @param array $groups the groups to search in
     * @param int   $id     the identifier to search for
     *
     * @return array the group, if found, a new group otherwise
     */
    private function &findGroup(array &$groups, int $id): array
    {
        foreach ($groups as &$group) {
            if ($group['id'] === $id) {
                return $group;
            }
        }

        $group = [
            'id' => $id,
            'amount' => 0.0,
            'margin' => 0.0,
            'margin_amount' => 0.0,
            'total' => 0.0,
            'description' => 'Unknown',
        ];
        $groups[] = $group;

        return $group;
    }

    /**
     * Adjust the user margin to have the desired overall minimum margin.
     *
     * @param array $parameters the parameters (rows) to update
     * @param float $minMargin  the desired minimum margin
     */
    private function adjustUserMargin(array &$parameters, float $minMargin): void
    {
        // no more below
        $parameters['overall_below'] = false;

        // get rows
        $groups = &$parameters['groups'];
        $totalGroup = &$this->findGroup($groups, CalculationService::ROW_TOTAL_GROUP);
        $netGroup = &$this->findGroup($groups, CalculationService::ROW_TOTAL_NET);
        $userGroup = &$this->findGroup($groups, CalculationService::ROW_USER_MARGIN);
        $overallGroup = &$this->findGroup($groups, CalculationService::ROW_OVERALL_TOTAL);

        // get values
        $groupAmount = $totalGroup['amount'];
        $userMargin = $userGroup['margin'];
        $netTotal = $netGroup['total'];

        // net total?
        if ($this->isFloatZero($netTotal)) {
            return;
        }

        // compute user margin to reach minimum
        $userMargin = (($minMargin + 1) * $groupAmount / $netTotal) - 1;

        // round up
        $userMargin = \ceil($userMargin * 100.0) / 100.0;

        // update user margin
        $userGroup['margin'] = $userMargin;
        $userGroup['total'] = $netTotal * $userMargin;

        // update overall total
        $overallGroup['total'] = $netTotal + $userGroup['total'];
        $overallGroup['margin'] = ($overallGroup['total'] / $groupAmount) - 1;
        $overallGroup['margin_amount'] = $overallGroup['total'] - $groupAmount;

        // update parameters
        $parameters['overall_margin'] = (int) (100 * $userMargin);
    }

    /**
     * Checks if the given request is a XMLHttpRequest (ajax) call.
     *
     * @return JsonResponse null if is a XMLHttpRequest call, a JSON error response otherwise
     */
    private function checkAjaxCall(Request $request): ?JsonResponse
    {
        // ajax call ?
        if ($request->isXmlHttpRequest()) {
            return null;
        }

        return $this->json([
            'result' => false,
            'message' => 'Invalid Http Request.',
        ]);
    }

    /**
     * Format the expired date.
     *
     * @param string $date the date to format
     *
     * @return string the formatted date, if applicable; 'Unknown' otherwise
     */
    private function formatExpired(string $date): string
    {
        $date = \DateTime::createFromFormat('m/Y', $date);
        if (false !== $date) {
            return $this->localeDate($date->modify('last day of this month 23:59:59'));
        }

        return 'Unknown';
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
     * Search distinct values.
     *
     * @param Request        $request    the request to get search parameters
     * @param BaseRepository $repository the respository to search in
     * @param string         $field      the field name to search for
     *
     * @return JsonResponse the found values
     */
    private function searchDistinct(Request $request, BaseRepository $repository, string $field): JsonResponse
    {
        try {
            $search = (string) $request->get('query', '');
            if (Utils::isString($search)) {
                $maxResults = (int) $request->get('limit', 15);
                $values = $repository->getDistinctValues($field, $search, $maxResults);
                if (!empty($values)) {
                    return $this->json($values);
                }
            }

            // empty
            return $this->json([
                'result' => false,
                'values' => [],
            ]);
        } catch (\Exception $e) {
            return $this->jsonException($e);
        }
    }
}
