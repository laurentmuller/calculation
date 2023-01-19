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

namespace App\Translator;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * Microsoft BingTranslatorService Text API 3.0.
 *
 * @see https://docs.microsoft.com/en-us/azure/cognitive-services/translator/translator-info-overview
 *
 * @psalm-suppress PropertyNotSetInConstructor
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
     * The detection language URI.
     */
    private const URI_DETECT = 'detect';

    /**
     * The languages URI.
     */
    private const URI_LANGUAGE = 'languages';

    /**
     * The translation URI.
     */
    private const URI_TRANSLATE = 'translate';

    /**
     * Constructor.
     *
     * @throws \InvalidArgumentException if the API key is null or empty
     */
    public function __construct(
        #[\SensitiveParameter]
        #[Autowire('%bing_translator_key%')]
        string $key
    ) {
        parent::__construct($key);
    }

    /**
     * {@inheritdoc}
     *
     * @throws \ReflectionException
     * @throws \Psr\Cache\InvalidArgumentException
     * @throws \Symfony\Contracts\HttpClient\Exception\ExceptionInterface
     */
    public function detect(string $text): array|false
    {
        // query
        $query = [['Text' => $text]];

        if (false === $response = $this->post(self::URI_DETECT, $query)) {
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
     *
     * @throws \ReflectionException
     * @throws \Psr\Cache\InvalidArgumentException
     * @throws \Symfony\Contracts\HttpClient\Exception\ExceptionInterface
     */
    public function translate(string $text, string $to, ?string $from = null, bool $html = false): array|false
    {
        // content
        $data = [['Text' => $text]];

        // query
        $query = [
            'to' => $to,
            'from' => $from ?? '',
            'textType' => $html ? 'html' : 'plain',
        ];

        // post
        if (false === $response = $this->post(self::URI_TRANSLATE, $data, $query)) {
            return false;
        }

        // translation
        if (null === $target = $this->getTranslation($response)) {
            return false;
        }

        // detect from
        if ($language = $this->detectLanguage($response)) {
            $from = $language;
        }

        return [
            'source' => $text,
            'target' => $target,
            'from' => [
                'tag' => $from ?? '',
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
     *
     * @throws \ReflectionException
     * @throws \Symfony\Contracts\HttpClient\Exception\ExceptionInterface
     */
    protected function doGetLanguages(): array|false
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
     * @@throws \Symfony\Contracts\HttpClient\Exception\ExceptionInterface
     */
    private function checkResponse(ResponseInterface $response): array|false
    {
        if (Response::HTTP_OK !== $response->getStatusCode()) {
            $content = $response->getContent(false);
            /** @psalm-var array $value */
            $value = \json_decode($content, true);
        } else {
            $value = $response->toArray(false);
        }

        // check error
        if (isset($value['error'])) {
            /**
             * @var null|array{
             *      result: bool,
             *      code: string|int,
             *      message: string,
             *      exception?: array|\Exception} $error
             */
            $error = $value['error'];
            $this->lastError = $error;

            return false;
        }

        return $value;
    }

    /**
     * @throws \ReflectionException
     */
    private function detectLanguage(mixed $response): ?string
    {
        if (!\is_array($response)) {
            return null;
        }
        if (!$this->isValidArray($response, 'response')) {
            return null;
        }
        if (!\is_array($result = $response[0])) {
            return null;
        }
        if (!\is_array($detectedLanguage = $this->getPropertyArray($result, 'detectedLanguage', false))) {
            return null;
        }
        if (!\is_string($language = $this->getProperty($detectedLanguage, 'language', false))) {
            return null;
        }

        return $language;
    }

    /**
     * Make an HTTP-GET call.
     *
     * @param string $uri   the uri to append to the host name
     * @param array  $query an associative array of query string values to add to the request
     *
     * @return array|false the HTTP response body on success, false on failure
     *
     * @throws \Symfony\Contracts\HttpClient\Exception\ExceptionInterface
     */
    private function get(string $uri, array $query = []): array|false
    {
        // add version
        $query['api-version'] = self::API_VERSION;

        // call
        $response = $this->requestGet($uri, [
            self::QUERY => $query,
        ]);

        return $this->checkResponse($response);
    }

    /**
     * @throws \ReflectionException
     */
    private function getTranslation(array $response): ?string
    {
        if (!$this->isValidArray($response, 'response')) {
            return null;
        }
        if (!\is_array($result = $response[0])) {
            return null;
        }
        if (!\is_array($translations = $this->getPropertyArray($result, 'translations'))) {
            return null;
        }
        if (!\is_array($translation = $translations[0])) {
            return null;
        }
        if (!\is_string($target = $this->getProperty($translation, 'text'))) {
            return null;
        }

        return $target;
    }

    /**
     * Make an HTTP-POST call.
     *
     * @param string $uri   the uri to append to the host name
     * @param array  $data  the JSON data
     * @param array  $query an associative array of query string values to add to the request
     *
     * @return array|false the HTTP response body on success, false on failure
     *
     * @throws \Symfony\Contracts\HttpClient\Exception\ExceptionInterface
     */
    private function post(string $uri, array $data, array $query = []): array|false
    {
        // add version
        $query['api-version'] = self::API_VERSION;

        // call
        $response = $this->requestPost($uri, [
                self::QUERY => $query,
                self::JSON => $data,
            ]);

        return $this->checkResponse($response);
    }
}
