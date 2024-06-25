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
use phpDocumentor\Reflection\DocBlock\Tags\See;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Cache\CacheInterface;

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
     * The host name.
     */
    private const HOST_NAME = 'https://api.openweathermap.org/data/2.5/';

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
     * @param int    $id    the city identifier
     * @param int    $count the number of results to return or -1 for all
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
     * Returns the current conditions data for a specific location.
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
     * @param int    $count the number of results to return or -1 for all
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
     * @param int    $count the number of results to return or -1 for all
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
     * @param int[]  $cityIds the city identifiers. The maximum number is 20.
     * @param string $units   the units to use
     *
     * @return array|false the conditions for the given cities if success; false on error
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

        /** @psalm-var  OpenWeatherGroupType|false */
        return $this->get(self::URI_GROUP, $query);
    }

    /**
     * Returns all essential weather data for a specific location.
     *
     * @param float  $latitude   the latitude
     * @param float  $longitude  the longitude
     * @param string ...$exclude the parts to exclude from the response. Available values:
     *                           <ul>
     *                           <li>'current'</li>
     *                           <li>'minutely'</li>
     *                           <li>'hourly'</li>
     *                           <li>'daily'</li>
     *                           </ul>
     * @param string $units      the units to use
     *
     * @return array|false the essential conditions if success; false on error
     *
     * @psalm-param self::EXCLUDE_* ...$exclude
     */
    public function oneCall(float $latitude, float $longitude, string $units = self::UNIT_METRIC, string ...$exclude): array|false
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
    private function doGet(string $uri, array $query = []): array|false
    {
        $response = $this->requestGet($uri, [
            self::QUERY => $query,
        ]);

        /** @psalm-var array<string, mixed> $results */
        $results = $response->toArray(false);
        if (!$this->checkErrorCode($results)) {
            return false;
        }
        $offset = $this->findTimezone($results);
        $timezone = $this->offsetToTimZone($offset);
        $this->formatter->update($results, $timezone);
        $this->addUnits($results, (string) $query['units']);
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
        /** @psalm-var mixed $value */
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
     * @param string $uri   the uri to append to the host name
     * @param array  $query an associative array of query string values to add to the request
     *
     * @return array|false the JSON response on success, false on failure
     */
    private function get(string $uri, array $query = []): array|false
    {
        $key = $this->getCacheKey($uri, $query);

        // @phpstan-ignore return.type
        return $this->getUrlCacheValue($key, fn (): array|false => $this->doGet($uri, $query));
    }

    /**
     * Gets the cache key for the given uri and query parameters.
     */
    private function getCacheKey(string $uri, array $query): string
    {
        return $uri . '?' . \http_build_query($query);
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

    /**
     * @psalm-param array<array-key, mixed> $results
     */
    private function sortResults(array &$results): void
    {
        \uksort($results, function (string|int $keyA, string|int $keyB) use ($results): int {
            $isArrayA = \is_array($results[$keyA]);
            $isArrayB = \is_array($results[$keyB]);
            if ($isArrayA && !$isArrayB) {
                return 1;
            }
            if (!$isArrayA && $isArrayB) {
                return -1;
            }

            return $keyA <=> $keyB;
        });

        /** @psalm-var array<array-key, mixed>|scalar $value */
        foreach ($results as &$value) {
            if (\is_array($value)) {
                $this->sortResults($value);
            }
        }
    }
}
