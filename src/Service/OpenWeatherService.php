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

use App\Enums\OpenWeatherUnits;
use phpDocumentor\Reflection\DocBlock\Tags\See;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;

/**
 * Service to get weather from OpenWeatherMap.
 *
 * @see https://openweathermap.org/api
 *
 * @phpstan-type OpenWeatherGroupType = array{cnt: int, units: array, list: array<int, array>}
 */
class OpenWeatherService extends AbstractHttpClientService
{
    /**
     * The number of daily results to return.
     */
    public const DEFAULT_COUNT = 5;

    /**
     * The number of search results to return.
     */
    public const DEFAULT_LIMIT = 15;

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
    final public const EXCLUDE_HOURLY = 'hourly';

    /**
     * The parameter value to exclude the minutely data.
     */
    final public const EXCLUDE_MINUTELY = 'minutely';

    /**
     * The maximum number of city identifiers to retrieve.
     */
    public const MAX_GROUP = 20;

    /**
     * The count parameter name.
     */
    public const PARAM_COUNT = 'cnt';

    /**
     * The excluded parameter name.
     */
    public const PARAM_EXCLUDE = 'exclude';

    /**
     * The city identifier parameter name.
     */
    public const PARAM_ID = 'id';

    /**
     * The latitude parameter name.
     */
    public const PARAM_LATITUDE = 'lat';

    /**
     * The limit parameter name.
     */
    public const PARAM_LIMIT = 'limit';

    /**
     * The latitude parameter name.
     */
    public const PARAM_LONGITUDE = 'lon';

    /**
     * The query parameter name.
     */
    public const PARAM_QUERY = 'query';

    /**
     * The unit's parameter name.
     */
    public const PARAM_UNITS = 'units';

    /**
     * The cache timeout (15 minutes).
     */
    private const CACHE_TIMEOUT = 60 * 15;

    /**
     * The host name version 2.5.
     */
    private const HOST_NAME_V_2_5 = 'https://api.openweathermap.org/data/2.5/';

    /**
     * The host name version 3.0 (used for one Api call).
     */
    private const HOST_NAME_V_3_0 = 'https://api.openweathermap.org/data/3.0/';

    /**
     * Current condition URI.
     */
    private const URI_CURRENT = 'weather';

    /**
     * The 16 days / daily forecast URI.
     */
    private const URI_DAILY = 'forecast/daily';

    /**
     * The 5 days / 3 hours forecast URI.
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
     * @throws \InvalidArgumentException if the API key is not defined, is null or empty
     */
    public function __construct(
        #[\SensitiveParameter]
        #[Autowire('%open_weather_key%')]
        string $key,
        CacheInterface $cache,
        LoggerInterface $logger,
        private readonly OpenWeatherFormatter $formatter
    ) {
        parent::__construct($key, $cache, $logger);
    }

    /**
     * Returns the current, the hourly and daily forecasts
     * conditions data for a specific location.
     *
     * @param int              $id    the city identifier
     * @param int              $count the number of results to return or -1 for all
     * @param OpenWeatherUnits $units the unit to use
     *
     * @phpstan-return array{current: array|false, forecast: array|false, daily: array|false}
     */
    public function all(
        int $id,
        int $count = self::DEFAULT_COUNT,
        OpenWeatherUnits $units = OpenWeatherUnits::METRIC
    ): array {
        return [
            'current' => $this->current($id, $units),
            'forecast' => $this->forecast($id, $count, $units),
            'daily' => $this->daily($id, $count, $units),
        ];
    }

    /**
     * Returns the current conditions data for a specific location.
     *
     * @param int              $id    the city identifier
     * @param OpenWeatherUnits $units the unit to use
     *
     * @return array|false the current conditions if success; false on error
     */
    public function current(int $id, OpenWeatherUnits $units = OpenWeatherUnits::METRIC): array|false
    {
        $query = [
            self::PARAM_ID => $id,
            self::PARAM_UNITS => $units,
        ];

        return $this->get(self::URI_CURRENT, $query);
    }

    /**
     * Returns daily forecast conditions data for a specific location.
     *
     * @param int              $id    the city identifier
     * @param int              $count the number of results to return or -1 for all
     * @param OpenWeatherUnits $units the unit to use
     *
     * @return array|false the current conditions if success; false on error
     */
    public function daily(
        int $id,
        int $count = self::DEFAULT_COUNT,
        OpenWeatherUnits $units = OpenWeatherUnits::METRIC
    ): array|false {
        $query = [
            self::PARAM_ID => $id,
            self::PARAM_UNITS => $units,
            self::PARAM_COUNT => $count,
        ];

        return $this->get(self::URI_DAILY, $query);
    }

    /**
     * Returns hourly forecast conditions data for a specific location.
     *
     * @param int              $id    the city identifier
     * @param int              $count the number of results to return or -1 for all
     * @param OpenWeatherUnits $units the unit to use
     *
     * @return array|false the current conditions if success; false on error
     */
    public function forecast(
        int $id,
        int $count = self::DEFAULT_COUNT,
        OpenWeatherUnits $units = OpenWeatherUnits::METRIC
    ): array|false {
        $query = [
            self::PARAM_ID => $id,
            self::PARAM_UNITS => $units,
            self::PARAM_COUNT => $count,
        ];

        return $this->get(self::URI_FORECAST, $query);
    }

    #[\Override]
    public function getCacheTimeout(): int
    {
        return self::CACHE_TIMEOUT;
    }

