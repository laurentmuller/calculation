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

use App\Traits\CacheTrait;
use App\Traits\TranslatorTrait;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\DependencyInjection\Exception\ParameterNotFoundException;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpKernel\KernelInterface;
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
    use CacheTrait;
    use TranslatorTrait;

    /**
     * The host name.
     */
    private const HOST_NAME = 'https://v6.exchangerate-api.com/v6/';

    /**
     * The parameter name for the API key.
     */
    private const PARAM_KEY = 'exchange_rate_key';

    /**
     * The success response code.
     */
    private const RESPONSE_SUCCESS = 'success';

    /**
     * The API key.
     */
    private string $key;

    /**
     * Constructor.
     *
     * @throws ParameterNotFoundException if the API key is not found
     */
    public function __construct(ParameterBagInterface $params, KernelInterface $kernel, AdapterInterface $adapter, TranslatorInterface $translator)
    {
        $this->key = $params->get(self::PARAM_KEY);
        $this->translator = $translator;
        if (!$kernel->isDebug()) {
            $this->adapter = $adapter;
        }
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
        $code = \strtoupper($code);
        $url = $this->getUrl("/latest/$code");

        if ($response = $this->getCacheValue($url)) {
            return $response;
        }

        if ($response = $this->getResponse($url)) {
            $rates = (array) ($response['conversion_rates'] ?? []);
            if (!empty($rates)) {
                $time = $this->getNextTime($response);
                $this->setCacheValue($url, $rates, $time);

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
        $sourceCode = \strtoupper($baseCode);
        $targetCode = \strtoupper($targetCode);
        $url = $this->getUrl("/pair/$sourceCode/$targetCode");

        if ($response = $this->getCacheValue($url)) {
            return $response;
        }

        if ($response = $this->getResponse($url)) {
            $rate = (float) ($response['conversion_rate'] ?? 0.0);
            if (!empty($rate)) {
                $time = $this->getNextTime($response);
                $this->setCacheValue($url, $rate, $time);

                return $rate;
            }
        }

        return 0.0;
    }

    /**
     * Gets the supported currency codes.
     *
     * @return array the supported currency codes or an empty array if an error occurs
     * @psalm-return array<string, string>
     */
    public function getSupportedCodes(): array
    {
        $url = $this->getUrl('/codes');
        if ($response = $this->getCacheValue($url)) {
            return $response;
        }

        if ($response = $this->getResponse($url)) {
            $codes = (array) ($response['supported_codes'] ?? []);
            if (!empty($codes)) {
                $codes = $this->mapCodes($codes);
                $time = $this->getNextTime($response);
                $this->setCacheValue($url, $codes, $time);

                return $codes;
            }
        }

        return [];
    }

    private function getNextTime(array $response): ?int
    {
        $nextupdate = (int) ($response['time_next_update_unix'] ?? 0);
        if (0 !== $nextupdate) {
            $delta = $nextupdate - \time() - 1;
            if ($delta > 0) {
                return $delta;
            }
        }

        return null;
    }

    private function getResponse(string $url): ?array
    {
        try {
            $response = $this->requestGet($url)->toArray();
            if ($this->validateResponse($response)) {
                return $response;
            }
        } catch (\Exception $e) {
            $this->setLastError(404, $this->translateError('unknown'), $e);
        }

        return null;
    }

    private function getUrl(string $endPoint): string
    {
        return self::HOST_NAME . $this->key . $endPoint;
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

    private function validateResponse(array $response): bool
    {
        $result = $response['result'] ?? 'unknown';
        if (self::RESPONSE_SUCCESS === $result) {
            return true;
        }

        // error
        $error = $response['error-type'] ?? 'unknown';
        $this->setLastError(404, $this->translateError($error));

        return false;
    }
}
