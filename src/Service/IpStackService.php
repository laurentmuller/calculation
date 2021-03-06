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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Service to get IP lookup.
 *
 * @author Laurent Muller
 *
 * @see https://ipstack.com/documentation
 */
class IpStackService extends AbstractHttpClientService
{
    use TranslatorTrait;

    /**
     * The host name.
     */
    private const HOST_NAME = 'http://api.ipstack.com/';

    /**
     * The parameter name for the API key.
     */
    private const PARAM_KEY = 'ip_stack_key';

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
    public function __construct(ParameterBagInterface $params, KernelInterface $kernel, AdapterInterface $adapter, TranslatorInterface $translator)
    {
        parent::__construct($kernel, $adapter, $params->get(self::PARAM_KEY));
        $this->translator = $translator;
    }

    /**
     * Gets the IP informations.
     *
     * @param Request $request the request to get client IP address or null for detecting the IP address
     *
     * @return array the current Ip informations if success; null on error
     */
    public function getIpInfo(?Request $request = null): ?array
    {
        $clientIp = $this->getClientIp($request);

        // find from cache
        if ($result = $this->getUrlCacheValue($clientIp)) {
            return $result;
        }

        try {
            // parameters
            $query = [
                'output' => 'json',
                'access_key' => $this->key,
                'language' => self::getAcceptLanguage(),
            ];

            // call
            $response = $this->requestGet($clientIp, [
                self::QUERY => $query,
            ]);

            // decode
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

    private function isValidResult(array $result): bool
    {
        if (isset($result['error'])) {
            $code = (int) ($result['code'] ?? 404);
            $id = (string) ($result['type'] ?? 'unknown');

            return $this->setLastError($code, $this->translateError($id));
        }

        if (empty($result['city'] ?? '')) {
            $code = (int) ($result['code'] ?? 404);
            $id = 'ip_not_found';

            return $this->setLastError($code, $this->translateError($id));
        }

        return true;
    }

    private function translateError(string $id): string
    {
        return $this->trans($id, [], 'ipstack');
    }
}