    /**
     * Returns current conditions data for a group of cities.
     *
     * @param int[]            $cityIds the city identifiers. The maximum is 20.
     * @param OpenWeatherUnits $units   the units to use
     *
     * @return array|false the conditions for the given cities, if success; false on error
     *
     * @phpstan-return OpenWeatherGroupType|false
     */
    public function group(array $cityIds, OpenWeatherUnits $units = OpenWeatherUnits::METRIC): array|false
    {
        if (\count($cityIds) > self::MAX_GROUP) {
            throw new \InvalidArgumentException('The number of city identifiers is greater than 20.');
        }
        $query = [
            self::PARAM_ID => \implode(',', $cityIds),
            self::PARAM_UNITS => $units,
        ];

        /** @phpstan-var OpenWeatherGroupType|false */
        return $this->get(self::URI_GROUP, $query);
    }

    /**
     * Returns all essential weather data for a specific location.
     *
     * @param float            $latitude   the latitude
     * @param float            $longitude  the longitude
     * @param string           ...$exclude the parts to exclude from the response. Available values:
     *                                     <ul>
     *                                     <li>'current'</li>
     *                                     <li>'minutely'</li>
     *                                     <li>'hourly'</li>
     *                                     <li>'daily'</li>
     *                                     </ul>
     * @param OpenWeatherUnits $units      the unit to use
     *
     * @phpstan-param self::EXCLUDE_* ...$exclude
     *
     * @return array|false the essential conditions if success; false on error
     */
    public function oneCall(
        float $latitude,
        float $longitude,
        OpenWeatherUnits $units = OpenWeatherUnits::METRIC,
        string ...$exclude
    ): array|false {
        $query = [
            self::PARAM_LATITUDE => $latitude,
            self::PARAM_LONGITUDE => $longitude,
            self::PARAM_UNITS => $units,
        ];
        if ([] !== $exclude) {
            $query['exclude'] = \implode(',', $exclude);
        }

        return $this->get(self::URI_ONECALL, $query, self::HOST_NAME_V_3_0);
    }

    #[\Override]
    protected function getDefaultOptions(): array
    {
        return [
            self::QUERY => [
                'appid' => $this->key,
                'lang' => self::getAcceptLanguage(),
            ],
        ];
    }

    /**
     * Adds units to the given array.
     *
     * @param array            $data  the data to update
     * @param OpenWeatherUnits $units the query unit
     */
    private function addUnits(array &$data, OpenWeatherUnits $units): void
    {
        $data['units'] = [
            'system' => $units->value,
            'speed' => $units->getSpeed(),
            'temperature' => $units->getDegree(),
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
        $code = (int) ($result['cod'] ?? Response::HTTP_OK);
        if (Response::HTTP_OK !== $code) {
            return $this->setLastError($code, (string) $result['message']);
        }

        return true;
    }

    /**
     * @phpstan-param array{units: string, ...} $query
     *
     * @throws ExceptionInterface
     */
    private function doGet(string $uri, array $query, string $hostName): array|false
    {
        $response = $this->requestGet($uri, [
            self::BASE_URI => $hostName,
            self::QUERY => $query,
        ]);

        $results = $response->toArray(false);
        if (!$this->checkErrorCode($results)) {
            return false;
        }

        $offset = $this->findTimezone($results);
        $timezone = $this->offsetToTimZone($offset);
        $this->formatter->update($results, $timezone);
        $units = OpenWeatherUnits::from($query[self::PARAM_UNITS]);
        $this->addUnits($results, $units);
        $this->sortResults($results);

        return $results;
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
        /** @phpstan-var mixed $value */
        foreach ($data as $key => $value) {
            if ('timezone' === $key) {
                return (int) $value;
            } elseif (\is_array($value)) {
                $timeZone = $this->findTimezone($value);
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
     * @param string $uri      the uri to append to the host name
     * @param array  $query    an associative array of query string values to add to the request
     * @param string $hostName the host name (API version) to use
     *
     * @phpstan-param array{units: OpenWeatherUnits, ...} $query
     *
     * @return array|false the JSON response on success, false on failure
     */
    private function get(string $uri, array $query, string $hostName = self::HOST_NAME_V_2_5): array|false
    {
        $query['units'] = $query['units']->value;
        $key = $this->getCacheKey($hostName . $uri, $query);

        return $this->getUrlCacheValue($key, fn (): array|false => $this->doGet($uri, $query, $hostName));
    }

    /**
     * Gets the cache key for the given url and query parameters.
     */
    private function getCacheKey(string $url, array $query): string
    {
        return $url . '?' . \http_build_query($query);
    }

    /**
     * Converts the given offset to a time zone.
     */
    private function offsetToTimZone(int $offset): \DateTimeZone
    {
        $hours = \intdiv($offset, 3600);
        $minutes = \abs(\intdiv($offset, 60) % 60);
        /** @phpstan-var non-empty-string $timezone */
        $timezone = \sprintf('%+03d%02d', $hours, $minutes); // @phpstan-ignore varTag.type

        return new \DateTimeZone($timezone);
    }

    /**
     * @phpstan-param array<array-key, mixed> $results
     */
    private function sortResults(array &$results): void
    {
        \uksort($results, static function (string|int $keyA, string|int $keyB) use ($results): int {
            $result = \is_array($results[$keyA]) <=> \is_array($results[$keyB]);
            if (0 !== $result) {
                return $result;
            }

            return $keyA <=> $keyB;
        });

        /** @phpstan-var array<array-key, mixed>|scalar $value */
        foreach ($results as &$value) {
            if (\is_array($value)) {
                $this->sortResults($value);
            }
        }
    }
}
