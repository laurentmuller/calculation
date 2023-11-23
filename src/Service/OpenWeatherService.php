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

namespace App\Service;

use App\Database\OpenWeatherDatabase;
use App\Utils\FormatUtils;
use phpDocumentor\Reflection\DocBlock\Tags\See;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Intl\Countries;
use Symfony\Component\Intl\Exception\MissingResourceException;

/**
 * Service to get weather from OpenWeatherMap.
 *
 * @see https://openweathermap.org/api
 *
 * @psalm-import-type OpenWeatherCityType from OpenWeatherDatabase
 *
 * @psalm-type OpenWeatherGroupType = array<array{
 *      cnt: int,
 *      units: array,
 *      list: array<int, array>
 *  }>
 */
class OpenWeatherService extends AbstractHttpClientService
{
    /**
     * The number of daily results to return.
     */
    final public const DEFAULT_COUNT = 5;

    /**
     * The number of search results to return.
     */
    final public const DEFAULT_LIMIT = 15;

    /**
     * The imperial degree.
     */
    final public const DEGREE_IMPERIAL = '°F';

    /**
     * The metric degree.
     */
    final public const DEGREE_METRIC = '°C';

    /**
     * The parameter value to exclude the current data.
     */
    final public const EXCLUDE_CURRENT = 'current';

    /**
     * The parameter value to exclude the daily data.
     */
    final public const EXCLUDE_DAILY = 'daily';

    /**
     * The parameter value to exclude the hourly data.
     */
    final public const EXCLUDE_HOURLY = 'hourly';

    /**
     * The parameter value to exclude the minutely data.
     */
    final public const EXCLUDE_MINUTELY = 'minutely';

    /**
     * The maximum number of city identifiers to retrieve.
     */
    final public const MAX_GROUP = 20;

    /**
     * The imperial speed.
     */
    final public const SPEED_IMPERIAL = 'mph';

    /**
     * The metric speed.
     */
    final public const SPEED_METRIC = 'm/s';

    /**
     * The imperial units parameter value.
     */
    final public const UNIT_IMPERIAL = 'imperial';

    /**
     * The metric units parameter value.
     */
    final public const UNIT_METRIC = 'metric';

    /**
     * The cache timeout (15 minutes).
     */
    private const CACHE_TIMEOUT = 60 * 15;

    /**
     * The country flag URL.
     */
    private const COUNTRY_URL = 'https://openweathermap.org/images/flags/{0}.png';

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
     * Current condition URI.
     */
    private const URI_CURRENT = 'weather';

    /**
     * 16 day / daily forecast URI.
     */
    private const URI_DAILY = 'forecast/daily';

    /**
     * 5 days / 3 hours forecast URI.
     */
    private const URI_FORECAST = 'forecast';

    /**
     * Current condition URI for a group (multiple cities).
     */
    private const URI_GROUP = 'group';

    /**
     * One call condition URI.
     */
    private const URI_ONECALL = 'onecall';

    /**
     * @throws \InvalidArgumentException if the API key  is not defined, is null or empty
     */
    public function __construct(
        #[\SensitiveParameter]
        #[Autowire('%open_weather_key%')]
        string $key,
        #[Autowire('%kernel.project_dir%/resources/data/openweather.sqlite')]
        private readonly string $databaseName,
        private readonly PositionService $service
    ) {
        parent::__construct($key);
    }

    /**
     * Returns the current, the hourly and daily forecasts
     * conditions data for a specific location.
     *
     * @param int    $id    the city identifier
     * @param int    $count the number of result to return or -1 for all
     * @param string $units the units to use
     *
     * @psalm-return array{current: array|false, forecast: array|false, daily: array|false}
     */
    public function all(int $id, int $count = self::DEFAULT_COUNT, string $units = self::UNIT_METRIC): array
    {
        return [
            'current' => $this->current($id, $units),
            'forecast' => $this->forecast($id, $count, $units),
            'daily' => $this->daily($id, $count, $units),
        ];
    }

