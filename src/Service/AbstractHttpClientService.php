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

use App\Model\HttpClientError;
use App\Traits\CacheAwareTrait;
use App\Traits\LoggerAwareTrait;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Symfony\Contracts\Service\ServiceSubscriberTrait;

/**
 * Service using the HttpClient.
 */
abstract class AbstractHttpClientService implements ServiceSubscriberInterface
{
    use CacheAwareTrait;
    use LoggerAwareTrait;
    use ServiceSubscriberTrait;

    /**
     * The base URI parameter name.
     */
    final protected const BASE_URI = 'base_uri';

    /**
     * The body parameter name.
     */
    final protected const BODY = 'body';

    /**
     * The header's parameter name.
     */
    final protected const HEADERS = 'headers';

    /**
     * The Json parameter name.
     */
    final protected const JSON = 'json';

    /**
     * The query parameter name.
     */
    final protected const QUERY = 'query';

    /**
     * The HTTP client.
     */
    protected ?HttpClientInterface $client = null;

    /**
     * The last client error.
     */
    protected ?HttpClientError $lastError = null;

    /**
     * Constructor.
     *
     * @throws \InvalidArgumentException if the API key is null or empty
     */
    public function __construct(protected readonly string $key)
    {
        // check key
        if (empty($key)) {
            throw new \InvalidArgumentException('The API key is empty.');
        }
    }

    /**
     * Gets the language to use for user interface strings.
     *
     * @param bool $languageOnly <code>true</code> to returns the language only, <code>false</code> to returns the language and the country
     *
     * @return string the language
     */
    public static function getAcceptLanguage(bool $languageOnly = true): string
    {
        $locale = \Locale::getDefault();
        if ($languageOnly) {
            return \Locale::getPrimaryLanguage($locale);
        }

        return \Locale::canonicalize($locale);
    }

    /**
     * Gets the last error.
     */
    public function getLastError(): ?HttpClientError
    {
        return $this->lastError;
    }

    /**
     * Gets the HTTP client.
     *
     * @see AbstractHttpClientService::getDefaultOptions()
     */
    protected function getClient(): HttpClientInterface
    {
        if (null === $this->client) {
            $options = $this->getDefaultOptions();
            $this->client = HttpClient::create($options);
        }

        return $this->client;
    }

    /**
     * Gets the default requests options used to create the HTTP client.
     *
     * @return array the default requests options
     *
     * @see AbstractHttpClientService::getClient()
     */
    protected function getDefaultOptions(): array
    {
        return [];
    }

    /**
     * Gets the value from this cache for the given URL.
     *
     * @param string $url     The URL for which to return the corresponding value
     * @param mixed  $default The default value to return or a callable function to get the default value.
     *                        If the callable function returns a value, this value is saved to the cache.
     *
     * @return mixed the value, if found; the default otherwise
     */
    protected function getUrlCacheValue(string $url, mixed $default = null): mixed
    {
        $key = $this->getUrlKey($url);

        return $this->getCacheValue($key, $default);
    }

    /**
     * Gets the cache key for the given URL.
     */
    protected function getUrlKey(string $url): string
    {
        $options = $this->getDefaultOptions();
        $prefix = (string) ($options[self::BASE_URI] ?? '');

        return $prefix . $url;
    }

    /**
     * Requests an HTTP resource.
     *
     * @param string $method  the method name ('GET', 'POST')
     * @param string $url     the URL request
     * @param array  $options the additional options to add to the request
     *
     * @return ResponseInterface the response
     *
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface when an unsupported option is passed
     */
    protected function request(string $method, string $url, array $options = []): ResponseInterface
    {
        return $this->getClient()->request($method, $url, $options);
    }

    /**
     * Requests an HTTP resource with the 'GET' method.
     *
     * @param string $url     the URL request
     * @param array  $options the additional options to add to the request
     *
     * @return ResponseInterface the response
     *
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface when an unsupported option is passed
     */
    protected function requestGet(string $url, array $options = []): ResponseInterface
    {
        return $this->request(Request::METHOD_GET, $url, $options);
    }

    /**
     * Requests an HTTP resource with the 'POST' method.
     *
     * @param string $url     the URL request
     * @param array  $options the additional options to add to the request
     *
     * @return ResponseInterface the response
     *
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface when an unsupported option is passed
     */
    protected function requestPost(string $url, array $options = []): ResponseInterface
    {
        return $this->request(Request::METHOD_POST, $url, $options);
    }

    /**
     * Sets the last error and log it.
     *
     * @return false this function returns always false
     */
    protected function setLastError(int $code, string $message, ?\Exception $exception = null): bool
    {
        $this->lastError = new HttpClientError($code, $message, $exception);
        if (null !== $exception) {
            $this->logException($exception, $message);
        } else {
            $this->logError($message);
        }

        return false;
    }

    /**
     * Save the given value to the cache for the given URL.
     *
     * @param string                 $url   The URL for which to save the value
     * @param mixed                  $value The value to save. If null, the key item is removed from the cache.
     * @param \DateInterval|int|null $time  The period of time from the present after which the item must be considered
     *                                      expired. An integer parameter is understood to be the time in seconds until
     *                                      expiration. If null is passed, a default value (60 minutes) is used.
     *
     * @return bool true if the cache is updated
     */
    protected function setUrlCacheValue(string $url, mixed $value, \DateInterval|int $time = null): bool
    {
        $key = $this->getUrlKey($url);

        return $this->setCacheValue($key, $value, $time);
    }
}
