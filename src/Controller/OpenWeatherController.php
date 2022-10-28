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

use App\Interfaces\RoleInterface;
use App\Service\OpenWeatherCityUpdater;
use App\Service\OpenWeatherService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\Form\Extension\Core\Type\SearchType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Validator\Constraints\Length;

/**
 * Controller for the OpenWeather API.
 *
 * @see https://openweathermap.org/api
 */
#[AsController]
#[Route(path: '/openweather')]
#[IsGranted(RoleInterface::ROLE_USER)]
class OpenWeatherController extends AbstractController
{
    /**
     * The number of daily results to return.
     */
    private const DEFAULT_COUNT = 5;

    /**
     * The number of search results to return.
     */
    private const DEFAULT_LIMIT = 25;

    /**
     * The count key name.
     */
    private const KEY_COUNT = 'count';

    /**
     * The exclude key name.
     */
    private const KEY_EXCLUDE = 'exclude';

    /**
     * The city identifier key name.
     */
    private const KEY_ID = 'id';

    /**
     * The latitude key name.
     */
    private const KEY_LATITUDE = 'latitude';

    /**
     * The limit key name.
     */
    private const KEY_LIMIT = 'limit';

    /**
     * The latitude key name.
     */
    private const KEY_LONGITUDE = 'longitude';

    /**
     * The query key name.
     */
    private const KEY_QUERY = 'query';

    /**
     * The units key name.
     */
    private const KEY_UNITS = 'units';

    /**
     * the prefix key for sessions.
     */
    private const PREFIX_KEY = 'openweather.';

    /**
     * Constructor.
     */
    public function __construct(private readonly OpenWeatherService $service)
    {
    }

    /**
     * Returns current conditions data for a specific location.
     *
     * @throws \Psr\Cache\InvalidArgumentException
     * @throws \ReflectionException
     * @throws \Symfony\Contracts\HttpClient\Exception\ExceptionInterface
     */
    #[Route(path: '/api/current', name: 'openweather_api_current')]
    public function apiCurrent(Request $request): JsonResponse
    {
        try {
            $id = $this->getRequestId($request);
            $units = $this->getRequestUnits($request);
            if (false === $response = $this->service->current($id, $units)) {
                $response = $this->service->getLastError();
            }

            return $this->json($response);
        } catch (\Exception $e) {
            return $this->jsonException($e);
        }
    }

    /**
     * Returns 16 day / daily forecast conditions data for a specific location.
     *
     * @throws \Psr\Cache\InvalidArgumentException
     * @throws \ReflectionException
     * @throws \Symfony\Contracts\HttpClient\Exception\ExceptionInterface
     */
    #[Route(path: '/api/daily', name: 'openweather_api_daily')]
    public function apiDaily(Request $request): JsonResponse
    {
        try {
            $cityId = $this->getRequestId($request);
            $units = $this->getRequestUnits($request);
            $count = $this->getRequestCount($request);
            if (false === $response = $this->service->daily($cityId, $count, $units)) {
                $response = $this->service->getLastError();
            }

            return $this->json($response);
        } catch (\Exception $e) {
            return $this->jsonException($e);
        }
    }

    /**
     * Returns 5 days / 3 hours forecast conditions data for a specific location.
     *
     * @throws \ReflectionException
     * @throws \Symfony\Contracts\HttpClient\Exception\ExceptionInterface
     * @throws \ReflectionException
     * @throws \Psr\Cache\InvalidArgumentException
     */
    #[Route(path: '/api/forecast', name: 'openweather_api_forecast')]
    public function apiForecast(Request $request): JsonResponse
    {
        try {
            $cityId = $this->getRequestId($request);
            $units = $this->getRequestUnits($request);
            $count = $this->getRequestCount($request);
            if (false === $response = $this->service->forecast($cityId, $count, $units)) {
                $response = $this->service->getLastError();
            }

            return $this->json($response);
        } catch (\Exception $e) {
            return $this->jsonException($e);
        }
    }

    /**
     * Returns all essential weather data for a specific location.
     *
     * @throws \ReflectionException
     * @throws \Symfony\Contracts\HttpClient\Exception\ExceptionInterface
     * @throws \ReflectionException
     * @throws \Psr\Cache\InvalidArgumentException
     */
    #[Route(path: '/api/onecall', name: 'openweather_api_onecall')]
    public function apiOneCall(Request $request): JsonResponse
    {
        try {
            $units = $this->getRequestUnits($request);
            $latitude = $this->getRequestFloat($request, self::KEY_LATITUDE);
            $longitude = $this->getRequestFloat($request, self::KEY_LONGITUDE);
            $exclude = (string) $this->getRequestString($request, self::KEY_EXCLUDE);
            $exclude = \explode(',', $exclude);
            $response = $this->service->oneCall($latitude, $longitude, $units, $exclude);
            if (false === $response) {
                return $this->json($this->service->getLastError());
            }

            return $this->json($response);
        } catch (\Exception $e) {
            return $this->jsonException($e);
        }
    }

