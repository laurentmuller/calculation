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

namespace App\Translator;

use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\DependencyInjection\Exception\ParameterNotFoundException;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Microsoft BingTranslatorService Text API 3.0.
 *
 * @author Laurent Muller
 *
 * @see https://docs.microsoft.com/en-us/azure/cognitive-services/translator/translator-info-overview
 */
class BingTranslatorService extends AbstractTranslatorService
{
    /**
     * The API version parameter.
     */
    private const API_VERSION = '3.0';

    /**
     * The host name.
     */
    private const HOST_NAME = 'https://api.cognitive.microsofttranslator.com/';

    /**
     * The parameter name for the API key.
     */
    private const PARAM_KEY = 'bing_translator_key';

    /**
     * The detect URI.
     */
    private const URI_DETECT = 'detect';

    /**
     * The languages URI.
     */
    private const URI_LANGUAGE = 'languages';

    /**
     * The translate URI.
     */
    private const URI_TRANSLATE = 'translate';

    /**
     * Constructor.
     *
     * @throws ParameterNotFoundException if the API key parameter is not defined
     * @throws \InvalidArgumentException  if the API key is null or empty
     */
    public function __construct(ParameterBagInterface $params, CacheItemPoolInterface $adapter, bool $isDebug)
    {
        /** @var string $key */
        $key = $params->get(self::PARAM_KEY);
        parent::__construct($adapter, $isDebug, $key);
    }

    /**
     * {@inheritdoc}
     */
    public function detect(string $text)
    {
        // query
        $query = [['Text' => $text]];

        /** @var bool|array $response */
        $response = $this->post(self::URI_DETECT, $query);
        if (!\is_array($response)) {
            return false;
        }

        // check response
        if (!$this->isValidArray($response, 'response')) {
            return false;
        }

        // get first result
        /** @var array $result */
        $result = $response[0];

        // get language
        /** @var string|bool $tag */
        $tag = $this->getProperty($result, 'language');
        if (!\is_string($tag)) {
            return false;
        }

        return [
            'tag' => $tag,
            'name' => $this->findLanguage($tag),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public static function getApiUrl(): string
    {
        return 'https://docs.microsoft.com/en-us/azure/cognitive-services/translator/translator-info-overview';
    }

    /**
     * {@inheritdoc}
     */
    public static function getDefaultIndexName(): string
    {
        return 'Bing';
    }

    /**
     * {@inheritdoc}
     */
    public function translate(string $text, string $to, ?string $from = null, bool $html = false)
    {
        // content
        $data = [['Text' => $text]];

        // query
        $query = [
            'to' => $to,
            'from' => $from ?: '',
            'textType' => $html ? 'html' : 'plain',
        ];

        // post
        /** @var bool|array $response */
        $response = $this->post(self::URI_TRANSLATE, $data, $query);
        if (!\is_array($response)) {
            return false;
        }

        // check response
        if (!$this->isValidArray($response, 'response')) {
            return false;
        }

        // get first result
        /** @var array $result */
        $result = $response[0];

        // translations
        $translations = $this->getPropertyArray($result, 'translations');
        if (!\is_array($translations)) {
            return false;
        }

        /** @var array $translation */
        $translation = $translations[0];

        // target
        $target = $this->getProperty($translation, 'text');
        if (!\is_string($target)) {
            return false;
        }

        // detected language
        $detectedLanguage = $this->getProperty($result, 'detectedLanguage', false);
        if (!\is_array($detectedLanguage)) {
            return false;
        }

        // from
        /** @var bool|string $from */
        $from = $this->getProperty($detectedLanguage, 'language', false);
        if (!\is_string($from)) {
            return false;
        }

        return [
            'source' => $text,
            'target' => $target,
            'from' => [
                'tag' => $from,
                'name' => $this->findLanguage($from),
            ],
            'to' => [
                'tag' => $to,
                'name' => $this->findLanguage($to),
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function doGetLanguages()
    {
        // query
        $query = ['scope' => 'translation'];

        // get
        $response = $this->get(self::URI_LANGUAGE, $query);
        if (!\is_array($response)) {
            return false;
        }

        // translations
        /** @var bool|array<string, array{name: string}>  $translation */
        $translation = $this->getPropertyArray($response, 'translation');
        if (!\is_array($translation)) {
            return false;
        }

        // build
        $result = [];
        foreach ($translation as $key => $value) {
            $result[$value['name']] = $key;
        }
        \ksort($result);

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultOptions(): array
    {
        $headers = [
            'Accept-language' => self::getAcceptLanguage(),
            'Ocp-Apim-Subscription-Key' => $this->key,
        ];

        return [
            self::BASE_URI => self::HOST_NAME,
            self::HEADERS => $headers,
        ];
    }

    /**
     * Make a HTTP-GET call.
     *
     * @param string $uri   the uri to append to the host name
     * @param array  $query an associative array of query string values to add to the request
     *
     * @return mixed|bool the HTTP response body on success, false on failure
     */
    private function get(string $uri, array $query = [])
    {
        // add version
        $query['api-version'] = self::API_VERSION;

        // call
        $response = $this->requestGet($uri, [
            self::QUERY => $query,
        ]);

        // check status code
        if (Response::HTTP_OK !== $response->getStatusCode()) {
            $content = $response->getContent(false);
            /** @var array|null */
            $response = \json_decode($content, true);
        } else {
            // decode
            $response = $response->toArray(false);
        }

        // check error
        if (isset($response['error'])) {
            /**
             * @var null|array{
             *      result: bool,
             *      code: string|int,
             *      message: string,
             *      exception?: array|\Exception} $error
             */
            $error = $response['error'];
            $this->lastError = $error;

            return false;
        }

        // ok
        return $response;
    }

    /**
     * Make a HTTP-POST call.
     *
     * @param string $uri   the uri to append to the host name
     * @param array  $data  the JSON data
     * @param array  $query an associative array of query string values to add to the request
     *
     * @return mixed|bool the HTTP response body on success, false on failure
     */
    private function post(string $uri, array $data, array $query = [])
    {
        // add version
        $query['api-version'] = self::API_VERSION;

        // call
        $response = $this->requestPost($uri, [
                self::QUERY => $query,
                self::JSON => $data,
            ]);

        // check status code
        if (Response::HTTP_OK !== $response->getStatusCode()) {
            $content = $response->getContent(false);
            /** @var array|null */
            $response = \json_decode($content, true);
        } else {
            // decode
            $response = $response->toArray(false);
        }

        // check error
        if (isset($response['error'])) {
            /**
             * @var null|array{
             *      result: bool,
             *      code: string|int,
             *      message: string,
             *      exception?: array|\Exception} $error
             */
            $error = $response['error'];
            $this->lastError = $error;

            return false;
        }

        // ok
        return $response;
    }
}
