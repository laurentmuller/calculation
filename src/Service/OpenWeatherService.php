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
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\DependencyInjection\Exception\ParameterNotFoundException;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Intl\Countries;
use Symfony\Component\Intl\Exception\MissingResourceException;
use Symfony\Contracts\Cache\ItemInterface;

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
     * The parameter value to exclude the current data.
     */
    public const EXCLUDE_CURRENT = 'current';

    /**
     * The parameter value to exclude the daily data.
     */
    public const EXCLUDE_DAILY = 'daily';

    /**
     * The parameter value to exclude the hourly data.
     */
    public const EXCLUDE_HOURLY = 'hourly';

    /**
     * The parameter value to exclude the minutely data.
     */
    public const EXCLUDE_MINUTELY = 'minutely';

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
     * The cache timeout (15 minutes).
     */
    private const CACHE_TIMEOUT = 60 * 15;

    /**
     * The country flag URL.
     */
    private const COUNTRY_URL = 'https://openweathermap.org/images/flags/{0}.png';

    /**
     * The relative path to data.
     */
    private const DATA_PATH = '/resources/data/';

    /**
     * The host name.
     */
    private const HOST_NAME = 'https://api.openweathermap.org/data/2.5/';

    /**
     * The big icon URL.
     */
    private const ICON_BIG_URL = 'https://openweathermap.org/img/wn/{0}@4x.png';

    /**
     * The small icon URL.
     */
    private const ICON_SMALL_URL = 'https://openweathermap.org/img/wn/{0}@2x.png';

    /**
     * The parameter name for the API key.
     */
    private const PARAM_KEY = 'openweather_key';

    /**
     * Current condition URI.
     */
    private const URI_CURRENT = 'weather';

    /**
     * 16 day / daily forecast URI.
     */
    private const URI_DAILY = 'forecast/daily';

    /**
     * 5 day / 3 hour forecast URI.
     */
    private const URI_FORECAST = 'forecast';

    /**
     * One call condition URI.
     */
    private const URI_ONECALL = 'onecall';

    /**
     * The cache adapter.
     *
     * @var AdapterInterface
     */
    private $adapter;

    /**
     * The data directory.
     *
     * @var string
     */
    private $dataDirectory;

    /**
     * The debug mode.
     *
     * @var bool
     */
    private $debug;

    /**
     * The invalid characters for the cache key.
     *
     * @var string[]
     */
    private $invalidChars;

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
    public function __construct(ParameterBagInterface $params, KernelInterface $kernel, AdapterInterface $adapter)
    {
        $this->key = $params->get(self::PARAM_KEY);
        $this->dataDirectory = $kernel->getProjectDir() . self::DATA_PATH;
        $this->debug = $kernel->isDebug();
        $this->adapter = $adapter;
        $this->invalidChars = \str_split(ItemInterface::RESERVED_CHARACTERS);
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
        if (!$result = $this->get(self::URI_CURRENT, $query)) {
            return false;
        }

        return $result;
    }

    /**
     * Returns 16 day / daily forecat conditions data for a specific location.
     *
     * @param int    $cityId the city identifier
     * @param int    $count  the number of result to return or -1 for all
     * @param string $units  the units to use
     *
     * @return array|bool the current conditions if success; false on error
     */
    public function daily(int $cityId, int $count = -1, string $units = self::UNIT_METRIC)
    {
        $query = [
            'id' => $cityId,
            'units' => $units,
        ];
        if ($count > 0) {
            $query['cnt'] = $count;
        }
        if (!$result = $this->get(self::URI_DAILY, $query)) {
            return false;
        }

        return $result;
    }

    /**
     * Returns 5 day / 3 hour forecat conditions data for a specific location.
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
        if (!$result = $this->get(self::URI_FORECAST, $query)) {
            return false;
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
     * Gets the degree units.
     */
    public function getDegreeUnit(string $units): string
    {
        return self::UNIT_METRIC === $units ? self::DEGREE_METRIC : self::DEGREE_IMPERIAL;
    }

    /**
     * Gets the speed units.
     */
    public function getSpeedUnit(string $units): string
    {
        return self::UNIT_METRIC === $units ? self::SPEED_METRIC : self::SPEED_IMPERIAL;
    }

    /**
     * Returns all essential weather data for a specific location.
     *
     * @param float    $latitude  the latitude
     * @param float    $longitude the longitude
     * @param string[] $exclude   the parts to exclude from the response. Available values:
     *                            <ul>
     *                            <li>'current'</li>
     *                            <li>'minutely'</li>
     *                            <li>'hourly'</li>
     *                            <li>'daily'</li>
     *                            </ul>
     * @param string   $units     the units to use
     *
     * @return array|bool the essential conditions if success; false on error
     */
    public function onecall(float $latitude, float $longitude, string $units = self::UNIT_METRIC, array $exclude = [])
    {
        $query = [
            'lat' => $latitude,
            'lon' => $longitude,
            'units' => $units,
        ];
        if (!empty($exclude)) {
            $query['exclude'] = \implode(',', $exclude);
        }
        if (!$result = $this->get(self::URI_ONECALL, $query)) {
            return false;
        }

        return $result;
    }

    /**
     * Returns information for an array of cities that match the search text.
     *
     * @param string $query the city to search for
     * @param string $units the units to use
     * @param int    $limit the maximum number of cities to return
     *
     * @return array|bool the search result if success; false on error
     */
    public function search(string $query, string $units = self::UNIT_METRIC, int $limit = 25)
    {
        // find from cache
        if (!$this->debug) {
            $key = $this->getCacheKey('search', ['city' => $query]);
            $item = $this->adapter->getItem($key);
            if ($item->isHit()) {
                return $item->get();
            }
        }

        // search
        $db = $this->getDatabase(true);
        $result = $db->findCity($query, $limit);
        $db->close();

        if (!empty($result)) {
            // update
            $this->updateResult($result);

            // save to cache
            if (!$this->debug) {
                $item->expiresAfter(self::CACHE_TIMEOUT)
                    ->set($result);
                $this->adapter->save($item);
            }
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
     * Adds units to the given array.
     *
     * @param array  $data  the data to update
     * @param string $units the query units
     */
    private function addUnits(array &$data, string $units): void
    {
        $data['units'] = [
            'system' => $units,
            'temperature' => $this->getDegreeUnit($units),
            'speed' => $this->getSpeedUnit($units),
            'pressure' => 'hPa',
            'degree' => '°',
            'percent' => '%',
            'volume' => 'mm',
        ];
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
     * Finds the time zone (shift in seconds from UTC).
     *
     * @param array $data the data search in
     *
     * @return int the offset, if found; 0 otherwise
     */
    private function findTimezone(array $data): int
    {
        foreach ($data as $key => $value) {
            if ('timezone' === $key) {
                return (int) $value;
            } elseif (\is_array($value) && $timeZone = $this->findTimeZone($value)) {
                return $timeZone;
            }
        }

        return 0;
    }

    /**
     * Make a HTTP-GET call.
     *
     * @param string $uri   the uri to append to the host name
     * @param array  $query an associative array of query string values to add to the request
     *
     * @return array|bool the JSON response on success, false on failure
     */
    private function get(string $uri, array $query = [])
    {
        //add API key and language
        $query['appid'] = $this->key;
        $query['lang'] = self::getAcceptLanguage(true);

        // find from cache
        if (!$this->debug) {
            $key = $this->getCacheKey($uri, $query);
            $item = $this->adapter->getItem($key);
            if ($item->isHit()) {
                return $item->get();
            }
        }

        // call
        $response = $this->requestGet($uri, [
            'query' => $query,
        ]);

        // decode
        $result = $response->toArray(false);

        // check
        if (!$result = $this->checkErrorCode($result)) {
            return false;
        }

        // update
        $offset = $this->findTimezone($result);
        $timezone = $this->offsetToTimZone($offset);
        $this->updateResult($result, $timezone);
        $this->addUnits($result, $query['units']);

        // save to cache
        if (!$this->debug) {
            $item->expiresAfter(self::CACHE_TIMEOUT)
                ->set($result);
            $this->adapter->save($item);
        }

        return $result;
    }

    /**
     * Gets the cache key for the given uri and query.
     */
    private function getCacheKey(string $uri, array $query): string
    {
        $key = $uri . '?' . \http_build_query($query);
        $key = \str_replace($this->invalidChars, '_', $key);

        return $key;
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
     * Converts the given offset to a time zone.
     *
     * @param int $offset the timezone offset in seconds from UTC
     *
     * @return string the timezone offset in +/-HH:MM form
     */
    private function offsetToTimZone(int $offset): \DateTimeZone
    {
        $sign = $offset < 0 ? '-' : '+';
        $minutes = \floor(\abs($offset) / 60) % 60;
        $hours = \floor(\abs($offset) / 3600);
        $id = \sprintf('%s%02d%02d', $sign, $hours, $minutes);

        return new \DateTimeZone($id);
    }

    private function replaceUrl(string $url, string $value): string
    {
        return \str_replace('{0}', $value, $url);
    }

    /**
     * Update result.
     *
     * @param array $result   the result to process
     * @param mixed $timezone the timezone identifier
     */
    private function updateResult(array &$result, $timezone = null): void
    {
        foreach ($result as $key => &$value) {
            switch ((string) $key) {
                case 'icon':
                    $result['icon_big'] = $this->replaceUrl(self::ICON_BIG_URL, $value);
                    $result['icon_small'] = $this->replaceUrl(self::ICON_SMALL_URL, $value);
                    break;

                case 'description':
                    $result[$key] = \ucfirst($value);
                    break;

                case 'country':
                    if ($name = $this->getCountryName($value)) {
                        $result['country_name'] = $name;
                    }
                    $result['country_flag'] = $this->replaceUrl(self::COUNTRY_URL, \strtolower($value));
                    break;

                case 'dt':
                    $result['dt_formatted'] = $this->localeDateTime($value, \IntlDateFormatter::MEDIUM);
                    $result['dt_date_formatted'] = $this->localeDate($value, \IntlDateFormatter::SHORT);
                    $result['dt_locale'] = $this->localeDateTime($value, null, null, $timezone);
                    unset($result['dt_txt']);
                    break;

                case 'sunrise':
                    $result['sunrise_formatted'] = $this->localeTime($value, null, $timezone);
                    break;

                case 'sunset':
                    $result['sunset_formatted'] = $this->localeTime($value, null, $timezone);
                    break;

                case 'weather':
                    if (\is_array($value) && !empty($value)) {
                        $this->updateResult($value, $timezone);
                        $value = $value[0];
                    }
                    break;

                default:
                    if (\is_array($value)) {
                        $this->updateResult($value, $timezone);
                    }
                    break;
            }
        }
    }
}