    /**
     * Returns current conditions data for a specific location.
     *
     * @param int    $id    the city identifier
     * @param string $units the units to use
     *
     * @return array|false the current conditions if success; false on error
     */
    public function current(int $id, string $units = self::UNIT_METRIC): array|false
    {
        $query = [
            'id' => $id,
            'units' => $units,
        ];

        return $this->get(self::URI_CURRENT, $query);
    }

    /**
     * Returns daily forecast conditions data for a specific location.
     *
     * @param int    $id    the city identifier
     * @param int    $count the number of result to return or -1 for all
     * @param string $units the units to use
     *
     * @return array|false the current conditions if success; false on error
     */
    public function daily(int $id, int $count = self::DEFAULT_COUNT, string $units = self::UNIT_METRIC): array|false
    {
        $query = [
            'id' => $id,
            'units' => $units,
        ];
        if ($count > 0) {
            $query['cnt'] = $count;
        }

        return $this->get(self::URI_DAILY, $query);
    }

    /**
     * Returns hourly forecast conditions data for a specific location.
     *
     * @param int    $id    the city identifier
     * @param int    $count the number of result to return or -1 for all
     * @param string $units the units to use
     *
     * @return array|false the current conditions if success; false on error
     */
    public function forecast(int $id, int $count = self::DEFAULT_COUNT, string $units = self::UNIT_METRIC): array|false
    {
        $query = [
            'id' => $id,
            'units' => $units,
        ];
        if ($count > 0) {
            $query['cnt'] = $count;
        }

        return $this->get(self::URI_FORECAST, $query);
    }

