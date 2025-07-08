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

use App\Traits\ArrayTrait;
use App\Utils\DateUtils;
use Psr\Log\LoggerInterface;
use Symfony\Component\Clock\DatePoint;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Intl\Currencies;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Exchange rate service.
 *
 * @see https://www.exchangerate-api.com/
 *
 * @phpstan-type ExchangeRateType = array{
 *     symbol: string,
 *     name: string,
 *     numericCode: int,
 *     fractionDigits: int,
 *     roundingIncrement: int}
 * @phpstan-type ExchangeRateAndDateType = array{
 *     rate: float,
 *     next: int|null,
 *     update: int|null}
 * @phpstan-type ExchangeQuotaType = array{
 *      allowed: int,
 *      remaining: int,
 *      date: DatePoint,
 *      documentation: string}
 * @phpstan-type ResponseType = array{
 *     refresh_day_of_month: int,
 *     plan_quota: int,
 *     requests_remaining: int,
 *     documentation: string}
 */
class ExchangeRateService extends AbstractHttpClientService
{
    use ArrayTrait;

    /**
     * The default cache timeout (15 minutes).
     */
    private const CACHE_TIMEOUT = 60 * 15;

    /**
     * The host name.
     */
    private const HOST_NAME = 'https://v6.exchangerate-api.com/v6/%s/';

    /**
     * The success response code.
     */
    private const RESPONSE_SUCCESS = 'success';

    /**
     * The URI for supported currency codes.
     */
    private const URI_CODES = 'codes';

    /**
     * The URI for the latest exchange rates.
     */
    private const URI_LATEST = 'latest/%s';

    /**
     * The URI for the quota.
     */
    private const URI_QUOTA = 'quota';

    /**
     * The URI for exchange rate.
     */
    private const URI_RATE = 'pair/%s/%s';

    /**
     * The base URI.
     */
    private readonly string $endpoint;

    /**
     * The cache timeout.
     */
    private int $timeout = 600;

    /**
     * @throws \InvalidArgumentException if the API key is not defined, is null or empty
     */
    public function __construct(
        #[\SensitiveParameter]
        #[Autowire('%exchange_rate_key%')]
        string $key,
        CacheInterface $cache,
        LoggerInterface $logger,
        private readonly TranslatorInterface $translator
    ) {
        parent::__construct($key, $cache, $logger);
        $this->endpoint = \sprintf(self::HOST_NAME, $key);
    }

    #[\Override]
    public function getCacheTimeout(): int
    {
        return $this->timeout;
    }

    /**
     * Gets the latest exchange rates from the given currency code to all the other currencies supported.
     *
     * @param string $code the base currency code
     *
     * @return array<string, float> an array with the currency code as the key and the currency rate as value or
     *                              an empty array if an error occurs
     */
    public function getLatest(string $code): array
    {
        $url = $this->getUrl(self::URI_LATEST, $code);

        return $this->getUrlCacheValue($url, fn (): array => $this->doGetLatest($url));
    }

    /**
     * Gets the deadline, the allowed and the remaining calls.
     *
     * @phpstan-return ExchangeQuotaType|null
     */
    public function getQuota(): ?array
    {
        $url = self::URI_QUOTA;

        return $this->getUrlCacheValue($url, fn (): ?array => $this->doGetQuota($url));
    }

    /**
     * Gets the exchange rate from the base currency code to the target currency code.
     *
     * @param string $baseCode   the base currency code
     * @param string $targetCode the target currency code
     *
     * @return float the exchange rate or 0.0 if an error occurs.
     */
    public function getRate(string $baseCode, string $targetCode): float
    {
        $url = $this->getUrl(self::URI_RATE, $baseCode, $targetCode);

        return $this->getUrlCacheValue($url, fn (): float => $this->doGetRate($url));
    }

    /**
     * Gets the exchange rate, last and next update dates from the base currency code to the target currency code.
     *
     * @param string $baseCode   the base currency code
     * @param string $targetCode the target currency code
     *
     * @return ?array the exchange rate, the next update and last update dates or null if an error occurs
     *
     * @phpstan-return ExchangeRateAndDateType|null
     */
    public function getRateAndDates(string $baseCode, string $targetCode): ?array
    {
        $url = $this->getUrl(self::URI_RATE, $baseCode, $targetCode);

        return $this->getUrlCacheValue($url, fn (): ?array => $this->doGetRateAndDates($url));
    }

    /**
     * Gets the supported currency codes.
     *
     * @return array the supported currency codes or an empty array if an error occurs
     *
     * @phpstan-return array<string, ExchangeRateType>
     */
    public function getSupportedCodes(): array
    {
        $url = self::URI_CODES;

        return $this->getUrlCacheValue($url, fn (): array => $this->doGetSupportedCodes($url));
    }

    #[\Override]
    protected function getDefaultOptions(): array
    {
        return [self::BASE_URI => $this->endpoint];
    }

    private function computeNextDate(int $day): DatePoint
    {
        $date = DateUtils::removeTime();
        $year = DateUtils::getYear($date);
        $month = DateUtils::getMonth($date);
        $date->setDate($year, $month, $day);
        if ($day < DateUtils::getDay($date)) {
            return DateUtils::add($date, '+1 month');
        }

        return $date;
    }

    /**
     * @phpstan-return array<string, float>
     *
     * @throws \Symfony\Contracts\HttpClient\Exception\ExceptionInterface
     */
    private function doGetLatest(string $url): array
    {
        $response = $this->get($url);
        if (!\is_array($response)) {
            return [];
        }
        /** @phpstan-var array<string, float>|null $rates */
        $rates = $response['conversion_rates'] ?? null;
        if (!\is_array($rates)) {
            return [];
        }
        $this->timeout = $this->getDeltaTime($response);

        return $rates;
    }

