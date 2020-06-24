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

use App\Database\OpenWeatherDatabase;
use App\Form\FormHelper;
use App\Service\OpenWeatherService;
use App\Utils\Utils;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\Form\Extension\Core\Type\SearchType;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
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
class OpenWeatherController extends BaseController
{
    /**
     * The key to save/load the city identifier within the session.
     */
    private const KEY_CITY_ID = 'openweather.cityId';

    /**
     * The key to save/load the limit within the session.
     */
    private const KEY_LIMIT = 'openweather.limit';

    /**
     * The key to save/load the search  query within the session.
     */
    private const KEY_QUERY = 'openweather.query';

    /**
     * The key to save/load the units within the session.
     */
    private const KEY_UNITS = 'openweather.units';

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
     * @Route("/current", name="openweather_current")
     */
    public function current(Request $request): JsonResponse
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
     * Returns current conditions data for a specific location.
     *
     * @Route("/current/view", name="openweather_current_view")
     */
    public function currentView(Request $request): Response
    {
        $cityId = $this->getRequestCityId($request);
        $units = $this->getRequestUnits($request);
        $count = $this->getRequestCount($request, 5);

        // load
        $current = $this->service->current($cityId, $units);
        $forecast = $this->service->forecast($cityId, $count, $units);
        $daily = $this->service->daily($cityId, $count, $units);

        //save
        if (false !== $current) {
            $session = $request->getSession();
            $session->set(self::KEY_CITY_ID, $cityId);
            $session->set(self::KEY_UNITS, $units);
        }

        return $this->render('openweather/current_weather.htm.twig', [
            'current' => $current,
            'forecast' => $forecast,
            'daily' => $daily,
        ]);
    }

    /**
     * Returns 16 day / daily forecast conditions data for a specific location.
     *
     * @Route("/daily", name="openweather_daily")
     */
    public function daily(Request $request): JsonResponse
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
     * @Route("/forecast", name="openweather_forecast")
     */
    public function forecast(Request $request): JsonResponse
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
     * Import cities.
     *
     * Data can be dowloaded from the <a href="http://bulk.openweathermap.org/sample/">sample directory</a>.
     *
     * @Route("/import", name="openweather_import")
     * @IsGranted("ROLE_ADMIN")
     */
    public function import(Request $request, KernelInterface $kernel, OpenWeatherService $service): Response
    {
        // create form
        $builder = $this->createFormBuilder();
        $helper = new FormHelper($builder);

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

        // handle request
        $form = $builder->getForm();
        if ($this->handleRequestForm($request, $form)) {
            try {
                $valids = 0;
                $errors = 0;
                $result = false;
                $message = null;

                // get temp file
                if (!$temp_name = Utils::tempfile('sql')) {
                    $message = $this->trans('import.error.temp_file');
                    goto end;
                }

                /** @var UploadedFile $file */
                $file = $form['file']->getData();
                $name = $file->getClientOriginalName();

                // get content
                if (!$content = $this->getGzContent($file)) {
                    $message = $this->trans('import.error.open_archive', ['name' => $name]);
                    goto end;
                }

                // decode content
                if (!$cities = $this->getJsonContent($content)) {
                    $message = $this->trans('import.error.empty_archive', ['name' => $name]);
                    goto end;
                }

                // create database
                $db = new OpenWeatherDatabase($temp_name);
                $db->beginTransaction();

                // insert cities
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

                // move
                $target = $service->getDatabaseName();
                $result = \rename($temp_name, $target);
                end:
            } finally {
                // clean
                if ($db) {
                    $db->close();
                }
                Utils::unlink($temp_name);
                Utils::unlink($file);
            }

            $data = [
                'result' => $result,
                'valids' => $valids,
                'errors' => $errors,
                'message' => $message,
            ];

            // display result
            return $this->render('openweather/import_result.html.twig', [
                'data' => $data,
            ]);
        }

        // display
        return $this->render('openweather/import_file.html.twig', [
            //'last_import' => $this->getApplication()->getLastImport(),
            'sample' => 'http://bulk.openweathermap.org/sample/',
            'openweathermap' => 'https://openweathermap.org/',
            'form' => $form->createView(),
        ]);
    }