    public function getCacheTimeout(): int
    {
        return self::CACHE_TIMEOUT;
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
        return $this->databaseName;
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
     * Returns current conditions data for a group of cities.
     *
     * @param int[]  $cityIds the city identifiers. The maximum number of city identifiers are 20.
     * @param string $units   the units to use
     *
     * @return array|bool the conditions for the given cities if success; false on error
     *
     * @psalm-return OpenWeatherGroupType|false
     */
    public function group(array $cityIds, string $units = self::UNIT_METRIC): array|false
    {
        if (\count($cityIds) > self::MAX_GROUP) {
            throw new \InvalidArgumentException('The number of city identifiers is greater than 20.');
        }
        $query = [
            'id' => \implode(',', $cityIds),
            'units' => $units,
        ];

        /** @psalm-var  OpenWeatherGroupType|false $result */
        $result = $this->get(self::URI_GROUP, $query);

        return $result;
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
     * @return array|false the essential conditions if success; false on error
     */
    public function oneCall(float $latitude, float $longitude, string $units = self::UNIT_METRIC, array $exclude = []): array|false
    {
        $query = [
            'lat' => $latitude,
            'lon' => $longitude,
            'units' => $units,
        ];
        if ([] !== $exclude) {
            $query['exclude'] = \implode(',', $exclude);
        }

        return $this->get(self::URI_ONECALL, $query);
    }

    /**
     * Search cities.
     *
     * @param string $name  the name of the city to search for
     * @param string $units the units to use
     * @param int    $limit the maximum number of cities to return
     *
     * @pslam-return array<int, OpenWeatherCityType>
     */
    public function search(string $name, string $units = self::UNIT_METRIC, int $limit = self::DEFAULT_LIMIT): array
    {
        $key = $this->getCacheKey('search', ['name' => $name, 'units' => $units, 'limit' => $limit]);

        return (array) ($this->getCacheValue($key, fn (): ?array => $this->doSearch($name, $limit)) ?? []);
    }

    protected function getDefaultOptions(): array
    {
        return [
            self::BASE_URI => self::HOST_NAME,
            self::QUERY => [
                'appid' => $this->key,
                'lang' => self::getAcceptLanguage(),
            ],
        ];
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
            'speed' => $this->getSpeedUnit($units),
            'temperature' => $this->getDegreeUnit($units),
            'pressure' => 'hPa',
            'degree' => '°',
            'percent' => '%',
            'volume' => 'mm',
        ];
    }

    /**
     * Checks if the response contains an error.
     */
    private function checkErrorCode(array $result): bool
    {
        if (isset($result['cod']) && Response::HTTP_OK !== (int) $result['cod']) {
            return $this->setLastError((int) $result['cod'], (string) $result['message']);
        }

        return true;
    }

    /**
     * @throws \Symfony\Contracts\HttpClient\Exception\ExceptionInterface
     * @throws \Exception
     */
    private function doGet(string $uri, array $query = []): ?array
    {
        $response = $this->requestGet($uri, [
            self::QUERY => $query,
        ]);

        /** @psalm-var array<string, mixed> $results */
        $results = $response->toArray(false);
        if (!$this->checkErrorCode($results)) {
            return null;
        }
        $offset = $this->findTimezone($results);
        $timezone = $this->offsetToTimZone($offset);
        $this->updateResults($results, $timezone);
        $this->addUnits($results, (string) $query['units']);

        return $results;
    }

    private function doSearch(string $name, int $limit): ?array
    {
        $db = null;

        try {
            $db = $this->getDatabase(true);
            /** @psalm-var array<int, mixed> $result */
            $result = $db->findCity($name, $limit);
            if ([] === $result) {
                return null;
            }
            $this->updateResults($result);

            return $result;
        } finally {
            $db?->close();
        }
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
        /** @psalm-var mixed $value */
        foreach ($data as $key => $value) {
            if ('timezone' === $key) {
                return (int) $value;
            } elseif (\is_array($value)) {
                $timeZone = $this->findTimeZone($value);
                if (0 !== $timeZone) {
                    return $timeZone;
                }
            }
        }

        return 0;
    }

    /**
     * Make an HTTP-GET call.
     *
     * @param string $uri   the uri to append to the host name
     * @param array  $query an associative array of query string values to add to the request
     *
     * @return array|false the JSON response on success, false on failure
     */
    private function get(string $uri, array $query = []): array|false
    {
        // find from cache
        $key = $this->getCacheKey($uri, $query);

        /** @psalm-var array|false $result */
        $result = $this->getCacheValue($key, fn (): ?array => $this->doGet($uri, $query)) ?? false;

        return $result;
    }

    /**
     * Gets the cache key for the given uri and query parameters.
     */
    private function getCacheKey(string $uri, array $query): string
    {
        return $uri . '?' . \http_build_query($query);
    }

    /**
     * Gets the country name from the alpha2 code.
     */
    private function getCountryName(string $country): ?string
    {
        try {
            return Countries::getName($country);
        } catch (MissingResourceException) {
            return null;
        }
    }

    /**
     * Converts the given offset to a time zone.
     *
     * @throws \Exception
     */
    private function offsetToTimZone(int $offset): \DateTimeZone
    {
        $hours = \intdiv($offset, 3600);
        $minutes = \abs(\intdiv($offset, 60) % 60);
        /** @psalm-var non-empty-string $timezone */
        $timezone = \sprintf('%+03d%02d', $hours, $minutes);

        return new \DateTimeZone($timezone);
    }

    private function replaceUrl(string $url, string $value): string
    {
        return \str_replace('{0}', $value, $url);
    }

    private function updateCoordinate(array &$results): void
    {
        if (!isset($results['lat']) || !isset($results['lon'])) {
            return;
        }
        $lat = (float) $results['lat'];
        $lon = (float) $results['lon'];
        $results['lat_lon_dms'] = $this->service->formatPosition($lat, $lon);
        $results['lat_lon_url'] = $this->service->getGoogleMapUrl($lat, $lon);
    }

    private function updateCountry(array &$results, string $value): void
    {
        $results['country_name'] = $this->getCountryName($value) ?? '';
        $results['country_flag'] = $this->replaceUrl(self::COUNTRY_URL, \strtolower($value));
    }

    private function updateDate(array &$result, int $value, \DateTimeZone $timezone = null): void
    {
        $result['dt_date'] = FormatUtils::formatDate($value, \IntlDateFormatter::SHORT);
        $result['dt_date_locale'] = FormatUtils::formatDate($value, \IntlDateFormatter::SHORT, timezone: $timezone);
        $result['dt_time'] = FormatUtils::formatTime($value, \IntlDateFormatter::SHORT);
        $result['dt_time_locale'] = FormatUtils::formatTime($value, \IntlDateFormatter::SHORT, timezone: $timezone);
        $result['dt_date_time'] = FormatUtils::formatDateTime($value, \IntlDateFormatter::SHORT, \IntlDateFormatter::SHORT);
        $result['dt_date_time_locale'] = FormatUtils::formatDateTime($value, \IntlDateFormatter::SHORT, \IntlDateFormatter::SHORT, timezone: $timezone);
        $result['dt_date_time_medium'] = FormatUtils::formatDateTime($value, \IntlDateFormatter::MEDIUM, \IntlDateFormatter::SHORT);
        $result['dt_date_time_medium_locale'] = FormatUtils::formatDateTime($value, \IntlDateFormatter::MEDIUM, \IntlDateFormatter::SHORT, timezone: $timezone);
        $result['dt_date_time_long'] = FormatUtils::formatDateTime($value, \IntlDateFormatter::LONG, \IntlDateFormatter::SHORT);
        $result['dt_date_time_long_locale'] = FormatUtils::formatDateTime($value, \IntlDateFormatter::LONG, \IntlDateFormatter::SHORT, timezone: $timezone);
        unset($result['dt_txt']);
    }

    private function updateDegree(array &$results, int $value): void
    {
        $results['deg_direction'] = $this->service->getDirection($value);
        $results['deg_description'] = $this->service->formatDirection($value);
    }

    private function updateDescription(mixed &$value): void
    {
        if (\is_string($value)) {
            $value = \ucfirst($value);
        }
    }

    private function updateIcon(array &$results, string $value): void
    {
        $results['icon_big'] = $this->replaceUrl(self::ICON_BIG_URL, $value);
        $results['icon_small'] = $this->replaceUrl(self::ICON_SMALL_URL, $value);
    }

    private function updateLatitude(array &$results, float $value): void
    {
        $results['lat_dms'] = $this->service->formatLatitude($value);
    }

    private function updateLongitude(array &$results, float $value): void
    {
        $results['lon_dms'] = $this->service->formatLongitude($value);
    }

    /**
     * @psalm-param array<array-key, mixed> $results
     */
    private function updateResults(array &$results, \DateTimeZone $timezone = null): void
    {
        /** @psalm-var mixed $value */
        foreach ($results as $key => &$value) {
            if (\is_array($value)) {
                $this->updateResults($value, $timezone);
                $this->updateCoordinate($value);
            }

            switch ((string) $key) {
                case 'icon':
                    $this->updateIcon($results, (string) $value);
                    break;
                case 'description':
                    $this->updateDescription($value);
                    break;
                case 'country':
                    $this->updateCountry($results, (string) $value);
                    break;
                case 'dt':
                    $this->updateDate($results, (int) $value, $timezone);
                    break;
                case 'sunrise':
                    $this->updateSunrise($results, (int) $value, $timezone);
                    break;
                case 'sunset':
                    $this->updateSunset($results, (int) $value, $timezone);
                    break;
                case 'weather':
                    $this->updateWeather($value);
                    break;
                case 'lat':
                    $this->updateLatitude($results, (float) $value);
                    break;
                case 'lon':
                    $this->updateLongitude($results, (float) $value);
                    break;
                case 'deg':
                    $this->updateDegree($results, (int) $value);
                    break;
                case 'timezone':
                    $this->updateTimezone($results, $timezone);
                    break;
            }
        }
    }

    private function updateSunrise(array &$results, int $value, \DateTimeZone $timezone = null): void
    {
        $results['sunrise_formatted'] = FormatUtils::formatTime($value, \IntlDateFormatter::SHORT, timezone: $timezone);
    }

    private function updateSunset(array &$results, int $value, \DateTimeZone $timezone = null): void
    {
        $results['sunset_formatted'] = FormatUtils::formatTime($value, \IntlDateFormatter::SHORT, timezone: $timezone);
    }

    private function updateTimezone(array &$results, \DateTimeZone $timezone = null): void
    {
        if ($timezone instanceof \DateTimeZone) {
            $results['timezone_name'] = $timezone->getName();
        }
    }

    private function updateWeather(mixed &$value): void
    {
        if (\is_array($value)) {
            $value = (array) \reset($value);
        }
    }
}
