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
use App\Attribute\GetPost;
use App\Interfaces\RoleInterface;
use App\Service\OpenWeatherCityUpdater;
use App\Service\OpenWeatherSearchService;
use App\Service\OpenWeatherService;
use App\Utils\FileUtils;
use Symfony\Component\Form\Extension\Core\DataTransformer\NumberToLocalizedStringTransformer;
use Symfony\Component\Form\Extension\Core\Type\SearchType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Constraints\Length;

/**
 * Controller for the OpenWeather API.
 *
 * @see https://openweathermap.org/api
 *
 * @psalm-type OpenWeatherSearchType = array{
 *     query: string,
 *     units: string,
 *     limit: int,
 *     count: int}
 *
 * @psalm-import-type OpenWeatherCityType from \App\Database\OpenWeatherDatabase
 */
#[AsController]
#[Route(path: '/openweather', name: 'openweather_')]
#[IsGranted(RoleInterface::ROLE_USER)]
class OpenWeatherController extends AbstractController
{
    /**
     * The count key name.
     */
    private const KEY_COUNT = 'count';

    /**
     * The excluded key name.
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
     * The unit's key name.
     */
    private const KEY_UNITS = 'units';

    /**
     * The prefix key for sessions.
     */
    private const PREFIX_KEY = 'openweather.';

    public function __construct(private readonly OpenWeatherService $service)
    {
    }

    /**
     * Returns the current conditions data for a specific location.
     *
     * @psalm-api
     */
    #[Get(path: '/api/current', name: 'api_current')]
    public function apiCurrent(Request $request): JsonResponse
    {
        try {
            $id = $this->getRequestId($request);
            $units = $this->getRequestUnits($request);
            $response = $this->service->current($id, $units);
            if (false === $response) {
                $response = $this->service->getLastError();
            }

            return $this->json($response);
        } catch (\Exception $e) {
            return $this->jsonException($e);
        }
    }

    /**
     * Returns 16 days / daily forecast conditions data for a specific location.
     *
     * @psalm-api
     */
    #[Get(path: '/api/daily', name: 'api_daily')]
    public function apiDaily(Request $request): JsonResponse
    {
        try {
            $cityId = $this->getRequestId($request);
            $units = $this->getRequestUnits($request);
            $count = $this->getRequestCount($request);
            $response = $this->service->daily($cityId, $count, $units);
            if (false === $response) {
                $response = $this->service->getLastError();
            }

            return $this->json($response);
        } catch (\Exception $e) {
            return $this->jsonException($e);
        }
    }

