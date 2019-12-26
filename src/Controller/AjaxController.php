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

use App\Entity\CategoryMargin;
use App\Repository\BaseRepository;
use App\Repository\CalculationRepository;
use App\Repository\ProductRepository;
use App\Service\CalculationService;
use App\Service\CaptchaImageService;
use App\Service\FakerService;
use App\Service\OpenWeatherService;
use App\Service\SwissPostService;
use App\Traits\MathTrait;
use App\Translator\BingTranslatorService;
use App\Translator\ITranslatorService;
use App\Translator\TranslatorFactory;
use App\Utils\SymfonyUtils;
use App\Utils\Utils;
use App\Validator\Password;
use App\Validator\PasswordValidator;
use Doctrine\ORM\EntityManagerInterface;
use FOS\UserBundle\Model\UserManagerInterface;
use ReCaptcha\ReCaptcha;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Intl\Locales;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Validator\ContainerConstraintValidatorFactory;
use Symfony\Component\Validator\Validation;
use Symfony\Contracts\Translation\TranslatorInterface;
use ZxcvbnPhp\Zxcvbn;

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
     * Render the MySql informations content.
     *
     * @Route("/mysql", name="ajax_mysql")
     * @IsGranted("ROLE_USER")
     */
    public function aboutMySql(EntityManagerInterface $manager): JsonResponse
    {
        $parameters = [
            'version' => SymfonyUtils::getSqlVersion($manager),
            'database' => SymfonyUtils::getSqlDatabase($manager),
            'configuration' => SymfonyUtils::getSqlConfiguration($manager),
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
    public function aboutPhp(): JsonResponse
    {
        $parameters = [
            'phpInfo' => SymfonyUtils::getPhpInfo(),
            'cache' => $this->getCacheClass(),
            'extensions' => $this->getLoadedExtensions(),
            'apache' => $this->getApacheVersion(),
        ];
        $content = $this->renderView('about/php_content.html.twig', $parameters);

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
        $rootDir = SymfonyUtils::formatPath($kernel->getProjectDir(), $projectDir);
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
            'rootDir' => $rootDir,
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
     * Translate a text with the Bing service.
     *
     * @Route("/bing", name="ajax_bing")
     * @IsGranted("ROLE_USER")
     */
    public function bing(Request $request, TranslatorFactory $factory): JsonResponse
    {
        $service = $factory->getService(TranslatorFactory::BING_SERVICE);

        return $this->translator($request, $service);
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
            'message' => 'Unable to get a new image.',
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
    public function checkEmail(Request $request, UserManagerInterface $manager): JsonResponse
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
            $user = $manager->findUserByEmail($email);
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
     * Check if a password is valid.
     *
     * @Route("/checkpassword", name="ajax_check_password", methods={"GET", "POST"})
     * @IsGranted("ROLE_USER")
     */
    public function checkPassword(Request $request, TranslatorInterface $translator): JsonResponse
    {
        // get values
        $password = (string) $request->get('password');
        $allViolations = !$request->isXmlHttpRequest();

        // create constraint
        $constraint = new Password([
            'blackList' => true,
            'email' => true,
            'minLength' => 8,
            'minStrength' => 2,
            'pwned' => true,
            'caseDiff' => true,
            'letters' => true,
            'numbers' => true,
            'specialCharacter' => true,
            'allViolations' => $allViolations,
        ]);

        // create validator
        $factory = new ContainerConstraintValidatorFactory($this->container);
        $validator = Validation::createValidatorBuilder()
            ->setConstraintValidatorFactory($factory)
            ->setTranslationDomain('validators')
            ->setTranslator($translator)
            ->getValidator();

        // validate
        /** @var \Symfony\Component\Validator\ConstraintViolationListInterface|\Symfony\Component\Validator\ConstraintViolationInterface $violations */
        $violations = $validator->validate($password, $constraint);

        // get score
        $zx = new Zxcvbn();
        $strength = $zx->passwordStrength($password);
        $score = $strength['score'];
        $verdict = PasswordValidator::translateLevel($translator, $score);
        $result = [
            'result' => true,
            'score' => $score,
            'verdict' => $verdict,
        ];

        // check
        if (0 !== \count($violations)) {
            if ($allViolations) {
                //$result = [];
                foreach ($violations as $violation) {
                    $result[] = $violation->getMessage();
                }
            } else {
                $result['result'] = false;
                $result['message'] = $violations[0]->getMessage();
            }
        }

        return $this->json($result);
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
    public function checkUsername(Request $request, UserManagerInterface $manager): JsonResponse
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
            $user = $manager->findUserByUsername($username);
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
    public function checkUsernameOrEmail(Request $request, UserManagerInterface $manager): JsonResponse
    {
        // find user name
        $usernameOrEmail = $request->get('username');
        if (null !== $usernameOrEmail && null !== $manager->findUserByUsernameOrEmail($usernameOrEmail)) {
            $response = true;
        } else {
            $response = $this->trans('fos_user.username.not_found', [], 'validators');
        }

        return $this->json($response);
    }

    /**
     * Identifies the language of a piece of text.
     *
     * @Route("/detect", name="ajax_detect")
     * @IsGranted("ROLE_USER")
     */
    public function detect(Request $request, BingTranslatorService $translator)
    {
        $text = $request->get('text', '');
        if (!Utils::isString($text)) {
            return $this->json([
                'result' => false,
                'message' => $this->trans('translator.text_error'),
            ]);
        }

        try {
            // detect
            if ($result = $translator->detect($text)) {
                return $this->json([
                    'result' => true,
                    'data' => $result,
                ]);
            }
            $error = $translator->getLastError();
            if ($error) {
                return $this->json([
                    'result' => false,
                    'message' => $this->trans('translator.detect_error'),
                    'exception' => [
                        'code' => $error->code,
                        'message' => $error->message,
                    ],
                ]);
            }

            return $this->json([
                'result' => false,
                'message' => $this->trans('translator.translate_error'),
            ]);
        } catch (\Exception $e) {
            return $this->jsonException($e, $this->trans('translator.detect_error'));
        }
    }

    /**
     * Translate a text with the Google service.
     *
     * @Route("/google", name="ajax_google")
     * @IsGranted("ROLE_USER")
     */
    public function google(Request $request, TranslatorFactory $factory): JsonResponse
    {
        $service = $factory->getService(TranslatorFactory::GOOGLE_SERVICE);

        return $this->translator($request, $service);
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

        $lang = [];
        $domain = 'datatables';

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

            // sort
            'aria.sortAscending',
            'aria.sortDescending',

            //select
            'select.rows.0',
            'select.rows.1',
            'select.rows._',
        ];

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
        if (!$kernel->isDebug()) {
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
    public function languages(Request $request, TranslatorFactory $factory, SessionInterface $session): JsonResponse
    {
        $class = $request->get('service', TranslatorFactory::BING_SERVICE);
        $service = $factory->getService($class);
        $languages = $service->getLanguages();

        return $this->json($languages);
    }

    /**
     * Returns current conditions data for a specific location.
     *
     * @Route("/openweather/current", name="ajax_openweather_current")
     * @IsGranted("ROLE_USER")
     */
    public function openweatherCurrent(Request $request, OpenWeatherService $service): JsonResponse
    {
        try {
            //Fribourg: 2660718
            $cityId = (int) $request->get('cityId', 0);
            $units = $request->get('units', OpenWeatherService::UNIT_METRIC);
            if (false === $response = $service->current($cityId, $units)) {
                $response = $service->getLastError();
            }

            return $this->json($response);
        } catch (\Exception $e) {
            return $this->jsonException($e);
        }
    }

    /**
     * Forecaste conditions data for a specific location.
     *
     * @Route("/openweather/forecast", name="ajax_openweather_forecast")
     * @IsGranted("ROLE_USER")
     */
    public function openweatherForecast(Request $request, OpenWeatherService $service): JsonResponse
    {
        try {
            $cityId = (int) $request->get('cityId', 0);
            $count = (int) $request->get('count', -1);
            $units = $request->get('units', OpenWeatherService::UNIT_METRIC);
            if (false === $response = $service->forecast($cityId, $count, $units)) {
                $response = $service->getLastError();
            }

            return $this->json($response);
        } catch (\Exception $e) {
            return $this->jsonException($e);
        }
    }

    /**
     * Returns information for an array of cities that match the search text.
     *
     * @Route("/openweather/search", name="ajax_openweather_search")
     * @IsGranted("ROLE_USER")
     */
    public function openweatherSearch(Request $request, OpenWeatherService $service): JsonResponse
    {
        try {
            $query = (string) $request->get('query');
            $units = $request->get('units', OpenWeatherService::UNIT_METRIC);
            if (false === $response = $service->search($query, $units)) {
                return $this->json($service->getLastError());
            }

            return $this->json($response);
        } catch (\Exception $e) {
            return $this->jsonException($e);
        }
    }

    /**
     * Gets random text used to display notifications.
     *
     * @Route("/random/text", name="ajax_random_text")
     * @IsGranted("ROLE_USER")
     */
    public function randomText(Request $request, FakerService $service): JsonResponse
    {
        /** @var \Faker\Generator $faker */
        $faker = $service->getFaker();
        $text = $faker->realText(145);

        return $this->json([
            'result' => true,
            'content' => $text,
        ]);
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

//         $data = [
//             'result' => !empty($rows),
//             'query' => $zip ?? $city ?? $street ?? '',
//             'limit' => $limit,
//             'count' => \count($rows),
//             'rows' => $rows,
//         ];

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
    public function translate(Request $request, TranslatorFactory $factory, SessionInterface $session): JsonResponse
    {
        // get parameters
        $to = $request->get('to', '');
        $from = $request->get('from');
        $text = $request->get('text', '');
        $class = $request->get('service', TranslatorFactory::BING_SERVICE);
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
    public function updateCalculation(Request $request, CalculationService $service): JsonResponse
    {
        // ajax call ?
        if (!$response = $this->isAjaxCall($request)) {
            return $response;
        }

        // parameters
        $source = $request->get('calculation');
        if (null === $source) {
            return $this->json([
                'result' => false,
                'message' => 'The calculation is null.',
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
                $parameters = $this->adjustUserMargin($parameters, $minMargin);
            }
            $parameters['min_margin'] = $minMargin;

            // render table
            $body = $this->renderView('calculation/calculation_ajax_totals.html.twig', $parameters);

            // ok
            $result = [
                'result' => true,
                'body' => $body,
                'margin' => $parameters['overall_margin'],
                'overall' => $parameters['overall_total'],
                'below' => $parameters['overall_below'],
            ];

            return $this->json($result);
        } catch (\Exception $e) {
            $message = $this->trans('calculation.edit.error.update_total');

            return $this->jsonException($e, $message);
        }
    }

    /**
     * Translate a text with the Yandex service.
     *
     * @Route("/yandex", name="ajax_yandex")
     * @IsGranted("ROLE_USER")
     */
    public function yandex(Request $request, TranslatorFactory $factory): JsonResponse
    {
        $service = $factory->getService(TranslatorFactory::YANDEX_SERVICE);

        return $this->translator($request, $service);
    }

    /**
     * Adjust the user margin to have the desired overall minimum margin.
     *
     * @param array $parameters the parameters (rows) to update
     * @param float $minMargin  the desired minimum margin
     *
     * @return array the updated parameters
     */
    private function adjustUserMargin(array $parameters, float $minMargin): array
    {
        // get rows
        $groups = $parameters['groups'];
        [$groupRow] = $this->getGroup($groups, CalculationService::ROW_TOTAL_GROUP);
        [$netRow] = $this->getGroup($groups, CalculationService::ROW_TOTAL_NET);
        [$userRow, $userIndex] = $this->getGroup($groups, CalculationService::ROW_USER_MARGIN);
        [$overallRow, $overallIndex] = $this->getGroup($groups, CalculationService::ROW_OVERALL_TOTAL);

        // get values
        $groupAmount = $groupRow['amount'];
        $netTotal = $netRow['total'];
        $userMargin = $userRow['margin'];

        // compute user margin to reach minimum
        while ((($netTotal * (1 + $userMargin) / $groupAmount) - 1) < $minMargin) {
            $userMargin += 0.01;
        }

        // update
        $userRow['margin'] = $userMargin;
        $userRow['total'] = $netTotal * $userMargin;

        $overallRow['total'] = $netTotal + $userRow['total'];
        $overallRow['margin'] = ($overallRow['total'] / $groupAmount) - 1;
        $overallRow['margin_amount'] = $overallRow['total'] - $groupAmount;

        // set
        $parameters['groups'][$userIndex] = $userRow;
        $parameters['groups'][$overallIndex] = $overallRow;
        $parameters['overall_margin'] = (int) (100 * $userMargin);
        $parameters['overall_below'] = false;

        return $parameters;
    }

    private function formatExpired($date)
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
    private function getApacheVersion()
    {
        $matches = [];
        $regex = '/Apache\/(?P<version>[1-9][0-9]*\.[0-9][^\s]*)/i';

        if (\function_exists('apache_get_version')) {
            if (($version = apache_get_version()) && \preg_match($regex, $version, $matches)) {
                return $matches['version'];
            }
        }

        if (!empty($_SERVER['SERVER_SOFTWARE']) && false !== \stripos($_SERVER['SERVER_SOFTWARE'], 'apache')) {
            if (\preg_match($regex, $_SERVER['SERVER_SOFTWARE'], $matches)) {
                return $matches['version'];
            }
        }

        // if (!\function_exists('shell_exec') || \ini_get('open_basedir')) {
        //     // not possible.
        //     return false;
        // }
        // if (!($httpd_v = \shell_exec('/usr/bin/env httpd -v')) && !($httpd_v = \shell_exec('/usr/bin/env apachectl -v'))) {
        //     $locations = [
        //         '/usr/sbin/httpd',
        //         '/usr/bin/httpd',
        //         '/usr/sbin/apache2',
        //         '/usr/bin/apache2',
        //         '/usr/local/sbin/httpd',
        //         '/usr/local/bin/httpd',
        //         '/usr/local/apache/sbin/httpd',
        //         '/usr/local/apache/bin/httpd',
        //     ];
        //     foreach ($locations as $location) {
        //         if (\is_file($location)) {
        //             if (($httpd_v = \shell_exec(\escapeshellarg($location) . ' -v'))) {
        //                 break;
        //             }
        //         }
        //     }
        //     // all done here.
        //     unset($locations, $location);
        // }
        // if ($httpd_v && \preg_match($regex, $httpd_v, $matches)) {
        //     return $matches['version'];
        // }

        return false; // unable to determine.
    }

    /**
     * Gets the cache adapter class.
     */
    private function getCacheClass(): string
    {
        return $this->application->getCacheClass();
    }

    /**
     * Gets the margin, in percent, for the given category and amount.
     *
     * @param EntityManagerInterface $manager
     *                                        the entity manager
     * @param int                    $id
     *                                        the category identifier
     * @param float                  $amount
     *                                        the amount to get percent for
     *
     * @return float the margin, in percent, if found; 0 otherwise
     */
    private function getCategoryMargin(EntityManagerInterface $manager, int $id, float $amount): float
    {
        /** @var \App\Repository\CategoryMarginRepository $repository */
        $repository = $manager->getRepository(CategoryMargin::class);

        return $repository->getMargin($id, $amount);
    }

    /**
     * Finds a groups for the given identifier.
     *
     * @param array $groups the groups to search in
     * @param int   $id     the indentifier to search for,
     *
     * @return array an array where the first element is the group and the second element is the group index (key)
     */
    private function getGroup(array $groups, int $id): array
    {
        for ($i = 0, $len = \count($groups); $i < $len; ++$i) {
            if ($groups[$i]['id'] === $id) {
                return [$groups[$i], $i];
            }
        }

        return [[], -1];
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
     * Returns if the given request is an Ajax call.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|bool
     */
    private function isAjaxCall(Request $request)
    {
        // ajax call ?
        if (!$request->isXmlHttpRequest()) {
            return $this->json([
                'result' => false,
                'message' => 'Invalid Http Request.',
            ]);
        }

        return true;
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

    private function translator(Request $request, ITranslatorService $service): JsonResponse
    {
        $locale = \Locale::getDefault();
        $locale = \explode('_', $locale)[0];

        $to = $request->get('to', $locale);
        $from = $request->get('from');
        $text = $request->get('text', 'Edit the translated document.');

        if (false === $response = $service->translate($text, $to, $from)) {
            return $this->json($service->getLastError());
        }

        $result = [
            'name' => $service->getName(),
            'class' => Utils::getShortName($service),
            'api' => $service->getApiUrl(),
            'response' => $response,
            'detect' => $service->detect($text),
            'languages' => $service->getLanguages(),
        ];

        return $this->json($result);
    }
}
