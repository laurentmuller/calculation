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

use App\Util\Utils;
use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * Service using the HttpClient.
 *
 * @author Laurent Muller
 *
 * @see Symfony\Component\HttpClient\HttpClient
 */
abstract class AbstractHttpClientService
{
    /**
     * The base URI parameter name.
     */
    protected const BASE_URI = 'base_uri';

    /**
     * The body parameter name.
     */
    protected const BODY = 'body';

    /**
     * The headers parameter name.
     */
    protected const HEADERS = 'headers';

    /**
     * The Json parameter name.
     */
    protected const JSON = 'json';

    /**
     * The query parameter name.
     */
    protected const QUERY = 'query';

    /**
     * The HTTP client.
     */
    protected ?HttpClientInterface $client = null;

    /**
     * The last error.
     */
    protected ?array $lastError = null;

    /**
     * Gets the language to use for user interface strings.
     *
     * @param bool $languageOnly <code>true</code> to returns the language only, <code>false</code> to returns the language and the country
     *
     * @return string the language
     */
    public static function getAcceptLanguage(bool $languageOnly = false): string
    {
        $locale = \Locale::getDefault();
        if ($languageOnly) {
            return \Locale::getPrimaryLanguage($locale);
        }

        return \Locale::canonicalize($locale);
    }

    /**
     * Gets the last error.
     *
     * @return array|null the last error with the 'code' and the 'message' and eventually the exception; null if none
     */
    public function getLastError(): ?array
    {
        return \is_array($this->lastError) ? $this->lastError : null;
    }

    /**
     * Returns a value indicating if the connection status is normal.
     *
     * @return bool true if the connection status is normal
     */
    public static function isConnected(): bool
    {
        $result = \connection_status();

        return \CONNECTION_NORMAL === $result;
    }

    /**
     * Clear the last error.
     */
    protected function clearLastError(): self
    {
        $this->lastError = null;

        return $this;
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
     * Requests an HTTP resource.
     *
     * @param string $method  the method name ('GET', 'POST')
     * @param string $url     the URL request
     * @param array  $options the additionnal options to add to the request
     *
     * @return ResponseInterface the response
     *
     * @throws TransportException when an unsupported option is passed
     */
    protected function request(string $method, string $url, array $options = []): ResponseInterface
    {
        return $this->getClient()->request($method, $url, $options);
    }

    /**
     * Requests an HTTP resource with the 'GET' method.
     *
     * @param string $url     the URL request
     * @param array  $options the additionnal options to add to the request
     *
     * @return ResponseInterface the response
     *
     * @throws TransportException when an unsupported option is passed
     */
    protected function requestGet(string $url, array $options = []): ResponseInterface
    {
        return $this->request(Request::METHOD_GET, $url, $options);
    }

    /**
     * Requests an HTTP resource with the 'POST' method.
     *
     * @param string $url     the URL request
     * @param array  $options the additionnal options to add to the request
     *
     * @return ResponseInterface the response
     *
     * @throws TransportException when an unsupported option is passed
     */
    protected function requestPost(string $url, array $options = []): ResponseInterface
    {
        return $this->request(Request::METHOD_POST, $url, $options);
    }

    /**
     * Sets the last error.
     *
     * @param int        $code    the error code
     * @param string     $message the error message
     * @param \Exception $e       the optional source exception
     *
     * @return bool this function returns always false
     */
    protected function setLastError(int $code, string $message, \Exception $e = null): bool
    {
        if (null !== $e) {
            $this->lastError = [
                'code' => $code,
                'message' => $message,
                'exception' => Utils::getExceptionContext($e),
            ];
        } else {
            $this->lastError = [
                'code' => $code,
                'message' => $message,
            ];
        }

        return false;
    }
}