    /**
     * Returns 5-days / 3-hour forecast conditions data for a specific location.
     *
     * @psalm-api
     */
    #[Get(path: '/api/forecast', name: 'api_forecast')]
    public function apiForecast(Request $request): JsonResponse
    {
        try {
            $cityId = $this->getRequestId($request);
            $units = $this->getRequestUnits($request);
            $count = $this->getRequestCount($request);
            $response = $this->service->forecast($cityId, $count, $units);
            if (false === $response) {
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
     * @psalm-api
     */
    #[Get(path: '/api/onecall', name: 'api_onecall')]
    public function apiOneCall(Request $request): JsonResponse
    {
        try {
            $units = $this->getRequestUnits($request);
            $latitude = $this->getRequestFloat($request, self::KEY_LATITUDE);
            $longitude = $this->getRequestFloat($request, self::KEY_LONGITUDE);
            $exclude = $this->getRequestString($request, self::KEY_EXCLUDE);
            /** @psalm-var OpenWeatherService::EXCLUDE_*[] $exclude */
            $exclude = \explode(',', $exclude);
            $response = $this->service->oneCall($latitude, $longitude, $units, ...$exclude);
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
     * @psalm-api
     */
    #[Get(path: '/api/search', name: 'api_search')]
    public function apiSearch(Request $request, OpenWeatherSearchService $service): JsonResponse
    {
        try {
            $query = $this->getRequestQuery($request);
            $units = $this->getRequestUnits($request);
            $cities = $service->search($query);
            if ([] === $cities) {
                return $this->jsonFalse();
            }

            foreach ($cities as $city) {
                $parameters = [
                    self::KEY_UNITS => $units,
                    self::KEY_LATITUDE => $city[self::KEY_LATITUDE],
                    self::KEY_LONGITUDE => $city[self::KEY_LONGITUDE],
                ];
                $city['onecall_url'] = $this->generateUrl(
                    'openweather_api_onecall',
                    $parameters,
                    UrlGeneratorInterface::ABSOLUTE_URL
                );
                $parameters = [
                    self::KEY_UNITS => $units,
                    self::KEY_ID => $city['id'],
                ];
                $city['current_url'] = $this->generateUrl(
                    'openweather_api_current',
                    $parameters,
                    UrlGeneratorInterface::ABSOLUTE_URL
                );
                $city['forecast_url'] = $this->generateUrl(
                    'openweather_api_forecast',
                    $parameters,
                    UrlGeneratorInterface::ABSOLUTE_URL
                );
                $city['daily_url'] = $this->generateUrl(
                    'openweather_api_daily',
                    $parameters,
                    UrlGeneratorInterface::ABSOLUTE_URL
                );
            }

            return $this->json($cities);
        } catch (\Exception $e) {
            return $this->jsonException($e);
        }
    }

    /**
     * Returns the current conditions data for a specific location.
     */
    #[Get(path: '/current', name: 'current')]
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

        return $this->render('openweather/weather.htm.twig', $values);
    }

    /**
     * Import cities.
     *
     * Data can be downloaded from <a href="https://bulk.openweathermap.org/sample/">sample directory</a>.
     */
    #[IsGranted(RoleInterface::ROLE_ADMIN)]
    #[GetPost(path: '/import', name: 'import')]
    public function import(Request $request, OpenWeatherCityUpdater $updater): Response
    {
        $form = $updater->createForm();
        if ($this->handleRequestForm($request, $form)) {
            /** @var UploadedFile $file */
            $file = $form->get('file')->getData();
            $results = $updater->import($file);
            FileUtils::remove($file);

            return $this->render('openweather/import_result.html.twig', $results);
        }

        return $this->render('openweather/import_query.html.twig', ['form' => $form]);
    }

    /**
     * Shows the search city view.
     */
    #[GetPost(path: '/search', name: 'search')]
    public function search(Request $request, OpenWeatherSearchService $service): Response
    {
        $data = [
            self::KEY_QUERY => $this->getSessionQuery($request),
            self::KEY_UNITS => $this->getSessionUnits($request),
            self::KEY_LIMIT => $this->getSessionLimit($request),
            self::KEY_COUNT => $this->getSessionCount($request),
        ];
        $form = $this->createSearchForm($data);
        if ($this->handleRequestForm($request, $form)) {
            /** @psalm-var OpenWeatherSearchType $data */
            $data = $form->getData();
            $query = $data[self::KEY_QUERY];
            $units = $data[self::KEY_UNITS];
            $limit = $data[self::KEY_LIMIT];
            $count = $data[self::KEY_COUNT];
            $cities = $service->search($query, $limit);
            if ([] !== $cities) {
                // save
                $this->setSessionValues([
                    self::KEY_QUERY => $query,
                    self::KEY_UNITS => $units,
                    self::KEY_LIMIT => $limit,
                    self::KEY_COUNT => $count,
                ]);
                if (1 === \count($cities)) {
                    return $this->redirectToRoute('openweather_current', [
                        self::KEY_ID => \reset($cities)['id'],
                        self::KEY_UNITS => $units,
                        self::KEY_COUNT => $count,
                    ]);
                }
                /** @psalm-var array{units: array, list: array<int, mixed>}|null $group */
                $group = null;
                $cityIds = \array_map(fn (array $city): int => $city['id'], $cities);
                foreach (\array_keys($cities) as $index) {
                    // load current weather by chunks of 20 items
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

            return $this->render('openweather/search_city.html.twig', [
                'form' => $form,
                'cities' => $cities,
                'units' => $units,
                'count' => $count,
            ]);
        }

        return $this->render('openweather/search_city.html.twig', [
            'form' => $form,
            'count' => $data[self::KEY_COUNT],
        ]);
    }

    /**
     * Shows the current weather, if applicable, the search cities otherwise.
     */
    #[Get(path: '', name: 'weather')]
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

    #[\Override]
    protected function getSessionKey(string $key): string
    {
        return self::PREFIX_KEY . $key;
    }

    /**
     * @psalm-param OpenWeatherSearchType $data
     *
     * @psalm-return FormInterface<mixed>
     */
    private function createSearchForm(array $data): FormInterface
    {
        $helper = $this->createFormHelper('openweather.search.', $data);
        $helper->field(self::KEY_QUERY)
            ->constraints(new Length(['min' => 2]))
            ->updateAttributes(['placeholder' => 'openweather.search.place_holder', 'minlength' => 2])
            ->add(SearchType::class);
        $helper->field(self::KEY_UNITS)
            ->addChoiceType([
                'openweather.current.metric.text' => OpenWeatherService::UNIT_METRIC,
                'openweather.current.imperial.text' => OpenWeatherService::UNIT_IMPERIAL,
            ]);
        $transformer = new NumberToLocalizedStringTransformer(0);
        $helper->field(self::KEY_LIMIT)
            ->modelTransformer($transformer)
            ->addHiddenType();
        $helper->field(self::KEY_COUNT)
            ->modelTransformer($transformer)
            ->addHiddenType();

        return $helper->createForm();
    }

    private function getRequestCount(Request $request): int
    {
        return $this->getRequestInt($request, self::KEY_COUNT, OpenWeatherService::DEFAULT_COUNT);
    }

    private function getRequestId(Request $request): int
    {
        return $this->getRequestInt($request, self::KEY_ID);
    }

    private function getRequestLimit(Request $request): int
    {
        return $this->getRequestInt($request, self::KEY_LIMIT, OpenWeatherService::DEFAULT_LIMIT);
    }

    private function getRequestQuery(Request $request): string
    {
        return \trim($this->getRequestString($request, self::KEY_QUERY));
    }

    private function getRequestUnits(Request $request): string
    {
        return $this->getRequestString($request, self::KEY_UNITS, OpenWeatherService::UNIT_METRIC);
    }

    private function getSessionCount(Request $request): int
    {
        return $this->getSessionInt(self::KEY_COUNT, $this->getRequestCount($request));
    }

    private function getSessionId(Request $request): int
    {
        return $this->getSessionInt(self::KEY_ID, $this->getRequestId($request));
    }

    private function getSessionLimit(Request $request): int
    {
        return $this->getSessionInt(self::KEY_LIMIT, $this->getRequestLimit($request));
    }

    private function getSessionQuery(Request $request): string
    {
        return $this->getSessionString(self::KEY_QUERY, $this->getRequestQuery($request));
    }

    private function getSessionUnits(Request $request): string
    {
        return $this->getSessionString(self::KEY_UNITS, $this->getRequestUnits($request));
    }
}
