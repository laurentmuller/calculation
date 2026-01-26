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
use App\Traits\ClosureSortTrait;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * Service to get weather from OpenWeatherMap.
 *
 * @see https://openweathermap.org/api
 *
 * @phpstan-type OpenWeatherGroupType = array{units: array, list: array<int, array>}
 */
class OpenWeatherService extends AbstractHttpClientService
{
    use ClosureSortTrait;

    /**
     * The number of daily results to return.
     */
    public const int DEFAULT_COUNT = 5;

    /**
     * The number of search results to return.
     */
    public const int DEFAULT_LIMIT = 15;

    /**
     * The parameter value to exclude the current data.
     */
    public const string EXCLUDE_CURRENT = 'current';

    /**
     * The parameter value to exclude the daily data.
     */
    public const string EXCLUDE_DAILY = 'daily';

    /**
     * The parameter value to exclude the hourly data.
     */
    public const string EXCLUDE_HOURLY = 'hourly';

    /**
     * The parameter value to exclude the minutely data.
     */
    public const string EXCLUDE_MINUTELY = 'minutely';

    /**
     * The maximum number of city identifiers to retrieve.
     */
    public const int MAX_GROUP = 20;

    /**
     * The count parameter name.
     */
    public const string PARAM_COUNT = 'cnt';

    /**
     * The excluded parameter name.
     */
    public const string PARAM_EXCLUDE = 'exclude';

    /**
     * The city identifier parameter name.
     */
    public const string PARAM_ID = 'id';

    /**
     * The latitude parameter name.
     */
    public const string PARAM_LATITUDE = 'lat';

    /**
     * The limit parameter name.
     */
    public const string PARAM_LIMIT = 'limit';

    /**
     * The latitude parameter name.
     */
    public const string PARAM_LONGITUDE = 'lon';

    /**
     * The query parameter name.
     */
    public const string PARAM_QUERY = 'query';

    /**
     * The unit's parameter name.
     */
    public const string PARAM_UNITS = 'units';

    /**
     * The cache timeout (15 minutes).
     */
    private const int CACHE_TIMEOUT = 60 * 15;

    /**
     * The host name version 2.5.
     */
    private const string HOST_NAME_V_2_5 = 'https://api.openweathermap.org/data/2.5/';

    /**
     * The host name version 3.0 (used for one Api call).
     */
    private const string HOST_NAME_V_3_0 = 'https://api.openweathermap.org/data/3.0/';

    /**
     * Current condition URI.
     */
    private const string URI_CURRENT = 'weather';

    /**
     * The 16 days / daily forecast URI.
     */
    private const string URI_DAILY = 'forecast/daily';

    /**
     * The 5 days / 3 hours forecast URI.
     */
    private const string URI_FORECAST = 'forecast';

    /**
     * One call condition URI.
     */
    private const string URI_ONECALL = 'onecall';

    /**
     * The user data parameter name.
     */
    private const string USER_DATA = 'user_data';

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
        $key = \sprintf('all-%d-%d-%s', $id, $count, $units->value);

        return $this->getCacheValue($key, fn (): array => $this->doGetAll($id, $count, $units));
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
        $key = \sprintf('current-%d-%s', $id, $units->value);

        return $this->getCacheValue($key, fn (): array|false => $this->doGetCurrent($id, $units));
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
        $key = \sprintf('daily-%d-%d-%s', $id, $count, $units->value);

        return $this->getCacheValue($key, fn (): array|false => $this->doGetDaily($id, $count, $units));
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
        $key = \sprintf('forecast-%d-%d-%s', $id, $count, $units->value);

        return $this->getCacheValue($key, fn (): array|false => $this->doGetForecast($id, $count, $units));
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
     *
     * @throws \InvalidArgumentException if the number of cities is greater than 20
     */
    public function group(array $cityIds, OpenWeatherUnits $units = OpenWeatherUnits::METRIC): array|false
    {
        $count = \count($cityIds);
        if ($count > self::MAX_GROUP) {
            throw new \InvalidArgumentException(\sprintf('Allowed cities: %d, %d given.', self::MAX_GROUP, $count));
        }

        $key = 'group-' . \implode('-', $cityIds);

        return $this->getCacheValue($key, fn (): array|false => $this->doGetGroup($cityIds, $units));
    }