    /**
     * @phpstan-return ExchangeQuotaType|null
     *
     * @throws \Symfony\Contracts\HttpClient\Exception\ExceptionInterface
     */
    private function doGetQuota(string $url): ?array
    {
        /** @phpstan-var ResponseType|null $response */
        $response = $this->get($url);
        if (!\is_array($response)) {
            return null;
        }

        return [
            'allowed' => $response['plan_quota'],
            'remaining' => $response['requests_remaining'],
            'documentation' => $response['documentation'],
            'date' => $this->computeNextDate($response['refresh_day_of_month']),
        ];
    }

    /**
     * @throws \Symfony\Contracts\HttpClient\Exception\ExceptionInterface
     */
    private function doGetRate(string $url): float
    {
        $response = $this->get($url);
        if (!\is_array($response)) {
            return 0.0;
        }
        /** @phpstan-var float|null $rate */
        $rate = $response['conversion_rate'] ?? null;
        if (!\is_float($rate)) {
            return 0.0;
        }
        $this->timeout = $this->getDeltaTime($response);

        return $rate;
    }

    /**
     * @phpstan-return ExchangeRateAndDateType|null
     *
     * @throws \Symfony\Contracts\HttpClient\Exception\ExceptionInterface
     */
    private function doGetRateAndDates(string $url): ?array
    {
        $response = $this->get($url);
        if (!\is_array($response)) {
            return null;
        }
        /** @phpstan-var float|null $rate */
        $rate = $response['conversion_rate'] ?? null;
        if (!\is_float($rate)) {
            return null;
        }
        $this->timeout = $this->getDeltaTime($response);

        return [
            'rate' => $rate,
            'next' => $this->getNextTime($response),
            'update' => $this->getUpdateTime($response),
        ];
    }

    /**
     * @return array<string, ExchangeRateType>
     *
     * @throws \Symfony\Contracts\HttpClient\Exception\ExceptionInterface
     */
    private function doGetSupportedCodes(string $url): array
    {
        $response = $this->get($url);
        if (!\is_array($response)) {
            return [];
        }
        /** @phpstan-var string[]|null $codes */
        $codes = $response['supported_codes'] ?? null;
        if (!\is_array($codes)) {
            return [];
        }
        $this->timeout = $this->getDeltaTime($response);

        return $this->mapCodes($codes);
    }

    /**
     * Make an HTTP-GET call.
     *
     * @throws \Symfony\Contracts\HttpClient\Exception\ExceptionInterface
     */
    private function get(string $url): ?array
    {
        try {
            /** @phpstan-var array<string, string> $result */
            $result = $this->requestGet($url)->toArray();
            if ($this->isValidResult($result)) {
                return $result;
            }
        } catch (\Exception $e) {
            $this->setLastError(404, $this->translateError('unknown'), $e);
        }

        return null;
    }

    private function getDeltaTime(array $response): int
    {
        $time = $this->getNextTime($response);
        if (null !== $time) {
            $delta = $time - \time() - 1;
            if ($delta > 0) {
                return $delta;
            }
        }

        return self::CACHE_TIMEOUT;
    }

    private function getNextTime(array $response): ?int
    {
        return $this->getResponseTime($response, 'time_next_update_unix');
    }

    private function getResponseTime(array $response, string $key): ?int
    {
        $time = (int) ($response[$key] ?? 0);

        return $time > 0 ? $time : null;
    }

    private function getUpdateTime(array $response): ?int
    {
        return $this->getResponseTime($response, 'time_last_update_unix');
    }

    private function getUrl(string $uri, string ...$parameters): string
    {
        return \sprintf($uri, ...\array_map(strtoupper(...), $parameters));
    }

    /**
     * @param array<string, string> $response
     */
    private function isValidResult(array $response): bool
    {
        $result = $response['result'] ?? '';
        if (self::RESPONSE_SUCCESS === $result) {
            return true;
        }
        $error = $response['error-type'] ?? 'unknown';
        $this->setLastError(404, $this->translateError($error));

        return false;
    }

    /**
     * Map a currency code.
     *
     * @param string $code the currency code
     *
     * @return ExchangeRateType
     */
    private function mapCode(string $code): array
    {
        return [
            'symbol' => Currencies::getSymbol($code),
            'name' => \ucfirst(Currencies::getName($code)),
            'numericCode' => Currencies::getNumericCode($code),
            'fractionDigits' => Currencies::getFractionDigits($code),
            'roundingIncrement' => Currencies::getRoundingIncrement($code),
        ];
    }

    /**
     * @param string[] $codes
     *
     * @return array<string, ExchangeRateType>
     */
    private function mapCodes(array $codes): array
    {
        /** @var string[] $codes */
        $codes = $this->getColumnFilter($codes, 0, Currencies::exists(...));

        // remove XCG (Caribbean guilder)
        $key = \array_search('XCG', $codes, true);
        if (false !== $key) {
            unset($codes[$key]);
        }

        $result = $this->mapToKeyValue($codes, fn (string $code): array => [$code => $this->mapCode($code)]);
        \uasort($result, static fn (array $a, array $b): int => $a['name'] <=> $b['name']);

        return $result;
    }

    private function translateError(string $id): string
    {
        return $this->translator->trans($id, [], 'exchange_rate');
    }
}
