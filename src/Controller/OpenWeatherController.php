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

use App\Database\OpenWeatherDatabase;
use App\Service\OpenWeatherService;
use App\Util\FileUtils;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Form\Extension\Core\Type\SearchType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Length;

/**
 * Controller for the OpenWeather API.
 *
 * @author Laurent Muller
 *
 * @see https://openweathermap.org/api
 *
 * @Route("/openweather")
 * @IsGranted("ROLE_USER")
 */
class OpenWeatherController extends AbstractController
{
    /**
     * The city identifier key name.
     */
    private const KEY_CITY_ID = 'cityId';

    /**
     * The count key name.
     */
    private const KEY_COUNT = 'count';

    /**
     * The limit key name.
     */
    private const KEY_LIMIT = 'limit';

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

    /*
     * the service
     */
    private OpenWeatherService $service;

    /**
     * Constructor.
     */
    public function __construct(OpenWeatherService $service)
    {
        $this->service = $service;
    }

    /**
     * Returns current conditions data for a specific location.
     *
     * @Route("/api/current", name="openweather_api_current")
     */
    public function apiCurrent(Request $request): JsonResponse
    {
        try {
            $cityId = $this->getRequestCityId($request);
            $units = $this->getRequestUnits($request);

            if (false === $response = $this->service->current($cityId, $units)) {
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
     * @Route("/api/daily", name="openweather_api_daily")
     */
    public function apiDaily(Request $request): JsonResponse
    {
        try {
            $cityId = $this->getRequestCityId($request);
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
     * Returns 5 day / 3 hour forecast conditions data for a specific location.
     *
     * @Route("/api/forecast", name="openweather_api_forecast")
     */
    public function apiForecast(Request $request): JsonResponse
    {
        try {
            $cityId = $this->getRequestCityId($request);
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
     * @Route("/api/onecall", name="openweather_api_onecall")
     */
    public function apiOnecall(Request $request): JsonResponse
    {
        try {
            $units = $this->getRequestUnits($request);
            $latitude = (float) $request->get('latitude', 0.0);
            $longitude = (float) $request->get('longitude', 0.0);
            $exclude = (array) $request->get('exclude', []);

            if (false === $response = $this->service->onecall($latitude, $longitude, $units, $exclude)) {
                $response = $this->service->getLastError();
            }

            return $this->json($response);
        } catch (\Exception $e) {
            return $this->jsonException($e);
        }
    }

    /**
     * Returns an array of cities that match the query text.
     *
     * @Route("/api/search", name="openweather_api_search")
     */
    public function apiSearch(Request $request, UrlGeneratorInterface $generator): JsonResponse
    {
        try {
            $query = $this->getRequestQuery($request);
            $units = $this->getRequestUnits($request);
            if (false === $response = $this->service->search($query, $units)) {
                return $this->json($this->service->getLastError());
            }

            // add urls
            $parameters = ['units' => $units];
            foreach ((array) $response as &$city) {
                $parameters['latitude'] = $city['latitude'];
                $parameters['longitude'] = $city['longitude'];
                $city['onecall_url'] = $generator->generate('openweather_api_onecall', $parameters, UrlGeneratorInterface::ABSOLUTE_URL);
                unset($parameters['latitude'], $parameters['longitude']);

                $parameters['cityId'] = $city['id'];
                $city['current_url'] = $generator->generate('openweather_api_current', $parameters, UrlGeneratorInterface::ABSOLUTE_URL);
                $city['forecast_url'] = $generator->generate('openweather_api_forecast', $parameters, UrlGeneratorInterface::ABSOLUTE_URL);
                $city['daily_url'] = $generator->generate('openweather_api_daily', $parameters, UrlGeneratorInterface::ABSOLUTE_URL);

                unset($parameters['cityId']);
            }

            return $this->json($response);
        } catch (\Exception $e) {
            return $this->jsonException($e);
        }
    }

    /**
     * Returns current conditions data for a specific location.
     *
     * @Route("/current", name="openweather_current")
     */
    public function current(Request $request): Response
    {
        $cityId = $this->getRequestCityId($request);
        $units = $this->getRequestUnits($request);
        $count = $this->getRequestCount($request);

        $current = $this->service->current($cityId, $units);
        $forecast = $this->service->forecast($cityId, $count, $units);
        $daily = $this->service->daily($cityId, $count, $units);

        if (false !== $current) {
            $this->setSessionValue(self::KEY_CITY_ID, $cityId);
            $this->setSessionValue(self::KEY_UNITS, $units);
            $this->setSessionValue(self::KEY_COUNT, $count);
        }

        return $this->render('openweather/current_weather.htm.twig', [
            'current' => $current,
            'forecast' => $forecast,
            'daily' => $daily,
            'count' => $count,
        ]);
    }

    /**
     * Import cities.
     *
     * Data can be dowloaded from the <a href="http://bulk.openweathermap.org/sample/">sample directory</a>.
     *
     * @Route("/import", name="openweather_import")
     * @IsGranted("ROLE_ADMIN")
     */
    public function import(Request $request, OpenWeatherService $service): Response
    {
        // create form
        $form = $this->createImportForm();

        // handle request
        if ($this->handleRequestForm($request, $form)) {
            $db = null;
            $file = null;
            $temp_name = null;

            try {
                // get temp file
                if (!$temp_name = FileUtils::tempfile('sql')) {
                    return $this->renderErrorResult('import.error.temp_file');
                }

                /** @var UploadedFile $file */
                $file = $form['file']->getData();
                $name = $file->getClientOriginalName();

                // get content
                if (!$content = $this->getGzContent($file)) {
                    return $this->renderErrorResult('import.error.open_archive', ['name' => $name]);
                }

                // decode content
                if (!$cities = $this->getJsonContent($content)) {
                    return $this->renderErrorResult('import.error.empty_archive', ['name' => $name]);
                }

                // create database
                $db = new OpenWeatherDatabase($temp_name);
                $db->beginTransaction();

                // insert cities
                $valids = 0;
                $errors = 0;
                foreach ($cities as $city) {
                    if (!$db->insertCity($city['id'], $city['name'], $city['country'], $city['coord']['lat'], $city['coord']['lon'])) {
                        ++$errors;
                    } elseif (0 === ++$valids % 50000) {
                        $db->commitTransaction();
                        $db->beginTransaction();
                    }
                }

                //close
                $db->commitTransaction();
                $db->compact();
                $db->close();

                // cities?
                if (0 === $valids) {
                    return $this->renderErrorResult('openweather.error.empty_city');
                }

                // move
                $target = $service->getDatabaseName();

                try {
                    if (!FileUtils::rename($temp_name, $target)) {
                        return $this->renderErrorResult('openweather.error.move_database');
                    }
                } catch (IOException $e) {
                    return $this->renderErrorResult('openweather.error.move_database');
                }

                // ok
                return $this->render('openweather/import_result.html.twig', [
                    'valids' => $valids,
                    'errors' => $errors,
                    'result' => true,
                ]);
            } finally {
                if (null !== $db) {
                    $db->close();
                }
                FileUtils::remove($temp_name);
                FileUtils::remove($file);
            }
        }

        // display form
        return $this->render('openweather/import_file.html.twig', [
            'sample' => 'http://bulk.openweathermap.org/sample/',
            'openweathermap' => 'https://openweathermap.org/',
            'form' => $form->createView(),
        ]);
    }

    /**
     * Shows the search city view.
     *
     * @Route("/search", name="openweather_search")
     */
    public function search(Request $request): Response
    {
        // get session data
        $data = [
            self::KEY_QUERY => $this->getSessionQuery($request),
            self::KEY_UNITS => $this->getSessionUnits($request),
            self::KEY_LIMIT => $this->getSessionLimit($request),
            self::KEY_COUNT => $this->getSessionCount($request),
        ];

        // create form
        $helper = $this->createFormHelper('openweather.search.', $data);

        $helper->field(self::KEY_QUERY)
            ->updateOption('constraints', new Length(['min' => 2]))
            ->updateAttribute('placeholder', 'openweather.search.place_holder')
            ->updateAttribute('minlength', 2)
            ->add(SearchType::class);

        $helper->field(self::KEY_UNITS)
            ->updateOption('choice_translation_domain', false)
            ->addChoiceType([
                OpenWeatherService::DEGREE_METRIC => OpenWeatherService::UNIT_METRIC,
                OpenWeatherService::DEGREE_IMPERIAL => OpenWeatherService::UNIT_IMPERIAL,
            ]);

        $helper->field(self::KEY_LIMIT)
            ->updateOption('choice_translation_domain', false)
            ->addChoiceType([
                10 => 10,
                15 => 15,
                25 => 25,
                50 => 50,
                100 => 100,
            ]);

        $helper->field(self::KEY_COUNT)
            ->addHiddenType();

        // handle request
        $form = $helper->createForm();
        if ($this->handleRequestForm($request, $form)) {
            // get values
            $query = (string) $form->get('query')->getData();
            $units = (string) $form->get('units')->getData();
            $limit = (int) $form->get('limit')->getData();
            $count = (int) $form->get('count')->getData();

            // search
            /** @var array $cities */
            $cities = $this->service->search($query, $units, $limit);

            // get identifers
            $cityIds = \array_map(function (array $city) {
                return $city['id'];
            }, $cities);

            // load current weather by chunk of 20 items
            $group = false;
            for ($i = 0, $len = \count($cities); $i < $len; ++$i) {
                if (0 === $i % OpenWeatherService::MAX_GROUP) {
                    $ids = \array_splice($cityIds, 0, OpenWeatherService::MAX_GROUP);
                    $group = $this->service->group($ids, $units);
                }
                /** @var array $group */
                if ($group) {
                    $cities[$i]['name'] = $group['list'][$i % 20]['name'];
                    $cities[$i]['current'] = $group['list'][$i % 20];
                    $cities[$i]['units'] = $group['units'];
                }
            }

            // save
            $this->setSessionValue(self::KEY_QUERY, $query);
            $this->setSessionValue(self::KEY_UNITS, $units);
            $this->setSessionValue(self::KEY_LIMIT, $limit);
            $this->setSessionValue(self::KEY_COUNT, $count);

            // display
            return $this->render('openweather/search_city.html.twig', [
                'form' => $form->createView(),
                'cities' => $cities,
                'units' => $units,
                'count' => $count,
            ]);
        }

        // display
        return $this->render('openweather/search_city.html.twig', [
            'form' => $form->createView(),
            'count' => $data[self::KEY_COUNT],
        ]);
    }

    /**
     * Shows the current weather, if applicable, the search cities otherwise.
     *
     * @Route("/wather", name="openweather_weather")
     */
    public function weather(Request $request): Response
    {
        $cityId = $this->getSessionCityId($request);
        if (0 !== $cityId) {
            return $this->redirectToRoute('openweather_current', [
                self::KEY_CITY_ID => $cityId,
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

    private function createImportForm(): FormInterface
    {
        // create form
        $helper = $this->createFormHelper();

        // constraints
        $constraints = new File([
            'mimeTypes' => 'application/gzip',
            'mimeTypesMessage' => $this->trans('import.error.mime_type'),
        ]);

        // file input
        $helper->field('file')
            ->label('import.file')
            ->updateOption('constraints', $constraints)
            ->updateAttribute('accept', 'application/x-gzip')
            ->addFileType();

        return $helper->createForm();
    }

    private function getGzContent(UploadedFile $file): ?string
    {
        $filename = (string) $file->getRealPath();

        // get size
        if (false === $handle = \fopen($filename, 'r')) {
            return null;
        }
        \fseek($handle, -4, \SEEK_END);
        $buffer = (string) \fread($handle, 4);
        $unpacked = (array) \unpack('V', $buffer);
        $uncompressedSize = \end($unpacked);
        \fclose($handle);

        // read the gzipped content, specifying the exact length
        if (false === $handle = \gzopen($filename, 'rb')) {
            return null;
        }
        $content = (string) \gzread($handle, $uncompressedSize);
        \gzclose($handle);

        return $content;
    }

    private function getJsonContent(string $content): ?array
    {
        $decoded = \json_decode($content, true);
        if (\JSON_ERROR_NONE !== \json_last_error()) {
            return null;
        }

        return $decoded;
    }

    private function getRequestCityId(Request $request): int
    {
        return (int) $request->get(self::KEY_CITY_ID, 0);
    }

    private function getRequestCount(Request $request, int $default = 5): int
    {
        return (int) $request->get(self::KEY_COUNT, $default);
    }

    private function getRequestLimit(Request $request, int $default = 25): int
    {
        return (int) $request->get(self::KEY_LIMIT, $default);
    }

    private function getRequestQuery(Request $request): string
    {
        return \trim((string) $request->get(self::KEY_QUERY, ''));
    }

    private function getRequestUnits(Request $request): string
    {
        return (string) $request->get(self::KEY_UNITS, OpenWeatherService::UNIT_METRIC);
    }

    private function getSessionCityId(Request $request): int
    {
        return (int) $this->getSessionInt(self::KEY_CITY_ID, $this->getRequestCityId($request));
    }

    private function getSessionCount(Request $request, int $default = 5): int
    {
        return (int) $this->getSessionInt(self::KEY_COUNT, $this->getRequestCount($request, $default));
    }

    private function getSessionLimit(Request $request, int $default = 25): int
    {
        return (int) $this->getSessionInt(self::KEY_LIMIT, $this->getRequestLimit($request, $default));
    }

    private function getSessionQuery(Request $request): string
    {
        return (string) $this->getSessionString(self::KEY_QUERY, $this->getRequestQuery($request));
    }

    private function getSessionUnits(Request $request): string
    {
        return (string) $this->getSessionString(self::KEY_UNITS, $this->getRequestUnits($request));
    }

    private function renderErrorResult(string $message, array $parameters = []): Response
    {
        return $this->render('openweather/import_result.html.twig', [
            'message' => $this->trans($message, $parameters),
            'result' => false,
        ]);
    }
}
