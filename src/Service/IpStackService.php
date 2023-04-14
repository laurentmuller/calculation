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
     * The cache timeout (1 hour).
     */
    private const CACHE_TIMEOUT = 3600;

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
     * @throws \InvalidArgumentException if the API key is not defined, is null or empty
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
        $url = $this->getUrl($request);

        /** @psalm-var IpStackType|null $results */
        $results = $this->getUrlCacheValue($url, fn () => $this->doGetIpInfo($url));

        return $results;
    }

    /**
     * @psalm-return IpStackType|null
     *
     * @throws \Symfony\Contracts\HttpClient\Exception\ExceptionInterface
     */
    private function doGetIpInfo(string $url): ?array
    {
        try {
            /** @psalm-var IpStackType $response */
            $response = $this->requestGet($url)->toArray();
            if ($this->isValidResponse($response)) {
                return $this->updateResponse($response);
            }
        } catch (\Exception $e) {
            $this->setLastError(404, $this->translateError('unknown'), $e);
        }

        return null;
    }

    private function getClientIp(?Request $request = null): string
    {
        if (!$request instanceof Request) {
            return self::URI_CHECK;
        }
        $clientIp = $request->getClientIp();
        if (null === $clientIp || '127.0.0.1' === $clientIp) {
            return self::URI_CHECK;
        }

        return $clientIp;
    }

    private function getUrl(?Request $request = null): string
    {
        $clientIp = $this->getClientIp($request);
        $query = \http_build_query([
            'language' => self::getAcceptLanguage(),
            'access_key' => $this->key,
            'output' => 'json',
            'hostname' => 1,
        ]);

        return \sprintf('%s%s?%s', self::HOST_NAME, $clientIp, $query);
    }

    /**
     * @psalm-param IpStackType $response
     */
    private function isValidResponse(array $response): bool
    {
        if (isset($response['error'])) {
            $code = $response['error']['code'] ?? 404;
            $type = $response['error']['type'] ?? 'unknown';

            return $this->setLastError($code, $this->translateError($type));
        }

        return true;
    }

    private function translateError(string $id): string
    {
        return $this->trans($id, [], 'ipstack');
    }

    /**
     * @psalm-param IpStackType $response
     *
     * @psalm-return IpStackType
     */
    private function updateResponse(array $response): array
    {
        if (isset($response['region_name'])) {
            $response['region_name'] = \ucfirst($response['region_name']);
        }
        $latitude = $response['latitude'] ?? null;
        $longitude = $response['longitude'] ?? null;
        if (null !== $latitude && null !== $longitude) {
            $response['latitude_dms'] = $this->service->formatLat($latitude);
            $response['longitude_dms'] = $this->service->formatLng($longitude);
            $response['position_dms'] = $this->service->formatLatLng($latitude, $longitude);
        }

        return $response;
    }
}
