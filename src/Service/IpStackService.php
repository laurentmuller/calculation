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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Symfony\Contracts\Service\ServiceSubscriberTrait;

/**
 * Service to get IP lookup.
 *
 * @see https://ipstack.com/documentation
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class IpStackService extends AbstractHttpClientService implements ServiceSubscriberInterface
{
    use ServiceSubscriberTrait;
    use TranslatorAwareTrait;

    /**
     * The host name.
     */
    private const HOST_NAME = 'https://api.ipstack.com/';

    /**
     * The API endpoint for detecting the IP address.
     */
    private const URI_CHECK = 'check';

    /**
     * Constructor.
     *
     * @throws ParameterNotFoundException if the API key parameter is not defined
     * @throws \InvalidArgumentException  if the API key is null or empty
     */
    public function __construct(
        #[Autowire('%ip_stack_key%')]
        string $key
    ) {
        parent::__construct($key);
    }

    /**
     * Gets the IP information.
     *
     * @param ?Request $request the request to get client IP address or null for detecting the IP address
     *
     * @return array{
     *     city: ?string,
     *     region_name: ?string,
     *     error: ?array{code: ?string, type: ?string}
     *  }|null the current Ip information if success; null on error
     *
     * @throws \ReflectionException
     * @throws \Symfony\Contracts\HttpClient\Exception\ExceptionInterface
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function getIpInfo(?Request $request = null): ?array
    {
        $clientIp = $this->getClientIp($request);

        /**
         * @var array{
         *     city: ?string,
         *     region_name: ?string,
         *     error: ?array{code: ?string, type: ?string}
         *  }|null $result */
        $result = $this->getUrlCacheValue($clientIp);
        if (\is_array($result)) {
            return $result;
        }

        try {
            $query = [
                'output' => 'json',
                'access_key' => $this->key,
                'language' => self::getAcceptLanguage(),
            ];

            $response = $this->requestGet($clientIp, [
                self::QUERY => $query,
            ]);

            /**
             * @var array{
             *     city: ?string,
             *     region_name: ?string,
             *     error: ?array{code: ?string, type: ?string}
             * } $result
             */
            $result = $response->toArray();

            // check
            if (!$this->isValidResult($result)) {
                return null;
            }

            // update region name
            if (isset($result['region_name'])) {
                $result['region_name'] = \ucfirst($result['region_name']);
            }

            // save to cache
            $this->setUrlCacheValue($clientIp, $result);

            return $result;
        } catch (\Exception $e) {
            $this->setLastError(404, $this->translateError('unknown'), $e);
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultOptions(): array
    {
        return [self::BASE_URI => self::HOST_NAME];
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

        return $clientIp;
    }

    /**
     * @param array{
     *     city: ?string,
     *     region_name: ?string,
     *     error: ?array{code: ?string, type: ?string}
     * } $result
     *
     * @throws \ReflectionException
     */
    private function isValidResult(array $result): bool
    {
        if (isset($result['error'])) {
            $code = (int) ($result['error']['code'] ?? 404);
            $id = $result['error']['type'] ?? 'unknown';

            return $this->setLastError($code, $this->translateError($id));
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
