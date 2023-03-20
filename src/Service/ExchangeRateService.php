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

use App\Traits\TranslatorAwareTrait;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Intl\Currencies;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Symfony\Contracts\Service\ServiceSubscriberTrait;

/**
 * Exchange rate service.
 *
 * @see https://www.exchangerate-api.com/
 *
 * @psalm-type ExchangeRateType = array{
 *     symbol: string,
 *     name: string,
 *     numericCode: int,
 *     fractionDigits: int,
 *     roundingIncrement: int
 * }
 * @psalm-type ExchangeRateAndDateType = array{
 *     rate: float,
 *     next: int|null,
 *     update: int|null
 * }
 */
class ExchangeRateService extends AbstractHttpClientService implements ServiceSubscriberInterface
{
    use ServiceSubscriberTrait;
    use TranslatorAwareTrait;

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
     * The URI for latest exchange rates.
     */
    private const URI_LATEST = 'latest/%s';

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
    private ?int $timeout = null;

    /**
     * Constructor.
     *
     * @throws \InvalidArgumentException if the API key  is not defined, is null or empty
     */
    public function __construct(
        #[\SensitiveParameter]
        #[Autowire('%exchange_rate_key%')]
        string $key
    ) {
        parent::__construct($key);
        $this->endpoint = \sprintf(self::HOST_NAME, $key);
    }

    /**
     * {@inheritdoc}
     */
    public function getCacheTimeout(): ?int
    {
        return $this->timeout;
    }

    /**
     * Gets the latest exchange rates from the given currency code to all the other currencies supported.
     *
     * @param string $code the base currency code
     *
     * @return array<string, float> an array with the currency code as key and the currency rate as value or an empty array if an error occurs
     */
    public function getLatest(string $code): array
    {
        $url = $this->getUrl(self::URI_LATEST, $code);
        /** @psalm-var array<string, float>|null $results */
        $results = $this->getUrlCacheValue($url, fn () => $this->doGetLatest($url));

        return $results ?? [];
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
        /** @psalm-var float|null $results */
        $results = $this->getUrlCacheValue($url, fn () => $this->doGetRate($url));

        return $results ?? 0.0;
    }

    /**
     * Gets the exchange rate, last and next update dates from the base currency code to the target currency code.
     *
     * @param string $baseCode   the base currency code
     * @param string $targetCode the target currency code
     *
     * @return ?array the exchange rate, the next update and last update dates or null if an error occurs
     *
     * @psalm-return ExchangeRateAndDateType|null
     */
    public function getRateAndDates(string $baseCode, string $targetCode): ?array
    {
        $url = $this->getUrl(self::URI_RATE, $baseCode, $targetCode);
        /** @psalm-var ExchangeRateAndDateType|null $results */
        $results = $this->getUrlCacheValue($url, fn () => $this->doGetRateAndDates($url));

        return $results;
    }

    /**
     * Gets the supported currency codes.
     *
     * @return array the supported currency codes or an empty array if an error occurs
     *
     * @psalm-return array<string, ExchangeRateType>
     */
    public function getSupportedCodes(): array
    {
        $url = self::URI_CODES;
        /** @psalm-var array<string, ExchangeRateType>|null $results */
        $results = $this->getUrlCacheValue($url, fn () => $this->doGetSupportedCodes($url));

        return $results ?? [];
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultOptions(): array
    {
        return [self::BASE_URI => $this->endpoint];
    }

    /**
     * @psalm-return array<string, float>|null
     *
     * @throws \Symfony\Contracts\HttpClient\Exception\ExceptionInterface
     */
    private function doGetLatest(string $url): ?array
    {
        if (\is_array($response = $this->get($url))) {
            /** @psalm-var array<string, float>|null $rates */
            $rates = $response['conversion_rates'] ?? null;
            if (\is_array($rates)) {
                $this->timeout = $this->getDeltaTime($response);

                return $rates;
            }
        }

        return null;
    }

    /**
     * @throws \Symfony\Contracts\HttpClient\Exception\ExceptionInterface
     */
    private function doGetRate(string $url): ?float
    {
        if (\is_array($response = $this->get($url))) {
            /** @psalm-var float|null $rate */
            $rate = $response['conversion_rate'] ?? null;
            if (\is_float($rate)) {
                $this->timeout = $this->getDeltaTime($response);

                return $rate;
            }
        }

        return null;
    }

    /**
     * @throws \Symfony\Contracts\HttpClient\Exception\ExceptionInterface
     *
     * @psalm-return ExchangeRateAndDateType|null
     */
    private function doGetRateAndDates(string $url): ?array
    {
        if (\is_array($response = $this->get($url))) {
            /** @psalm-var float|null $rate */
            $rate = $response['conversion_rate'] ?? null;
            if (\is_float($rate)) {
                $this->timeout = $this->getDeltaTime($response);

                return [
                    'rate' => $rate,
                    'next' => $this->getNextTime($response),
                    'update' => $this->getUpdateTime($response),
                ];
            }
        }

        return null;
    }

    /**
     * @return array<string, ExchangeRateType>|null
     *
     * @throws \Symfony\Contracts\HttpClient\Exception\ExceptionInterface
     */
    private function doGetSupportedCodes(string $url): ?array
    {
        if (\is_array($response = $this->get($url))) {
            /** @psalm-var string[]|null $codes */
            $codes = $response['supported_codes'] ?? null;
            if (\is_array($codes)) {
                $results = $this->mapCodes($codes);
                $this->timeout = $this->getDeltaTime($response);

                return $results;
            }
        }

        return null;
    }

    /**
     * Make an HTTP-GET call.
     *
     * @throws \Symfony\Contracts\HttpClient\Exception\ExceptionInterface
     */
    private function get(string $url): ?array
    {
        try {
            /** @psalm-var array<string, string> $result */
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
        return \sprintf($uri, ...\array_map('strtoupper', $parameters));
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
        /** @psalm-var string[] $codes */
        $codes = \array_filter(\array_column($codes, 0), Currencies::exists(...));
        /** @psalm-var array<string, ExchangeRateType> $result */
        $result = \array_reduce($codes, function (array $carry, string $code): array {
            $carry[$code] = $this->mapCode($code);

            return $carry;
        }, []);
        \uasort($result, fn (array $a, array $b) => $a['name'] <=> $b['name']);

        return $result;
    }

    private function translateError(string $id): string
    {
        return $this->trans($id, [], 'exchange_rate');
    }
}