    /**
     * Returns all essential weather data for a specific location.
     *
     * @Route("/onecall", name="openweather_onecall")
     */
    public function onecall(Request $request): JsonResponse
    {
        try {
            $latitude = (float) $request->get('latitude', 0.0);
            $longitude = (float) $request->get('longitude', 0.0);
            $exclude = (array) $request->get('exclude', []);
            $units = $this->getRequestUnits($request);
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
     * @Route("/search", name="openweather_search")
     */
    public function search(Request $request, UrlGeneratorInterface $generator): JsonResponse
    {
        try {
            $query = (string) $request->get('query');
            $units = $this->getRequestUnits($request);
            if (false === $response = $this->service->search($query, $units)) {
                return $this->json($this->service->getLastError());
            }

            // add urls
            $parameters = ['units' => $units];
            foreach ($response as &$city) {
                $parameters['latitude'] = $city['latitude'];
                $parameters['longitude'] = $city['longitude'];
                $city['onecall_url'] = $generator->generate('openweather_onecall', $parameters, UrlGeneratorInterface::ABSOLUTE_URL);
                unset($parameters['latitude'], $parameters['longitude']);

                $parameters['cityId'] = $city['id'];
                $city['current_url'] = $generator->generate('openweather_current', $parameters, UrlGeneratorInterface::ABSOLUTE_URL);
                $city['forecast_url'] = $generator->generate('openweather_forecast', $parameters, UrlGeneratorInterface::ABSOLUTE_URL);
                $city['daily_url'] = $generator->generate('openweather_daily', $parameters, UrlGeneratorInterface::ABSOLUTE_URL);

                $city['current_view_url'] = $generator->generate('openweather_current_view', $parameters, UrlGeneratorInterface::ABSOLUTE_URL);

                unset($parameters['cityId']);
            }

            return $this->json($response);
        } catch (\Exception $e) {
            return $this->jsonException($e);
        }
    }

    /**
     * Shows the search city view.
     *
     * @Route("/search/view", name="openweather_search_view")
     */
    public function searchView(Request $request): Response
    {
        // create form
        $builder = $this->createFormBuilder();
        $helper = new FormHelper($builder, 'openweather.search.');

        $query = $this->getSessionQuery($request);
        $helper->field('query')
            ->updateOption('data', $query)
            ->updateOption('constraints', new Length(['min' => 2]))
            ->updateAttribute('placeholder', 'openweather.search.place_holder')
            ->updateAttribute('minlength', 2)
            ->add(SearchType::class);

        $units = $this->getSessionUnits($request);
        $helper->field('units')
            ->updateOption('data', $units)
            ->updateOption('choice_translation_domain', false)
            ->addChoiceType([
                OpenWeatherService::DEGREE_METRIC => OpenWeatherService::UNIT_METRIC,
                OpenWeatherService::DEGREE_IMPERIAL => OpenWeatherService::UNIT_IMPERIAL,
            ]);

        $limit = $this->getSessionLimit($request);
        $helper->field('limit')
            ->updateOption('data', $limit)
            ->updateOption('choice_translation_domain', false)
            ->addChoiceType([
                10 => 10,
                25 => 25,
                50 => 50,
                100 => 100,
            ]);

        // handle request
        $form = $builder->getForm();
        if ($this->handleRequestForm($request, $form)) {
            // get values
            $query = (string)$form->get('query')->getData();
            $units = (string)$form->get('units')->getData();
            $limit = (int)$form->get('limit')->getData();

            // search
            $cities = $this->service->search($query, $units, $limit);

            // update
            foreach ($cities as &$city) {
                if (false !== $current = $this->service->current($city['id'], $units)) {
                    $city['name'] = $current['name'];
                    $city['current'] = $current;
                }
            }

            // save
            $session = $request->getSession();
            $session->set(self::KEY_QUERY, $query);
            $session->set(self::KEY_UNITS, $units);
            $session->set(self::KEY_LIMIT, $limit);

            // display
            return $this->render('openweather/search_city.html.twig', [
                'form' => $form->createView(),
                'cities' => $cities,
                'units' => $units,
            ]);
        }

        // display
        return $this->render('openweather/search_city.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * Shows the current weather, if applicable, the search city view otherwise.
     *
     * @Route("/wather/view", name="openweather_weather_view")
     */
    public function weatherView(Request $request): Response
    {
        $cityId = $this->getSessionCityId($request);
        $units = $this->getSessionUnits($request);

        if (0 !== $cityId) {
            return $this->redirectToRoute('openweather_current_view', ['cityId' => $cityId, 'units' => $units]);
        }

        return $this->redirectToRoute('openweather_search_view', ['units' => $units]);
    }

    private function getGzContent(UploadedFile $file): ?string
    {
        $filename = $file->getRealPath();

        // get size
        if (false === $handle = \fopen($filename, 'r')) {
            return null;
        }
        \fseek($handle, -4, SEEK_END);
        $buffer = \fread($handle, 4);
        $unpacked = \unpack('V', $buffer);
        $uncompressedSize = \end($unpacked);
        \fclose($handle);

        // read the gzipped content, specifying the exact length
        if (false === $handle = \gzopen($filename, 'rb')) {
            return null;
        }
        $content = \gzread($handle, $uncompressedSize);
        \gzclose($handle);

        return $content;
    }

    private function getJsonContent($content): ?array
    {
        $decoded = \json_decode($content, true);
        if (JSON_ERROR_NONE !== \json_last_error()) {
            return null;
        }

        return $decoded;
    }

    private function getRequestCityId(Request $request): int
    {
        return (int) $request->get('cityId', 0);
    }

    private function getRequestCount(Request $request, int $default = -1): int
    {
        return (int) $request->get('count', $default);
    }

    private function getRequestLimit(Request $request, int $default = 25): int
    {
        return (int) $request->get('limit', $default);
    }

    private function getRequestQuery(Request $request): ?string
    {
        return \trim((string) $request->get('query', ''));
    }

    private function getRequestUnits(Request $request): string
    {
        return (string) $request->get('units', OpenWeatherService::UNIT_METRIC);
    }

    private function getSessionCityId(Request $request): int
    {
        $session = $request->getSession();
        $value = (int) $session->get(self::KEY_CITY_ID, $this->getRequestCityId($request));

        return $value;
    }

    private function getSessionLimit(Request $request, int $default = 25): int
    {
        $session = $request->getSession();
        $value = (int) $session->get(self::KEY_LIMIT, $this->getRequestLimit($request, $default));

        return $value;
    }

    private function getSessionQuery(Request $request): string
    {
        $session = $request->getSession();
        $value = (string) $session->get(self::KEY_QUERY, $this->getRequestQuery($request));

        return $value;
    }

    private function getSessionUnits(Request $request): string
    {
        $session = $request->getSession();
        $value = (string) $session->get(self::KEY_UNITS, $this->getRequestUnits($request));

        return $value;
    }
}
