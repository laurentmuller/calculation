<?php
/*
 * This file is part of the Calculation package.
 *
 * (c) bibi.nu. <bibi@bibi.nu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace App\Service;

use App\Traits\TranslatorTrait;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\DependencyInjection\Exception\ParameterNotFoundException;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Intl\Currencies;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Exchange rate service.
 *
 * @author Laurent Muller
 *
 * @see https://www.exchangerate-api.com/
 */
class ExchangeRateService extends AbstractHttpClientService
{
    use TranslatorTrait;

    /**
     * The host name.
     */
    private const HOST_NAME = 'https://v6.exchangerate-api.com/v6/%s/';

    /**
     * The parameter name for the API key.
     */
    private const PARAM_KEY = 'exchange_rate_key';

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
    private string $endpoint;

    /**
     * Constructor.
     *
     * @throws ParameterNotFoundException if the API key is not defined
     * @throws \InvalidArgumentException  if the API key is null or empty
     */
    public function __construct(ParameterBagInterface $params, AdapterInterface $adapter, bool $isDebug, TranslatorInterface $translator)
    {
        /** @var string $key */
        $key = $params->get(self::PARAM_KEY);
        parent::__construct($adapter, $isDebug, $key);
        $this->endpoint = \sprintf(self::HOST_NAME, $key);
        $this->translator = $translator;
    }

    /**
     * Gets the exchange rates from the given curreny code to all the other currencies supported.
     *
     * @param string $code the base curreny code
     *
     * @return array an array with the currency code as key and the currency rate as value or an empty array if an error occurs
     * @psalm-return array<string, float>
     */
    public function getLatest(string $code): array
    {
        $url = \sprintf(self::URI_LATEST, \strtoupper($code));

        if ($response = $this->getUrlCacheValue($url)) {
            return $response;
        }

        if ($response = $this->getResponse($url)) {
            $rates = (array) ($response['conversion_rates'] ?? []);
            if (!empty($rates)) {
                $time = $this->getDeltaTime($response);
                $this->setUrlCacheValue($url, $rates, $time);

                return $rates;
            }
        }

        return [];
    }

    /**
     * Gets the exchange rate from the base curreny code to the target currency code.
     *
     * @param string $baseCode   the base curreny code
     * @param string $targetCode the target curreny code
     *
     * @return float the exchange rate or 0.0 if an error occurs.
     */
    public function getRate(string $baseCode, string $targetCode): float
    {
        $url = \sprintf(self::URI_RATE, \strtoupper($baseCode), \strtoupper($targetCode));
        if ($response = $this->getUrlCacheValue($url)) {
            return $response;
        }

        if ($response = $this->getResponse($url)) {
            $rate = (float) ($response['conversion_rate'] ?? 0.0);
            if (!empty($rate)) {
                $time = $this->getDeltaTime($response);
                $this->setUrlCacheValue($url, $rate, $time);

                return $rate;
            }
        }

        return 0.0;
    }

    /**
     * Gets the exchange rate, last and next update dates from the base curreny code to the target currency code.
     *
     * @param string $baseCode   the base curreny code
     * @param string $targetCode the target curreny code
     *
     * @return array|null the exchange rate, the next update and last update dates or null if an error occurs
     */
    public function getRateAndDates(string $baseCode, string $targetCode): ?array
    {
        $url = \sprintf(self::URI_RATE, \strtoupper($baseCode), \strtoupper($targetCode));
        if ($response = $this->getUrlCacheValue($url)) {
            return $response;
        }

        if ($response = $this->getResponse($url)) {
            $rate = (float) ($response['conversion_rate'] ?? 0.0);
            if (!empty($rate)) {
                $result = [
                    'rate' => $rate,
                    'next' => $this->getNextTime($response),
                    'update' => $this->getUpdateTime($response),
                ];

                $time = $this->getDeltaTime($response);
                $this->setUrlCacheValue($url, $result, $time);

                return $result;
            }
        }

        return null;
    }

    /**
     * Gets the supported currency codes.
     *
     * @return array the supported currency codes or an empty array if an error occurs
     * @psalm-return array<string, string>
     */
    public function getSupportedCodes(): array
    {
        $url = self::URI_CODES;
        if ($response = $this->getUrlCacheValue($url)) {
            return $response;
        }

        if ($response = $this->getResponse($url)) {
            $codes = (array) ($response['supported_codes'] ?? []);
            if (!empty($codes)) {
                $codes = $this->mapCodes($codes);
                $time = $this->getDeltaTime($response);
                $this->setUrlCacheValue($url, $codes, $time);

                return $codes;
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
        $time = (int) ($response['time_next_update_unix'] ?? 0);
        if (0 !== $time) {
            $delta = $time - \time() - 1;
            if ($delta > 0) {
                return $delta;
            }
        }

        return null;
    }

    private function getNextTime(array $response): ?int
    {
        $time = (int) ($response['time_next_update_unix'] ?? 0);

        return 0 !== $time ? $time : null;
    }

    private function getResponse(string $url): ?array
    {
        try {
            $result = $this->requestGet($url)->toArray();
            if ($this->isValidResult($result)) {
                return $result;
            }
        } catch (\Exception $e) {
            $this->setLastError(404, $this->translateError('unknown'), $e);
        }

        return null;
    }

    private function getUpdateTime(array $response): ?int
    {
        $time = (int) ($response['time_last_update_unix'] ?? 0);

        return 0 !== $time ? $time : null;
    }

    private function isValidResult(array $result): bool
    {
        $result = $result['result'] ?? 'unknown';
        if (self::RESPONSE_SUCCESS === $result) {
            return true;
        }

        // error
        $error = $result['error-type'] ?? 'unknown';
        $this->setLastError(404, $this->translateError($error));

        return false;
    }

    private function mapCodes(array $codes): array
    {
        // filter
        $codes = \array_filter(\array_column($codes, 0), function (string $code): bool {
            return Currencies::exists($code);
        });

        // map
        $result = [];
        foreach ($codes as $code) {
            $result[$code] = [
                'symbol' => Currencies::getSymbol($code),
                'name' => \ucfirst(Currencies::getName($code)),
                'numericCode' => Currencies::getNumericCode($code),
                'fractionDigits' => Currencies::getFractionDigits($code),
                'roundingIncrement' => Currencies::getRoundingIncrement($code),
            ];
        }

        \uasort($result, function (array $a, array $b) {
            return $a['name'] <=> $b['name'];
        });

        return $result;
    }

    private function translateError(string $id): string
    {
        return $this->trans($id, [], 'exchangerate');
    }
}
