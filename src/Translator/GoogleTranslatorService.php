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

/**
 * Google translator service v2.0.
 *
 * @see https://cloud.google.com/translate/docs/translating-text
 * @psalm-suppress PropertyNotSetInConstructor
 */
class GoogleTranslatorService extends AbstractTranslatorService
{
    /**
     * The host name.
     */
    private const HOST_NAME = 'https://translation.googleapis.com/language/translate/v2/';

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
    private const URI_TRANSLATE = '';

    /**
     * Constructor.
     *
     * @throws \InvalidArgumentException if the API key is null or empty
     */
    public function __construct(
        #[Autowire('%google_translator_key%')]
        string $key,
        #[Autowire('%kernel.debug%')]
        bool $isDebug
    ) {
        parent::__construct($isDebug, $key);
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
        $query = ['q' => $text];
        $response = $this->get(self::URI_DETECT, $query);
        if (!\is_array($response)) {
            return false;
        }

        // detections
        $detections = $this->getPropertyArray($response, 'detections');
        if (!\is_array($detections)) {
            return false;
        }

        // entries
        if (!$this->isValidArray($detections[0], 'entries')) {
            return false;
        }
        /** @var array $entries */
        $entries = $detections[0];

        // entry
        if (!$this->isValidArray($entries[0], 'detection')) {
            return false;
        }
        /** @var array $detection */
        $detection = $entries[0];

        // language
        $tag = $this->getProperty($detection, 'language');
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
        return 'https://cloud.google.com/translate/docs/translating-text';
    }

    /**
     * {@inheritdoc}
     */
    public static function getDefaultIndexName(): string
    {
        return 'Google';
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
        // query
        $query = [
            'q' => $text,
            'target' => $to,
            'source' => $from ?? '',
            'format' => $html ? 'html' : 'text',
        ];
        if (false === $response = $this->get(self::URI_TRANSLATE, $query)) {
            return false;
        }

        // translation
        if (!$target = $this->getTranslation($response)) {
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
        $query = ['target' => self::getAcceptLanguage()];
        if (false === $response = $this->get(self::URI_LANGUAGE, $query)) {
            return false;
        }

        // languages
        /** @var bool|array<array{name: string, language: string}>  $languages */
        $languages = $this->getPropertyArray($response, 'languages');
        if (!\is_array($languages)) {
            return false;
        }

        // build
        $result = [];
        foreach ($languages as $language) {
            $result[$language['name']] = $language['language'];
        }
        \ksort($result);

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
     * @throws \ReflectionException
     */
    private function detectLanguage(array $response): ?string
    {
        if (!\is_array($translations = $this->getPropertyArray($response, 'translations'))) {
            return null;
        }
        if (!\is_array($translation = $translations[0])) {
            return null;
        }
        if (!\is_string($language = $this->getProperty($translation, 'detectedSourceLanguage', false))) {
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
     * @return array|false the data response on success, false otherwise
     *
     * @throws \ReflectionException
     * @throws \Symfony\Contracts\HttpClient\Exception\ExceptionInterface
     */
    private function get(string $uri, array $query = []): array|false
    {
        // add key parameter
        $query['key'] = $this->key;

        // call
        $response = $this->requestGet($uri, [
            self::QUERY => $query,
        ]);

        // decode
        $response = $response->toArray(false);

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

        // get data
        /** @var bool|array $data */
        $data = $this->getProperty($response, 'data');
        if (!\is_array($data)) {
            return false;
        }

        return $data;
    }

    /**
     * @throws \ReflectionException
     */
    private function getTranslation(array $response): ?string
    {
        if (!\is_array($translations = $this->getPropertyArray($response, 'translations'))) {
            return null;
        }
        if (!\is_array($translation = $translations[0])) {
            return null;
        }
        if (!\is_string($target = $this->getProperty($translation, 'translatedText'))) {
            return null;
        }

        return $target;
    }
}
