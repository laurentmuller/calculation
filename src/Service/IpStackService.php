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
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\DependencyInjection\Exception\ParameterNotFoundException;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Service to get IP lookup.
 *
 * @author Laurent Muller
 *
 * @see https://ipstack.com/documentation
 */
class IpStackService extends AbstractHttpClientService
{
    use CacheTrait;

    /**
     * The host name.
     */
    private const HOST_NAME = 'http://api.ipstack.com/';

    /**
     * The parameter name for the API key.
     */
    private const PARAM_KEY = 'ip_stack_key';

    /**
     * The API endpoint capable of detecting the IP address.
     */
    private const URI_CHECK = 'check';

    /**
     * The API key.
     */
    private string $key;

    /**
     * Constructor.
     *
     * @throws ParameterNotFoundException if the Ip Stack key parameter is not defined
     */
    public function __construct(ParameterBagInterface $params, KernelInterface $kernel, AdapterInterface $adapter)
    {
        $this->key = $params->get(self::PARAM_KEY);
        if (!$kernel->isDebug()) {
            $this->adapter = $adapter;
        }
    }

    /**
     * Gets the API key.
     */
    public function getApiKey(): string
    {
        return $this->key;
    }

    /**
     * Gets the IP information.
     *
     * @param Request $request the request to get client IP address
     *
     * @return array|bool the current Ip information if success; false on error
     */
    public function getIpInfo(?Request $request = null)
    {
        // request?
        if (null === $request) {
            $request = Request::createFromGlobals();
        }

        // get client Ip
        $clientIp = $request->getClientIp();
        if (null === $clientIp || '127.0.0.1' === $clientIp) {
            $clientIp = self::URI_CHECK;
        }

        // find from cache
        $key = "IpStackService.$clientIp";
        if ($result = $this->getCacheValue($key)) {
            return $result;
        }

        $query = [
            'access_key' => $this->key,
            'language' => self::getAcceptLanguage(true),
            'output' => 'json',
        ];

        // call
        $response = $this->requestGet($clientIp, [
            self::QUERY => $query,
        ]);

        // decode
        $result = $response->toArray(false);

        // check
        if (!$result = $this->checkErrorCode($result)) {
            return false;
        }

        // save to cache
        $this->setCacheValue($key, $result);

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultOptions(): array
    {
        return [self::BASE_URI => self::HOST_NAME];
    }

    /**
     * Checks if the response contains an error.
     *
     * @param array $result the response to validate
     *
     * @return array|bool the result if no error found; false if an error
     */
    private function checkErrorCode(array $result)
    {
        if (isset($result['error'])) {
            $code = (int) ($result['code'] ?? 404);
            $message = (string) ($result['type'] ?? 'unknown');

            return $this->setLastError($code, $message);
        }

        if ('' === (string) ($result['city'] ?? '')) {
            return $this->setLastError(404, 'ip_not_found');
        }

        return $result;
    }
}