    /**
     * Returns all essential weather data for a specific location.
     *
     * @param float            $latitude   the latitude
     * @param float            $longitude  the longitude
     * @param string           ...$exclude the parts to exclude from the response. One of the EXCLUDE_* constants.
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
        $key = \sprintf('oneCall-%f-%f-%s-%s', $latitude, $longitude, $units->value, \implode(',', $exclude));

        return $this->getCacheValue(
            $key,
            fn (): array|false => $this->doGetOneCall($latitude, $longitude, $units, ...$exclude)
        );
    }

    #[\Override]
    protected function getDefaultOptions(): array
    {
        return [
            self::BASE_URI => self::HOST_NAME_V_2_5,
            self::QUERY => [
                'appid' => $this->key,
                'lang' => self::getAcceptLanguage(),
            ],
        ];
    }

    /**
     * Checks if the response contains an error.
     */
    private function checkErrorCode(array $result): bool
    {
        $code = (int) ($result['cod'] ?? Response::HTTP_OK);
        if (Response::HTTP_OK === $code) {
            return true;
        }

        return $this->setLastError($code, (string) $result['message']);
    }

    /**
     * Returns the current, the hourly and daily forecasts conditions data for a specific location.
     *
     * @phpstan-return array{current: array|false, forecast: array|false, daily: array|false}
     */
    private function doGetAll(
        int $id,
        int $count = self::DEFAULT_COUNT,
        OpenWeatherUnits $units = OpenWeatherUnits::METRIC
    ): array {
        $responses = [
            $this->getRequestCurrent($id, $units),
            $this->getRequestForecast($id, $count, $units),
            $this->getRequestDaily($id, $count, $units),
        ];

        $results = [];
        foreach ($this->getClient()->stream($responses) as $response => $chunk) {
            $key = (string) $response->getInfo(self::USER_DATA);
            if ($chunk->isFirst() && Response::HTTP_OK !== $response->getStatusCode()) {
                $results[$key] = false;
                continue;
            }
            if ($chunk->isLast()) {
                $results[$key] = $this->parseResponse($response, $units);
            }
        }

        /** @phpstan-var array{current: array|false, forecast: array|false, daily: array|false} $results */
        return $results;
    }

    private function doGetCurrent(int $id, OpenWeatherUnits $units = OpenWeatherUnits::METRIC): array|false
    {
        $response = $this->getRequestCurrent($id, $units);

        return $this->parseResponse($response, $units);
    }

    private function doGetDaily(
        int $id,
        int $count = self::DEFAULT_COUNT,
        OpenWeatherUnits $units = OpenWeatherUnits::METRIC
    ): array|false {
        $response = $this->getRequestDaily($id, $count, $units);

        return $this->parseResponse($response, $units);
    }

    private function doGetForecast(
        int $id,
        int $count = self::DEFAULT_COUNT,
        OpenWeatherUnits $units = OpenWeatherUnits::METRIC
    ): array|false {
        $response = $this->getRequestForecast($id, $count, $units);

        return $this->parseResponse($response, $units);
    }

    /**
     * @param int[] $cityIds
     *
     * @phpstan-return OpenWeatherGroupType|false
     */
    private function doGetGroup(array $cityIds, OpenWeatherUnits $units): array|false
    {
        $responses = \array_map(
            fn (int $cityId): ResponseInterface => $this->getRequestCurrent($cityId, $units),
            $cityIds
        );

        $list = [];
        $client = $this->getClient();
        foreach ($client->stream($responses) as $response => $chunk) {
            if ($chunk->isFirst() && Response::HTTP_OK !== $response->getStatusCode()) {
                return false;
            }
            if ($chunk->isLast()) {
                $value = $this->parseResponse($response, $units);
                if (false === $value) {
                    return false;
                }
                $list[] = $value;
            }
        }

        return [
            'units' => $units->attributes(),
            'list' => $list,
        ];
    }

    private function doGetOneCall(
        float $latitude,
        float $longitude,
        OpenWeatherUnits $units = OpenWeatherUnits::METRIC,
        string ...$exclude
    ): array|false {
        $response = $this->getRequestOneCall($latitude, $longitude, $units, ...$exclude);

        return $this->parseResponse($response, $units);
    }