    /**
     * Returns an array of cities that match the query text.
     *
     * @throws \ReflectionException
     * @throws \Psr\Cache\InvalidArgumentException
     */
    #[Route(path: '/api/search', name: 'openweather_api_search')]
    public function apiSearch(Request $request, UrlGeneratorInterface $generator): JsonResponse
    {
        try {
            $query = $this->getRequestQuery($request);
            $units = $this->getRequestUnits($request);
            $cities = $this->service->search($query, $units);
            if ($lastError = $this->service->getLastError()) {
                return $this->json($lastError);
            }
            if (empty($cities)) {
                return $this->jsonFalse();
            }

            /**  @psalm-var array{id: int, latitude: float, longitude: float} $city */
            foreach ($cities as $city) {
                $parameters = [
                    self::KEY_UNITS => $units,
                    self::KEY_LATITUDE => $city[self::KEY_LATITUDE],
                    self::KEY_LONGITUDE => $city[self::KEY_LONGITUDE],
                ];
                $city['onecall_url'] = $generator->generate('openweather_api_onecall', $parameters, UrlGeneratorInterface::ABSOLUTE_URL);

                $parameters = [
                    self::KEY_UNITS => $units,
                    self::KEY_ID => $city['id'],
                ];
                $city['current_url'] = $generator->generate('openweather_api_current', $parameters, UrlGeneratorInterface::ABSOLUTE_URL);
                $city['forecast_url'] = $generator->generate('openweather_api_forecast', $parameters, UrlGeneratorInterface::ABSOLUTE_URL);
                $city['daily_url'] = $generator->generate('openweather_api_daily', $parameters, UrlGeneratorInterface::ABSOLUTE_URL);
            }

            return $this->json($cities);
        } catch (\Exception $e) {
            return $this->jsonException($e);
        }
    }

    /**
     * Returns current conditions data for a specific location.
     *
     * @throws \Psr\Cache\InvalidArgumentException
     * @throws \Symfony\Contracts\HttpClient\Exception\ExceptionInterface
     * @throws \ReflectionException
     */
    #[Route(path: '/current', name: 'openweather_current')]
    public function current(Request $request): Response
    {
        $id = $this->getRequestId($request);
        $units = $this->getRequestUnits($request);
        $count = $this->getRequestCount($request);
        $values = $this->service->all($id, $count, $units);
        if (false !== $values['current']) {
            $this->setSessionValues([
                self::KEY_ID => $id,
                self::KEY_UNITS => $units,
                self::KEY_COUNT => $count,
            ]);
        }
        $values['count'] = $count;
        $values['api_url'] = 'https://openweathermap.org/api';

        return $this->renderForm('openweather/weather.htm.twig', $values);
    }

    /**
     * Import cities.
     *
     * Data can be downloaded from the <a href="https://bulk.openweathermap.org/sample/">sample directory</a>.
     */
    #[IsGranted(RoleInterface::ROLE_ADMIN)]
    #[Route(path: '/import', name: 'openweather_import')]
    public function import(Request $request, OpenWeatherCityUpdater $updater): Response
    {
        $form = $updater->createForm();
        if ($this->handleRequestForm($request, $form)) {
            /** @var UploadedFile $file */
            $file = $form['file']->getData();
            $results = $updater->import($file);

            return $this->renderForm('openweather/import_result.html.twig', $results);
        }

        return $this->renderForm('openweather/import_file.html.twig', [
            'sample_url' => 'https://bulk.openweathermap.org/sample/',
            'site_url' => 'https://openweathermap.org/',
            'form' => $form,
        ]);
    }

