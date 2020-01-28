<?php
/*
 * This file is part of the Calculation package.
 *
 * Copyright (c) 2019 bibi.nu. All rights reserved.
 *
 * This computer code is protected by copyright law and international
 * treaties. Unauthorised reproduction or distribution of this code, or
 * any portion of it, may result in severe civil and criminal penalties,
 * and will be prosecuted to the maximum extent possible under the law.
 */

declare(strict_types=1);

namespace App\Service;

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
abstract class HttpClientService
{
    /**
     * The base URI parameter name.
     */
    protected const BASE_URI = 'base_uri';

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
     *
     * @var HttpClientInterface|null
     */
    protected $client;

    /**
     * The last error.
     *
     * @var array|null
     */
    protected $lastError;

    /**
     * Gets the language to use for user interface strings.
     *
     * @param bool $languageOnly true to returns the language only, false to returns the language and the country
     *
     * @return string the language
     */
    public static function getAcceptLanguage(bool $languageOnly = false): string
    {
        $locale = \str_replace('_', '-', \Locale::getDefault());
        if ($languageOnly) {
            return \explode('-', $locale)[0];
        }

        return $locale;
    }

    /**
     * Gets the last error.
     *
     * @return array|null the last error with the 'code' and the 'message' entries; null if none
     */
    public function getLastError(): ?array
    {
        return $this->lastError;
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
     * @see HttpClientService::getDefaultOptions()
     */
    protected function getClient(): HttpClientInterface
    {
        if (!$this->client) {
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
     * @see HttpClientService::getClient()
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
     * @param int    $code    the error code
     * @param string $message the error message
     *
     * @return bool this function returns always false
     */
    protected function setLastError(int $code, string $message): bool
    {
        $this->lastError = [
            'code' => $code,
            'message' => $message,
        ];

        return false;
    }
}