    /**
     * Finds the time zone (shift in seconds from UTC).
     *
     * @param array<string, mixed> $data the data search in
     *
     * @return int the offset, if found; 0 otherwise
     */
    private function findTimezoneOffset(array $data): int
    {
        foreach ($data as $key => $value) {
            if ('timezone' === $key) {
                return (int) $value;
            } elseif (\is_array($value)) {
                $timeZone = $this->findTimezoneOffset($value);
                if (0 !== $timeZone) {
                    return $timeZone;
                }
            }
        }

        return 0;
    }

    private function getRequestCurrent(
        int $id,
        OpenWeatherUnits $units = OpenWeatherUnits::METRIC
    ): ResponseInterface {
        return $this->getClient()->request(Request::METHOD_GET, self::URI_CURRENT, [
            self::USER_DATA => 'current',
            self::QUERY => [
                self::PARAM_ID => $id,
                self::PARAM_UNITS => $units->value,
            ],
        ]);
    }

    private function getRequestDaily(
        int $id,
        int $count = self::DEFAULT_COUNT,
        OpenWeatherUnits $units = OpenWeatherUnits::METRIC
    ): ResponseInterface {
        return $this->getClient()->request(Request::METHOD_GET, self::URI_DAILY, [
            self::USER_DATA => 'daily',
            self::QUERY => [
                self::PARAM_ID => $id,
                self::PARAM_COUNT => $count,
                self::PARAM_UNITS => $units->value,
            ],
        ]);
    }

    private function getRequestForecast(
        int $id,
        int $count = self::DEFAULT_COUNT,
        OpenWeatherUnits $units = OpenWeatherUnits::METRIC
    ): ResponseInterface {
        return $this->getClient()->request(Request::METHOD_GET, self::URI_FORECAST, [
            self::USER_DATA => 'forecast',
            self::QUERY => [
                self::PARAM_ID => $id,
                self::PARAM_COUNT => $count,
                self::PARAM_UNITS => $units->value,
            ],
        ]);
    }

    private function getRequestOneCall(
        float $latitude,
        float $longitude,
        OpenWeatherUnits $units = OpenWeatherUnits::METRIC,
        string ...$exclude
    ): ResponseInterface {
        return $this->getClient()->request(Request::METHOD_GET, self::URI_ONECALL, [
            self::USER_DATA => 'oneCall',
            self::BASE_URI => self::HOST_NAME_V_3_0,
            self::QUERY => [
                self::PARAM_LATITUDE => $latitude,
                self::PARAM_LONGITUDE => $longitude,
                self::PARAM_UNITS => $units->value,
                self::PARAM_EXCLUDE => \implode(',', $exclude),
            ],
        ]);
    }

    /**
     * Converts the given offset, in seconds, to a time zone.
     */
    private function offsetToTimeZone(int $offset): \DateTimeZone
    {
        return $this->getCacheValue(
            \sprintf('time_zone_%d', $offset),
            static fn (): \DateTimeZone => new \DateTimeZone(
                \sprintf(
                    '%+03d%02d',
                    \intdiv($offset, 3600),
                    \abs(\intdiv($offset, 60) % 60)
                )
            )
        );
    }

    private function parseResponse(ResponseInterface $response, OpenWeatherUnits $units): array|false
    {
        $result = $response->toArray(false);
        if (!$this->checkErrorCode($result)) {
            return false;
        }

        $offset = $this->findTimezoneOffset($result);
        $timezone = $this->offsetToTimeZone($offset);
        $this->formatter->update($result, $timezone);
        $result['units'] = $units->attributes();
        $this->sortResults($result);

        return $result;
    }

    /**
     * @phpstan-param array<array-key, mixed> $results
     */
    private function sortResults(array &$results): void
    {
        $this->sortKeysByClosures(
            $results,
            static fn (string|int $keyA, string|int $keyB): int => \is_array($results[$keyA]) <=> \is_array($results[$keyB]),
            static fn (string|int $keyA, string|int $keyB): int => $keyA <=> $keyB
        );

        foreach ($results as &$value) {
            if (\is_array($value)) {
                $this->sortResults($value);
            }
        }
    }
}
