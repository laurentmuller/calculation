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
use Symfony\Component\DependencyInjection\Exception\ParameterNotFoundException;
use Symfony\Component\Intl\Currencies;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Symfony\Contracts\Service\ServiceSubscriberTrait;

/**
 * Exchange rate service.
 *
 * @see https://www.exchangerate-api.com/
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class ExchangeRateService extends AbstractHttpClientService implements ServiceSubscriberInterface
{
    use ServiceSubscriberTrait;
    use TranslatorAwareTrait;

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
     * Constructor.
     *
     * @throws ParameterNotFoundException if the API key is not defined
     * @throws \InvalidArgumentException  if the API key is null or empty
     */
    public function __construct(
        #[Autowire('%exchange_rate_key%')]
        string $key
    ) {
        parent::__construct($key);
        $this->endpoint = \sprintf(self::HOST_NAME, $key);
    }

    /**
     * Gets the latest exchange rates from the given currency code to all the other currencies supported.
     *
     * @param string $code the base currency code
     *
     * @return array<string, float> an array with the currency code as key and the currency rate as value or an empty array if an error occurs
     *
     * @throws \ReflectionException
     * @throws \Symfony\Contracts\HttpClient\Exception\ExceptionInterface
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function getLatest(string $code): array
    {
        $url = \sprintf(self::URI_LATEST, \strtoupper($code));
        /** @var array<string, float>|null $rates */
        $rates = $this->getUrlCacheValue($url);
        if (\is_array($rates)) {
            return $rates;
        }

        if ($response = $this->getResponse($url)) {
            /** @var array<string, float>|null $rates */
            $rates = $response['conversion_rates'] ?? null;
            if (\is_array($rates)) {
                $this->saveResponse($url, $response, $rates);

                return $rates;
            }
        }

        return [];
    }

    /**
     * Gets the exchange rate from the base currency code to the target currency code.
     *
     * @param string $baseCode   the base currency code
     * @param string $targetCode the target currency code
     *
     * @return float the exchange rate or 0.0 if an error occurs.
     *
     * @throws \ReflectionException
     * @throws \Symfony\Contracts\HttpClient\Exception\ExceptionInterface
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function getRate(string $baseCode, string $targetCode): float
    {
        $url = \sprintf(self::URI_RATE, \strtoupper($baseCode), \strtoupper($targetCode));
        /** @var float|null $rate */
        $rate = $this->getUrlCacheValue($url);
        if (\is_float($rate)) {
            return $rate;
        }

        if ($response = $this->getResponse($url)) {
            /** @var float|null $rate */
            $rate = $response['conversion_rate'] ?? null;
            if (\is_float($rate)) {
                $this->saveResponse($url, $response, $rate);

                return $rate;
            }
        }

        return 0.0;
    }

    /**
     * Gets the exchange rate, last and next update dates from the base currency code to the target currency code.
     *
     * @param string $baseCode   the base currency code
     * @param string $targetCode the target currency code
     *
     * @return array{rate: float, next: int|null, update: int|null}|null the exchange rate, the next update and last update dates or null if an error occurs
     *
     * @throws \ReflectionException
     * @throws \Symfony\Contracts\HttpClient\Exception\ExceptionInterface
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function getRateAndDates(string $baseCode, string $targetCode): ?array
    {
        $url = \sprintf(self::URI_RATE, \strtoupper($baseCode), \strtoupper($targetCode));

        /** @var array{rate: float, next: int|null, update: int|null}|null $result */
        $result = $this->getUrlCacheValue($url);
        if (\is_array($result)) {
            return $result;
        }

        if ($response = $this->getResponse($url)) {
            /** @var float|null $rate */
            $rate = $response['conversion_rate'] ?? null;
            if (\is_float($rate)) {
                $result = [
                    'rate' => $rate,
                    'next' => $this->getNextTime($response),
                    'update' => $this->getUpdateTime($response),
                ];
                $this->saveResponse($url, $response, $result);

                return $result;
            }
        }

        return null;
    }

    /**
     * Gets the supported currency codes.
     *
     * @return array<string, array{symbol: string, name: string, numericCode: int, fractionDigits: int, roundingIncrement: int}> the supported currency codes or an empty array if an error occurs
     *
     * @throws \ReflectionException
     * @throws \Symfony\Contracts\HttpClient\Exception\ExceptionInterface
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function getSupportedCodes(): array
    {
        $url = self::URI_CODES;

        /** @var array<string, array{symbol: string, name: string, numericCode: int, fractionDigits: int, roundingIncrement: int}>|null $result */
        $result = $this->getUrlCacheValue($url);
        if (\is_array($result)) {
            return $result;
        }

        if ($response = $this->getResponse($url)) {
            /** @var string[]|null $codes */
            $codes = $response['supported_codes'] ?? null;
            if (\is_array($codes)) {
                $result = $this->mapCodes($codes);
                $this->saveResponse($url, $response, $result);

                return $result;
            }
        }

        return [];
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultOptions(): array
    {
        return [self::BASE_URI => $this->endpoint];
    }

    private function getDeltaTime(array $response): ?int
    {
        $time = $this->getNextTime($response);
        if (null !== $time) {
            $delta = $time - \time() - 1;
            if ($delta > 0) {
                return $delta;
            }
        }

        return null;
    }

    private function getNextTime(array $response): ?int
    {
        return $this->getTime($response, 'time_next_update_unix');
    }

    /**
     * @throws \ReflectionException
     * @throws \Symfony\Contracts\HttpClient\Exception\ExceptionInterface
     */
    private function getResponse(string $url): ?array
    {
        try {
            /** @var array<string, string> $result */
            $result = $this->requestGet($url)->toArray();
            if ($this->isValidResult($result)) {
                return $result;
            }
        } catch (\Exception $e) {
            $this->setLastError(404, $this->translateError('unknown'), $e);
        }

        return null;
    }

    private function getTime(array $response, string $key): ?int
    {
        $time = (int) ($response[$key] ?? 0);

        return 0 !== $time ? $time : null;
    }

    private function getUpdateTime(array $response): ?int
    {
        return $this->getTime($response, 'time_last_update_unix');
    }

    /**
     * @param array<string, string> $response
     *
     * @throws \ReflectionException
     */
    private function isValidResult(array $response): bool
    {
        // result
        $result = $response['result'] ?? 'unknown';
        if (self::RESPONSE_SUCCESS === $result) {
            return true;
        }

        // error
        $error = $response['error-type'] ?? 'unknown';
        $this->setLastError(404, $this->translateError($error));

        return false;
    }

    /**
     * Map a currency code.
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
     * @return array<string, array{symbol: string, name: string, numericCode: int, fractionDigits: int, roundingIncrement: int}>
     */
    private function mapCodes(array $codes): array
    {
        /** @var string[] $codes */
        $codes = \array_filter(\array_column($codes, 0), fn (string $code): bool => Currencies::exists($code));

        /** @var array<string, array{symbol: string, name: string, numericCode: int, fractionDigits: int, roundingIncrement: int}> $result */
        $result = \array_reduce($codes, function (array $carry, string $code): array {
            $carry[$code] = $this->mapCode($code);

            return $carry;
        }, []);

        \uasort($result, fn (array $a, array $b) => $a['name'] <=> $b['name']);

        return $result;
    }

    /**
     * @throws \ReflectionException
     * @throws \Psr\Cache\InvalidArgumentException
     */
    private function saveResponse(string $url, array $response, mixed $value): void
    {
        $time = $this->getDeltaTime($response);
        $this->setUrlCacheValue($url, $value, $time);
    }

    private function translateError(string $id): string
    {
        return $this->trans($id, [], 'exchangerate');
    }
}
