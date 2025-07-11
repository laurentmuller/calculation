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

use App\Model\TranslateQuery;
use App\Utils\StringUtils;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * Microsoft BingTranslatorService Text API 3.0.
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
     * @throws \InvalidArgumentException if the API key is not defined, is null or is empty
     */
    public function __construct(
        #[\SensitiveParameter]
        #[Autowire('%bing_translator_key%')]
        string $key,
        CacheInterface $cache,
        LoggerInterface $logger
    ) {
        parent::__construct($key, $cache, $logger);
    }

    /**
     * @throws ExceptionInterface
     */
    #[\Override]
    public function detect(string $text): array|false
    {
        $json = [['Text' => $text]];
        $response = $this->call(uri: self::URI_DETECT, json: $json);
        if (false === $response) {
            return false;
        }

        /** @phpstan-var string|null $tag */
        $tag = $this->getValue($response, '[0][language]');
        if (!\is_string($tag)) {
            return false;
        }

        return [
            'tag' => $tag,
            'name' => $this->findLanguage($tag),
        ];
    }

    #[\Override]
    public static function getApiUrl(): string
    {
        return 'https://docs.microsoft.com/en-us/azure/cognitive-services/translator/translator-info-overview';
    }

    #[\Override]
    public function getName(): string
    {
        return 'Bing';
    }

    /**
     * @throws ExceptionInterface
     */
    #[\Override]
    public function translate(TranslateQuery $query): array|false
    {
        $params = [
            'from' => $query->from,
            'to' => $query->to,
            'textType' => $query->html ? 'html' : 'plain',
        ];
        $json = [['Text' => $query->text]];
        $response = $this->call(self::URI_TRANSLATE, $params, $json);
        if (false === $response) {
            return false;
        }

        /** @phpstan-var string|null $target */
        $target = $this->getValue($response, '[0][translations][0][text]');
        if (!\is_string($target)) {
            return false;
        }

        /** @phpstan-var string|null $language */
        $language = $this->getValue($response, '[0][detectedLanguage][language]', false);
        if (\is_string($language)) {
            $query->from = $language;
        }

        return $this->createTranslateResults($query, $target);
    }

    #[\Override]
    protected function getDefaultOptions(): array
    {
        return [
            self::BASE_URI => self::HOST_NAME,
            self::HEADERS => [
                'Accept-language' => self::getAcceptLanguage(),
                'Ocp-Apim-Subscription-Key' => $this->key,
            ],
            self::QUERY => [
                'api-version' => self::API_VERSION,
            ],
        ];
    }

    /**
     * @throws ExceptionInterface
     */
    #[\Override]
    protected function loadLanguages(): array|false
    {
        $query = ['scope' => 'translation'];
        $response = $this->call(uri: self::URI_LANGUAGE, query: $query, json: $query, method: Request::METHOD_GET);
        if (false === $response) {
            return false;
        }
        /** @phpstan-var array<string, array{name: string}>|null  $translation */
        $translation = $this->getValue($response, '[translation]');
        if (!\is_array($translation)) {
            return false;
        }
        $result = [];
        foreach ($translation as $key => $value) {
            $result[$value['name']] = $key;
        }
        \ksort($result);

        return $result;
    }

    /**
     * @phpstan-param Request::METHOD_* $method
     *
     * @throws ExceptionInterface
     */
    private function call(string $uri, array $query = [], array $json = [], string $method = Request::METHOD_POST): array|false
    {
        $response = $this->request($method, $uri, [
            self::JSON => $json,
            self::QUERY => $query,
        ]);
        $values = $this->checkResponse($response);
        if (!\is_array($values)) {
            return false;
        }

        return $values;
    }

    /**
     * @throws ExceptionInterface
     */
    private function checkResponse(ResponseInterface $response): array|false
    {
        if (Response::HTTP_OK !== $response->getStatusCode()) {
            $content = $response->getContent(false);
            $value = StringUtils::decodeJson($content);
        } else {
            $value = $response->toArray(false);
        }
        if (!$this->handleError($value)) {
            return false;
        }

        return $value;
    }
}
