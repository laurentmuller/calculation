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

namespace App\Service;

use App\Database\OpenWeatherDatabase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Service to get weather from OpenWeatherMap.
 *
 * @author Laurent Muller
 *
 * @see https://openweathermap.org/api
 */
class OpenWeatherService extends HttpClientService
{
    /**
     * The database name.
     */
    public const DATABASE_NAME = 'openweather.sqlite';

    /**
     * The imperial units.
     */
    public const UNIT_IMPERIAL = 'imperial';

    /**
     * The metric units.
     */
    public const UNIT_METRIC = 'metric';

    /**
     * The relative path to data.
     */
    private const DATA_PATH = '/resources/data/';

    /**
     * The host name.
     */
    private const HOST_NAME = 'http://api.openweathermap.org/data/2.5/';

    /**
     * The icon URL.
     */
    private const ICON_URL = 'http://openweathermap.org/img/wn/{0}@2x.png';

    /**
     * The parameter name for the API key.
     */
    private const PARAM_KEY = 'openweather_key';

    /**
     * Current condition URI.
     */
    private const URI_CURRENT_CONDITION = 'weather';

    /**
     * 5 days / 3 hours of Daily Forecasts URI.
     */
    private const URI_FORECASTS_5_DAYS = 'forecast';

    /**
     * The data directory.
     *
     * @var string
     */
    private $dataDirectory;

    /**
     * The API key.
     *
     * @var string
     */
    private $key;

    /**
     * Constructor.
     *
     * @param ContainerInterface $container the container to get the API key
     * @param KernelInterface    $kernel    the kernel to get the data path
     *
     * @throws \InvalidArgumentException if the OpenWeather key parameter is not defined
     */
    public function __construct(ContainerInterface $container, KernelInterface $kernel)
    {
        // check key
        if (!$container->hasParameter(self::PARAM_KEY)) {
            throw new \InvalidArgumentException('The OpenWeather key is not defined.');
        }
        $this->key = $container->getParameter(self::PARAM_KEY);
        $this->dataDirectory = $kernel->getProjectDir() . self::DATA_PATH;
    }

    /**
     * Returns current conditions data for a specific location.
     *
     * @param int    $cityId the city identifier
     * @param string $units  the units to use
     *
     * @return array|bool the current conditions if success; false on error
     */
    public function current(int $cityId, string $units = self::UNIT_METRIC)
    {
        $query = [
            'id' => $cityId,
            'units' => $units,
        ];

        if (!$result = $this->get(self::URI_CURRENT_CONDITION, $query)) {
            return false;
        }

        return $result;
    }

    /**
     * Returns forecats conditions data for a specific location.
     *
     * @param int    $cityId the city identifier
     * @param int    $count  the number of result to return or -1 for all
     * @param string $units  the units to use
     *
     * @return array|bool the current conditions if success; false on error
     */
    public function forecast(int $cityId, int $count = -1, string $units = self::UNIT_METRIC)
    {
        $query = [
            'id' => $cityId,
            'units' => $units,
        ];
        if ($count > 0) {
            $query['cnt'] = $count;
        }

        if (!$result = $this->get(self::URI_FORECASTS_5_DAYS, $query)) {
            return false;
        }

        return $result;
    }

    /**
     * Gets the database.
     *
     * @param bool $readonly true open the database for reading only
     *
     * @return OpenWeatherDatabase the database
     */
    public function getDatabase(bool $readonly = false): OpenWeatherDatabase
    {
        return new OpenWeatherDatabase($this->getDatabaseName(), $readonly);
    }

    /**
     * Gets the database file name.
     */
    public function getDatabaseName(): string
    {
        return $this->dataDirectory . self::DATABASE_NAME;
    }

    /**
     * Import cities from the given file name.
     *
     * @param string $filename the file to read
     *
     * @return bool true on success
     */
    public function import(string $filename): bool
    {
        // open
        if (false === $data = \file_get_contents($filename)) {
            return false;
        }

        if (!$decoded = $this->decodeJson($data)) {
            return false;
        }

        $total = 1;
        $insert = 0;
        $errors = 0;
        $db = $this->getDatabase();
        $db->beginTransaction();
        foreach ($decoded as $city) {
            // insert
            $fields = [
                $city['id'],
                $city['name'],
                $city['country'],
                $city['coord']['lat'],
                $city['coord']['lon'],
            ];
            if ($db->insertCity($fields)) {
                ++$insert;
            } else {
                ++$errors;
            }

            // commit
            if (0 === ++$total % 50000) {
                $db->commitTransaction();
                $db->beginTransaction();
            }
        }

        $db->commitTransaction();
        $db->compact();
        $db->close();

        return true;
    }

    /**
     * Returns information for an array of cities that match the search text.
     *
     * @param string $query the city to search for
     * @param string $units the units to use
     *
     * @return array|bool the search result if success; false on error
     */
    public function search(string $query, string $units = self::UNIT_METRIC)
    {
        $db = $this->getDatabase(true);
        $result = $db->findCity($query);
        $db->close();

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultOptions(): array
    {
        return [self::BASE_URI => self::HOST_NAME];
    }

    /**
     * Checks if the response contains an error.
     *
     * @param array $result the response to validate
     *
     * @return array|bool the result if no error found; false if an error
     */
    private function checkErrorCode(array $result)
    {
        if (isset($result['cod']) && Response::HTTP_OK !== (int) $result['cod']) {
            return $this->setLastError((int) $result['cod'], $result['message']);
        }

        return $result;
    }

    /**
     * Decodes a JSON string.
     *
     * @param string $data  the data to decode
     * @param bool   $assoc when true, returned objects will be converted into associative arrays
     *
     * @return mixed the decoded data if success; false on error
     */
    private function decodeJson(?string $data, bool $assoc = true)
    {
        $result = \json_decode($data, $assoc);
        if (JSON_ERROR_NONE !== $error = \json_last_error()) {
            return $this->setLastError($error, \json_last_error_msg());
        }

        return $result;
    }

    /**
     * Make a HTTP-GET call.
     *
     * @param string $uri   the uri to append to the host name
     * @param array  $query an associative array of query string values to add to the request
     *
     * @return mixed|bool the HTTP response body on success, false on failure
     */
    private function get(string $uri, array $query = [])
    {
        //try {
        //add key and lang
        $query['appid'] = $this->key;
        $query['lang'] = self::getAcceptLanguage(true);

        // call
        $response = $this->requestGet($uri, [
            'query' => $query,
        ]);

        // check error
//         $status = $response->getStatusCode();
//         if (Response::HTTP_OK !== $status) {
//             // return $this->setLastError($status, $response->getReasonPhrase());
//         }

        // decode
        $result = $response->toArray(false);

        // check result
        if (!$result = $this->checkErrorCode($result)) {
            return false;
        }

        // icons
        $this->updateIconUrl($result);

        return $result;
    }

    /**
     * Update icon's URL.
     *
     * @param array $data the data to process
     */
    private function updateIconUrl(array &$data): void
    {
        \array_walk_recursive($data, function (&$item, $key): void {
            if ('icon' === $key) {
                $item = \str_replace('{0}', $item, self::ICON_URL);
            }
        });
    }
}