    /**
     * Shows the search city view.
     *
     * @throws \Psr\Cache\InvalidArgumentException
     * @throws \Symfony\Contracts\HttpClient\Exception\ExceptionInterface
     * @throws \ReflectionException
     */
    #[Route(path: '/search', name: 'openweather_search')]
    public function search(Request $request): Response
    {
        $data = [
            self::KEY_QUERY => $this->getSessionQuery($request),
            self::KEY_UNITS => $this->getSessionUnits($request),
            self::KEY_LIMIT => $this->getSessionLimit($request),
            self::KEY_COUNT => $this->getSessionCount($request),
        ];
        $form = $this->createSearchForm($data);

        if ($this->handleRequestForm($request, $form)) {
            /** @var array{query: string, units: string, limit: int, count: int} $data */
            $data = $form->getData();
            $query = $data[self::KEY_QUERY];
            $units = $data[self::KEY_UNITS];
            $limit = $data[self::KEY_LIMIT];
            $count = $data[self::KEY_COUNT];

            /** @var array<int, array{id: int}>|false $cities */
            $cities = $this->service->search($query, $units, $limit);
            if (false !== $cities) {
                // save
                $this->setSessionValues([
                    self::KEY_QUERY => $query,
                    self::KEY_UNITS => $units,
                    self::KEY_LIMIT => $limit,
                    self::KEY_COUNT => $count,
                ]);

                // display current weather if only 1 city is found
                if (1 === \count($cities)) {
                    return $this->redirectToRoute('openweather_current', [
                        self::KEY_ID => \reset($cities)['id'],
                        self::KEY_UNITS => $units,
                        self::KEY_COUNT => $count,
                    ]);
                }

                /** @var array{units: array, list: array<int, mixed>}|null $group */
                $group = null;
                $cityIds = \array_map(fn (array $city): int => $city['id'], $cities);
                foreach (\array_keys($cities) as $index) {
                    // load current weather by chunk of 20 items
                    if (0 === $index % OpenWeatherService::MAX_GROUP) {
                        $ids = \array_splice($cityIds, 0, OpenWeatherService::MAX_GROUP);
                        $group = $this->service->group($ids, $units);
                    }
                    if (\is_array($group)) {
                        $cities[$index]['units'] = $group['units'];
                        $cities[$index]['current'] = (array) $group['list'][$index % 20];
                    }
                }
            }

            return $this->renderForm('openweather/search_city.html.twig', [
                'form' => $form,
                'cities' => $cities,
                'units' => $units,
                'count' => $count,
            ]);
        }

        return $this->renderForm('openweather/search_city.html.twig', [
            'form' => $form,
            'count' => $data[self::KEY_COUNT],
        ]);
    }

    /**
     * Shows the current weather, if applicable, the search cities otherwise.
     */
    #[Route(path: '/weather', name: 'openweather_weather')]
    public function weather(Request $request): Response
    {
        $id = $this->getSessionId($request);
        if (0 !== $id) {
            return $this->redirectToRoute('openweather_current', [
                self::KEY_ID => $id,
                self::KEY_UNITS => $this->getSessionUnits($request),
                self::KEY_COUNT => $this->getSessionCount($request),
            ]);
        }

        return $this->redirectToRoute('openweather_search');
    }

    protected function getSessionKey(string $key): string
    {
        return self::PREFIX_KEY . $key;
    }

    private function createSearchForm(array $data): FormInterface
    {
        $helper = $this->createFormHelper('openweather.search.', $data);
        $helper->field(self::KEY_QUERY)
            ->constraints(new Length(['min' => 2]))
            ->updateAttributes(['placeholder' => 'openweather.search.place_holder', 'minlength' => 2])
            ->add(SearchType::class);
        $helper->field(self::KEY_UNITS)
            ->updateOption('choice_translation_domain', false)
            ->addChoiceType([
                OpenWeatherService::DEGREE_METRIC => OpenWeatherService::UNIT_METRIC,
                OpenWeatherService::DEGREE_IMPERIAL => OpenWeatherService::UNIT_IMPERIAL,
            ]);
        $limits = [5, 10, 15, 25, 50];
        $helper->field(self::KEY_LIMIT)
            ->updateOption('choice_translation_domain', false)
            ->addChoiceType(\array_combine($limits, $limits));
        $helper->field(self::KEY_COUNT)
            ->addHiddenType();

        return $helper->createForm();
    }

    private function getRequestCount(Request $request): int
    {
        return $this->getRequestInt($request, self::KEY_COUNT, self::DEFAULT_COUNT);
    }

    private function getRequestId(Request $request): int
    {
        return $this->getRequestInt($request, self::KEY_ID);
    }

    private function getRequestLimit(Request $request): int
    {
        return $this->getRequestInt($request, self::KEY_LIMIT, self::DEFAULT_LIMIT);
    }

    private function getRequestQuery(Request $request): string
    {
        return \trim((string) $this->getRequestString($request, self::KEY_QUERY, ''));
    }

    private function getRequestUnits(Request $request): string
    {
        return (string) $this->getRequestString($request, self::KEY_UNITS, OpenWeatherService::UNIT_METRIC);
    }

    private function getSessionCount(Request $request): int
    {
        return (int) $this->getSessionInt(self::KEY_COUNT, $this->getRequestCount($request));
    }

    private function getSessionId(Request $request): int
    {
        return (int) $this->getSessionInt(self::KEY_ID, $this->getRequestId($request));
    }

    private function getSessionLimit(Request $request): int
    {
        return (int) $this->getSessionInt(self::KEY_LIMIT, $this->getRequestLimit($request));
    }

    private function getSessionQuery(Request $request): string
    {
        return (string) $this->getSessionString(self::KEY_QUERY, $this->getRequestQuery($request));
    }

    private function getSessionUnits(Request $request): string
    {
        return (string) $this->getSessionString(self::KEY_UNITS, $this->getRequestUnits($request));
    }
}
