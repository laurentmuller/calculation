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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Symfony\Contracts\Service\ServiceSubscriberTrait;

/**
 * Service to get IP lookup.
 *
 * @psalm-type IpStackType = array{
 *     city: ?string,
 *     region_name: ?string,
 *     latitude: ?float,
 *     latitude_dms?: string,
 *     longitude: ?float,
 *     longitude_dms?: string,
 *     position_dms?: string,
 *     error?: array{code: ?int, type: ?string, info: ?string}
 * }
 *
 * @see https://ipstack.com/documentation
 */
class IpStackService extends AbstractHttpClientService implements ServiceSubscriberInterface
{
    use ServiceSubscriberTrait;
    use TranslatorAwareTrait;

    /**
     * The cache timeout (1 minute).
     */
    private const CACHE_TIMEOUT = 60;

    /**
     * The host name.
     */
    private const HOST_NAME = 'http://api.ipstack.com/';

    /**
     * The API endpoint for detecting the IP address.
     */
    private const URI_CHECK = 'check';

    /**
     * Constructor.
     *
     * @throws \InvalidArgumentException if the API key  is not defined, is null or empty
     */
    public function __construct(
        #[\SensitiveParameter]
        #[Autowire('%ip_stack_key%')]
        string $key,
        private readonly PositionService $service
    ) {
        parent::__construct($key);
    }

    /**
     * {@inheritdoc}
     */
    public function getCacheTimeout(): int
    {
        return self::CACHE_TIMEOUT;
    }

    /**
     * Gets the IP information.
     *
     * @param ?Request $request the request to get client IP address or null for detecting the IP address
     *
     * @return IpStackType|null the current Ip information if success; null on error
     */
    public function getIpInfo(?Request $request = null): ?array
    {
        $clientIp = $this->getClientIp($request);
        /** @psalm-var IpStackType|null $results */
        $results = $this->getUrlCacheValue($clientIp, fn () => $this->doGetIpInfo($clientIp));

        return $results;
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultOptions(): array
    {
        return [self::BASE_URI => self::HOST_NAME];
    }

    /**
     * @psalm-return IpStackType|null
     *
     * @throws \Symfony\Contracts\HttpClient\Exception\ExceptionInterface
     */
    private function doGetIpInfo(string $clientIp): ?array
    {
        try {
            $query = [
                'output' => 'json',
                'access_key' => $this->key,
                'language' => self::getAcceptLanguage(),
            ];
            $response = $this->requestGet($clientIp, [
                self::QUERY => $query,
            ]);
            /** @psalm-var IpStackType $result */
            $result = $response->toArray();
            if (!$this->isValidResult($result)) {
                return null;
            }
            if (isset($result['region_name'])) {
                $result['region_name'] = \ucfirst($result['region_name']);
            }
            if (isset($result['latitude'])) {
                $result['latitude_dms'] = $this->service->formatLat($result['latitude']);
            }
            if (isset($result['longitude'])) {
                $result['longitude_dms'] = $this->service->formatLng($result['longitude']);
            }
            if (isset($result['latitude']) && isset($result['longitude'])) {
                $result['position_dms'] = $this->service->formatLatLng($result['latitude'], $result['longitude']);
            }

            return $result;
        } catch (\Exception $e) {
            $this->setLastError(404, $this->translateError('unknown'), $e);
        }

        return null;
    }

    /**
     * Gets the client IP address for the given request.
     */
    private function getClientIp(?Request $request = null): string
    {
        if (null === $request) {
            return self::URI_CHECK;
        }
        $clientIp = $request->getClientIp();
        if (null === $clientIp || '127.0.0.1' === $clientIp) {
            return self::URI_CHECK;
        }
        // for debug purpose
        $this->logDebug("Client Ip: $clientIp");

        return $clientIp;
    }

    /**
     * Returns if the given result is valid.
     *
     * @param IpStackType $result
     *
     * @return bool true if valid; false otherwise
     */
    private function isValidResult(array $result): bool
    {
        if (isset($result['error'])) {
            $code = $result['error']['code'] ?? 404;
            $type = $result['error']['type'] ?? 'unknown';

            return $this->setLastError($code, $this->translateError($type));
        }
        if (empty($result['city'] ?? null)) {
            return $this->setLastError(404, $this->translateError('ip_not_found'));
        }

        return true;
    }

    private function translateError(string $id): string
    {
        return $this->trans($id, [], 'ipstack');
    }
}
