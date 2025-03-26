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
use App\Enums\OpenWeatherUnits;
use App\Interfaces\RoleInterface;
use App\Service\OpenWeatherCityUpdater;
use App\Service\OpenWeatherSearchService;
use App\Service\OpenWeatherService;
use App\Traits\CookieTrait;
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
 * Controller for the OpenWeatherMap API.
 *
 * @see https://openweathermap.org/api
 *
 * @psalm-type OpenWeatherSearchType = array{
 *     query: string,
 *     units: OpenWeatherUnits,
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
    use CookieTrait;

    /**
     * The prefix key for sessions.
     */
    private const PREFIX_KEY = 'openweather';

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
            $latitude = $this->getRequestFloat($request, OpenWeatherService::PARAM_LATITUDE);
            $longitude = $this->getRequestFloat($request, OpenWeatherService::PARAM_LONGITUDE);
            $exclude = $this->getRequestString($request, OpenWeatherService::PARAM_EXCLUDE);
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
                    OpenWeatherService::PARAM_UNITS => $units,
                    OpenWeatherService::PARAM_LATITUDE => $city['latitude'],
                    OpenWeatherService::PARAM_LONGITUDE => $city['longitude'],
                ];
                $city['onecall_url'] = $this->generateUrl(
                    'openweather_api_onecall',
                    $parameters,
                    UrlGeneratorInterface::ABSOLUTE_URL
                );
                $parameters = [
                    OpenWeatherService::PARAM_UNITS => $units,
                    OpenWeatherService::PARAM_ID => $city['id'],
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
        $values['count'] = $count;
        $values['api_url'] = 'https://openweathermap.org/api';
        $response = $this->render('openweather/weather.htm.twig', $values);

        if (false !== $values['current']) {
            $values = [
                OpenWeatherService::PARAM_ID => $id,
                OpenWeatherService::PARAM_UNITS => $units,
                OpenWeatherService::PARAM_COUNT => $count,
            ];
            $this->updateCookies(
                $response,
                $values,
                self::PREFIX_KEY
            );
        }

        return $response;
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
            OpenWeatherService::PARAM_QUERY => $this->getCookieQuery($request),
            OpenWeatherService::PARAM_UNITS => $this->getCookieUnits($request),
            OpenWeatherService::PARAM_LIMIT => $this->getCookieLimit($request),
            OpenWeatherService::PARAM_COUNT => $this->getCookieCount($request),
        ];
        $form = $this->createSearchForm($data);
        if ($this->handleRequestForm($request, $form)) {
            /** @psalm-var OpenWeatherSearchType $data */
            $data = $form->getData();
            $query = $data[OpenWeatherService::PARAM_QUERY];
            $units = $data[OpenWeatherService::PARAM_UNITS];
            $limit = $data[OpenWeatherService::PARAM_LIMIT];
            $count = $data[OpenWeatherService::PARAM_COUNT];
            $cities = $service->search($query, $limit);

            // found?
            if ([] !== $cities) {
                // only one?
                if (1 === \count($cities)) {
                    $response = $this->redirectToRoute('openweather_current', [
                        OpenWeatherService::PARAM_ID => \reset($cities)['id'],
                        OpenWeatherService::PARAM_UNITS => $units,
                        OpenWeatherService::PARAM_COUNT => $count,
                    ]);
                    $values = [
                        OpenWeatherService::PARAM_QUERY => $query,
                        OpenWeatherService::PARAM_UNITS => $units,
                        OpenWeatherService::PARAM_LIMIT => $limit,
                        OpenWeatherService::PARAM_COUNT => $count,
                    ];
                    $this->updateCookies(
                        $response,
                        $values,
                        self::PREFIX_KEY
                    );

                    return $response;
                }

                $cities = $this->updateCities($cities, $units);
            }

            $response = $this->render('openweather/search_city.html.twig', [
                'form' => $form,
                'cities' => $cities,
                'units' => $units,
                'count' => $count,
            ]);
            $this->updateCookie(
                $response,
                OpenWeatherService::PARAM_QUERY,
                $query,
                self::PREFIX_KEY
            );

            return $response;
        }

        return $this->render('openweather/search_city.html.twig', [
            'form' => $form,
            'count' => $data[OpenWeatherService::PARAM_COUNT],
        ]);
    }

    /**
     * Shows the current weather, if applicable, the search cities otherwise.
     */
    #[Get(path: '', name: 'weather')]
    public function weather(Request $request): Response
    {
        $id = $this->getCookieId($request);
        if (0 !== $id) {
            return $this->redirectToRoute('openweather_current', [
                OpenWeatherService::PARAM_ID => $id,
                OpenWeatherService::PARAM_UNITS => $this->getCookieUnits($request),
                OpenWeatherService::PARAM_COUNT => $this->getCookieCount($request),
            ]);
        }

        return $this->redirectToRoute('openweather_search');
    }

    /**
     * @psalm-param OpenWeatherSearchType $data
     *
     * @psalm-return FormInterface<mixed>
     */
    private function createSearchForm(array $data): FormInterface
    {
        $helper = $this->createFormHelper('openweather.search.', $data);
        $helper->field(OpenWeatherService::PARAM_QUERY)
            ->constraints(new Length(['min' => 2]))
            ->updateAttributes(['placeholder' => 'openweather.search.place_holder', 'minlength' => 2])
            ->add(SearchType::class);
        $helper->field(OpenWeatherService::PARAM_UNITS)
            ->addEnumType(OpenWeatherUnits::class);
        $transformer = new NumberToLocalizedStringTransformer(0);
        $helper->field(OpenWeatherService::PARAM_LIMIT)
            ->modelTransformer($transformer)
            ->addHiddenType();
        $helper->field(OpenWeatherService::PARAM_COUNT)
            ->modelTransformer($transformer)
            ->addHiddenType();

        return $helper->createForm();
    }

    private function getCookieCount(Request $request): int
    {
        return $this->getCookieInt(
            $request,
            OpenWeatherService::PARAM_COUNT,
            $this->getRequestCount($request),
            self::PREFIX_KEY
        );
    }

    private function getCookieId(Request $request): int
    {
        return $this->getCookieInt(
            $request,
            OpenWeatherService::PARAM_ID,
            $this->getRequestId($request),
            self::PREFIX_KEY
        );
    }

    private function getCookieLimit(Request $request): int
    {
        return $this->getCookieInt(
            $request,
            OpenWeatherService::PARAM_LIMIT,
            $this->getRequestLimit($request),
            self::PREFIX_KEY
        );
    }

    private function getCookieQuery(Request $request): string
    {
        return $this->getCookieString(
            $request,
            OpenWeatherService::PARAM_QUERY,
            $this->getRequestQuery($request),
            self::PREFIX_KEY
        );
    }

    private function getCookieUnits(Request $request): OpenWeatherUnits
    {
        return $this->getCookieEnum(
            $request,
            OpenWeatherService::PARAM_UNITS,
            $this->getRequestUnits($request),
            self::PREFIX_KEY
        );
    }

    private function getRequestCount(Request $request): int
    {
        return $this->getRequestInt($request, OpenWeatherService::PARAM_COUNT, OpenWeatherService::DEFAULT_COUNT);
    }

    private function getRequestId(Request $request): int
    {
        return $this->getRequestInt($request, OpenWeatherService::PARAM_ID);
    }

    private function getRequestLimit(Request $request): int
    {
        return $this->getRequestInt($request, OpenWeatherService::PARAM_LIMIT, OpenWeatherService::DEFAULT_LIMIT);
    }

    private function getRequestQuery(Request $request): string
    {
        return \trim($this->getRequestString($request, OpenWeatherService::PARAM_QUERY));
    }

    private function getRequestUnits(Request $request): OpenWeatherUnits
    {
        return $this->getRequestEnum($request, OpenWeatherService::PARAM_UNITS, OpenWeatherUnits::getDefault());
    }

    /**
     * @psalm-param array<int, OpenWeatherCityType> $cities
     */
    private function updateCities(array $cities, OpenWeatherUnits $units): array
    {
        $maxGroup = OpenWeatherService::MAX_GROUP;
        $cityIds = \array_map(fn (array $city): int => $city['id'], $cities);
        for ($i = 0, $len = \count($cityIds); $i < $len; $i += $maxGroup) {
            $ids = \array_slice($cityIds, $i, $maxGroup);
            $group = $this->service->group($ids, $units);
            if (!\is_array($group)) {
                continue;
            }
            $groupUnits = $group['units'];
            for ($index = $i, $min = \min($len, $i + $maxGroup); $index < $min; ++$index) {
                $cities[$index]['current'] = $group['list'][$index % $maxGroup];
                $cities[$index]['units'] = $groupUnits;
            }
        }

        return $cities;
    }
}
