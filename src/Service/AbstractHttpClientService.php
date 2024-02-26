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
use App\Traits\CacheKeyTrait;
use App\Traits\LoggerTrait;
use Psr\Cache\CacheItemInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * Service using the HttpClient.
 */
abstract class AbstractHttpClientService
{
    use CacheKeyTrait;
    use LoggerTrait;

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
    private ?HttpClientInterface $client = null;

    /**
     * The last client error.
     */
    private ?HttpClientError $lastError = null;

    /**
     * @throws \InvalidArgumentException if the API key is null or empty
     */
    public function __construct(
        #[\SensitiveParameter]
        protected readonly string $key,
        protected readonly CacheInterface $cache,
        protected readonly LoggerInterface $logger
    ) {
        if ('' === $key) {
            throw new \InvalidArgumentException('The API key is empty.');
        }
    }

    /**
     * Gets the language to use for user interface strings.
     *
     * @param bool $languageOnly <code>true</code> to returns language only, <code>false</code> to returns language and the country (if any)
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

    abstract public function getCacheTimeout(): int;

    /**
     * Gets the last error.
     */
    public function getLastError(): ?HttpClientError
    {
        return $this->lastError;
    }

    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    /**
     * Returns if the last error is set.
     *
     * @psalm-assert-if-true HttpClientError $this->lastError
     */
    public function hasLastError(): bool
    {
        return $this->lastError instanceof HttpClientError;
    }

    /**
     * Create the HTTP client.
     *
     * @see AbstractHttpClientService::getDefaultOptions()
     */
    protected function createClient(): HttpClientInterface
    {
        $options = $this->getDefaultOptions();

        return HttpClient::create($options);
    }

    /**
     * Gets the value from this cache for the given key.
     *
     * @template T
     *
     * @param (callable():T) $callback the callback to call when value is not is the cache
     *
     * @return T
     */
    protected function getCacheValue(string $key, callable $callback): mixed
    {
        try {
            return $this->cache->get($key, function (CacheItemInterface $item) use ($callback): mixed {
                $item->expiresAfter($this->getCacheTimeout());

                return \call_user_func($callback);
            });
        } catch (\Psr\Cache\InvalidArgumentException $e) {
            $this->logException($e);

            return \call_user_func($callback);
        }
    }

    /**
     * Gets the HTTP client.
     */
    protected function getClient(): HttpClientInterface
    {
        return $this->client ??= $this->createClient();
    }

    /**
     * Gets the default requests options used to create the HTTP client.
     *
     * @return array<string, string|array> the default requests options
     *
     * @see AbstractHttpClientService::createClient()
     */
    protected function getDefaultOptions(): array
    {
        return [];
    }

    /**
     * Gets the value from this cache for the given URL.
     *
     * @template T
     *
     * @param string         $url      the URL for which to return the corresponding value
     * @param (callable():T) $callback the callback to call when value is not is the cache
     *
     * @return T the value, if found; the default otherwise
     */
    protected function getUrlCacheValue(string $url, callable $callback): mixed
    {
        $key = $this->getUrlKey($url);

        return $this->getCacheValue($key, $callback);
    }

    /**
     * Gets the cache key for the given URL.
     */
    protected function getUrlKey(string $url): string
    {
        $options = $this->getDefaultOptions();
        if (isset($options[self::BASE_URI]) && \is_string($options[self::BASE_URI])) {
            return $this->cleanKey($options[self::BASE_URI] . $url);
        }

        return $this->cleanKey($url);
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
     * @throws \Symfony\Contracts\HttpClient\Exception\ExceptionInterface when an unsupported option is passed
     *
     * @psalm-param Request::METHOD_* $method
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
     * @throws \Symfony\Contracts\HttpClient\Exception\ExceptionInterface when an unsupported option is passed
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
     * @throws \Symfony\Contracts\HttpClient\Exception\ExceptionInterface when an unsupported option is passed
     */
    protected function requestPost(string $url, array $options = []): ResponseInterface
    {
        return $this->request(Request::METHOD_POST, $url, $options);
    }

    /**
     * Sets the last error and log it.
     */
    protected function setLastError(int $code, string $message, ?\Exception $exception = null): false
    {
        $this->lastError = new HttpClientError($code, $message, $exception);
        if ($exception instanceof \Exception) {
            $this->logException($exception, $message);
        } else {
            $this->logError($message);
        }

        return false;
    }
}
