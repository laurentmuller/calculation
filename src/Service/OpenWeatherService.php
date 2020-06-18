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
use App\Traits\DateFormatterTrait;
use Symfony\Component\DependencyInjection\Exception\ParameterNotFoundException;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Intl\Countries;
use Symfony\Component\Intl\Exception\MissingResourceException;

/**
 * Service to get weather from OpenWeatherMap.
 *
 * @author Laurent Muller
 *
 * @see https://openweathermap.org/api
 */
class OpenWeatherService extends HttpClientService
{
    use DateFormatterTrait;

    /**
     * The database name.
     */
    public const DATABASE_NAME = 'openweather.sqlite';

    /**
     * The imperial degree.
     */
    public const DEGREE_IMPERIAL = '°F';

    /**
     * The metric degree.
     */
    public const DEGREE_METRIC = '°C';

    /**
     * The imperial speed.
     */
    public const SPEED_IMPERIAL = 'mph';

    /**
     * The metric speed.
     */
    public const SPEED_METRIC = 'm/s';

    /**
     * The imperial units parameter value.
     */
    public const UNIT_IMPERIAL = 'imperial';

    /**
     * The metric units parameter value.
     */
    public const UNIT_METRIC = 'metric';

    /**
     * The country flag URL.
     */
    private const COUNTRY_FLAG_URL = 'http://openweathermap.org/images/flags/{0}.png';

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
     * @throws ParameterNotFoundException if the OpenWeather key parameter is not defined
     */
    public function __construct(ParameterBagInterface $params, KernelInterface $kernel)
    {
        $this->key = $params->get(self::PARAM_KEY);
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

        // update country name and flag
        if (isset($result['sys']['country'])) {
            $country = $result['sys']['country'];
            if ($name = $this->getCountryName($country)) {
                $result['sys']['country_name'] = $name;
            }
            $result['sys']['country_flag'] = $this->getCountryFlag($country);
        }

        // update dates
        if (isset($result['dt'])) {
            $result['dt_formatted'] = $this->localeDateTime($result['dt']);
        }
        if (isset($result['sys']['sunrise'])) {
            $result['sys']['sunrise_formatted'] = $this->localeTime($result['sys']['sunrise']);
        }
        if (isset($result['sys']['sunset'])) {
            $result['sys']['sunset_formatted'] = $this->localeTime($result['sys']['sunset']);
        }

        // units
        $result['sys']['degree_unit'] = $this->getDegreeUnit($units);
        $result['sys']['speed_unit'] = $this->getSpeedUnit($units);

        return $result;
    }

    /**
     * Delete all cities.
     *
     * @return bool true on success
     */
    public function deletCities(): bool
    {
        $db = $this->getDatabase();
        $result = $db->deletCities();
        $db->close();

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

        // update country name
        if (isset($result['city']['country'])) {
            $country = $result['city']['country'];
            if ($name = $this->getCountryName($country)) {
                $result['city']['country_name'] = $name;
            }
            $result['city']['country_flag'] = $this->getCountryFlag($country);
        }

        // update dates
        if (isset($result['city']['sunrise'])) {
            $result['city']['sunrise_formatted'] = $this->localeTime($result['city']['sunrise']);
        }
        if (isset($result['city']['sunset'])) {
            $result['city']['sunset_formatted'] = $this->localeTime($result['city']['sunset']);
        }

        // units
        $result['city']['degree_unit'] = $this->getDegreeUnit($units);
        $result['city']['speed_unit'] = $this->getSpeedUnit($units);

        foreach ($result['list'] as &$data) {
            if (isset($data['dt'])) {
                $data['dt_formatted'] = $this->localeDateTime($data['dt']);
            }
        }

        return $result;
    }

    /**
     * Gets the API key.
     */
    public function getApiKey(): string
    {
        return $this->key;
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
     * Import cities from the given source.
     *
     * @param resource|string $source the JSON source to read
     *
     * @return bool true on success
     */
    public function import($source): bool
    {
        // get content
        if (\is_resource($source) && false === $data = \stream_get_contents($source)) {
            return $this->setLastError(204, 'Unable to read the content.');
        } elseif (\is_string($source) && false === $data = \file_get_contents($source)) {
            return $this->setLastError(204, 'Unable to read the content.');
        } else {
            return $this->setLastError(204, 'The source must be a resource or a string.');
        }

        // decode
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

        foreach ($result as &$city) {
            $country = $city['country'];
            if ($name = $this->getCountryName($country)) {
                $city['country_name'] = $name;
            }
            $city['country_flag'] = $this->getCountryFlag($country);
        }

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
        //add key and lang
        $query['appid'] = $this->key;
        $query['lang'] = self::getAcceptLanguage(true);

        // call
        $response = $this->requestGet($uri, [
            'query' => $query,
        ]);

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
     * Gets the country flag url.
     */
    private function getCountryFlag(string $country): string
    {
        return \str_replace('{0}', \strtolower($country), self::COUNTRY_FLAG_URL);
    }

    /**
     * Gets the country name from the alpha2 code.
     */
    private function getCountryName(string $country): ?string
    {
        try {
            return Countries::getName($country);
        } catch (MissingResourceException $e) {
            return null;
        }
    }

    /**
     * Gets the degree units.
     */
    private function getDegreeUnit(string $units): string
    {
        return self::UNIT_METRIC === $units ? self::DEGREE_METRIC : self::DEGREE_IMPERIAL;
    }

    /**
     * Gets the speed units.
     */
    private function getSpeedUnit(string $units): string
    {
        return self::UNIT_METRIC === $units ? self::SPEED_METRIC : self::SPEED_IMPERIAL;
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
