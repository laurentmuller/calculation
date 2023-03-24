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
     * @throws \InvalidArgumentException if the API key  is not defined, is null or empty
     */
    public function __construct(
        #[\SensitiveParameter]
        #[Autowire('%google_translator_key%')]
        string $key
    ) {
        parent::__construct($key);
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Symfony\Contracts\HttpClient\Exception\ExceptionInterface
     */
    public function detect(string $text): array|false
    {
        $query = ['q' => $text];
        if (!$response = $this->call(uri: self::URI_DETECT, query: $query)) {
            return false;
        }
        /** @psalm-var string|null $tag */
        $tag = $this->getValue($response, '[data][detections][0][0][language]');
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
    public static function getName(): string
    {
        return 'Google';
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Symfony\Contracts\HttpClient\Exception\ExceptionInterface
     */
    public function translate(string $text, string $to, ?string $from = null, bool $html = false): array|false
    {
        $query = [
            'q' => $text,
            'target' => $to,
            'source' => $from ?? '',
            'format' => $html ? 'html' : 'text',
        ];
        if (!$response = $this->call(uri: self::URI_TRANSLATE, query: $query)) {
            return false;
        }
        /** @psalm-var string|null $target */
        $target = $this->getValue($response, '[data][translations][0][translatedText]');
        if (!\is_string($target)) {
            return false;
        }
        /** @psalm-var string|null $language */
        $language = $this->getValue($response, '[data][translations][0][detectedSourceLanguage]', false);
        if (\is_string($language)) {
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
     */
    protected function getDefaultOptions(): array
    {
        return [
            self::BASE_URI => self::HOST_NAME,
            self::QUERY => [
                'key' => $this->key,
            ],
        ];
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Symfony\Contracts\HttpClient\Exception\ExceptionInterface
     */
    protected function loadLanguages(): array|false
    {
        $query = ['target' => self::getAcceptLanguage()];
        if (!$response = $this->call(uri: self::URI_LANGUAGE, query: $query)) {
            return false;
        }
        /** @psalm-var array<array{name: string, language: string}>|false  $languages */
        $languages = $this->getValue($response, '[data][languages]');
        if (!\is_array($languages)) {
            return false;
        }
        $result = [];
        foreach ($languages as $language) {
            $result[$language['name']] = $language['language'];
        }
        \ksort($result);

        return $result;
    }

    /**
     * @throws \Symfony\Contracts\HttpClient\Exception\ExceptionInterface
     */
    private function call(string $uri, array $query = []): array|false
    {
        $response = $this->requestGet($uri, [
            self::QUERY => $query,
        ]);
        $response = $response->toArray(false);
        if (!$this->handleError($response)) {
            return false;
        }

        return $response;
    }
}
