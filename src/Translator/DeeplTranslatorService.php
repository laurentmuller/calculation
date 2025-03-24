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
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Intl\Languages;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * DeepL translator service v2.0.
 *
 * @see https://developers.deepl.com/docs
 *
 * @psalm-type TranslationType = array{detected_source_language: string, text: string}
 */
class DeeplTranslatorService extends AbstractTranslatorService
{
    /**
     * The host name.
     */
    private const HOST_NAME = 'https://api-free.deepl.com/v2/';

    /**
     * The languages URI.
     */
    private const URI_LANGUAGE = 'languages?type=target';

    /**
     * The translation URI.
     */
    private const URI_TRANSLATE = 'translate';

    /**
     * @throws \InvalidArgumentException if the API key is not defined, is null or is empty
     */
    public function __construct(
        #[\SensitiveParameter]
        #[Autowire('%deepl_translator_key%')]
        string $key,
        CacheInterface $cache,
        LoggerInterface $logger
    ) {
        parent::__construct($key, $cache, $logger);
    }

    #[\Override]
    public function detect(string $text): array|false
    {
        return false;
    }

    #[\Override]
    public static function getApiUrl(): string
    {
        return 'https://developers.deepl.com/docs';
    }

    #[\Override]
    public function getName(): string
    {
        return 'DeepL';
    }

    /**
     * @throws ExceptionInterface
     */
    #[\Override]
    public function translate(TranslateQuery $query): array|false
    {
        $params = [
            'source_lang' => $query->from,
            'target_lang' => $query->to,
            'text' => [$query->text],
        ];

        $response = $this->requestPost(self::URI_TRANSLATE, [self::JSON => $params]);
        if (Response::HTTP_OK !== $response->getStatusCode()) {
            return $this->parseError($response);
        }

        $values = $response->toArray();
        $text = $this->getValue($values, '[translations][0][text]');
        if (!\is_string($text)) {
            return false;
        }
        if ('' === $query->from) {
            /** @psalm-var mixed $from */
            $from = $this->getValue($values, '[translations][0][detected_source_language]', false);
            if (\is_string($from)) {
                $query->from = \strtolower($from);
            }
        }

        return $this->createTranslateResults($query, $text);
    }

    #[\Override]
    protected function getDefaultOptions(): array
    {
        return [
            self::BASE_URI => self::HOST_NAME,
            self::HEADERS => [
                'Authorization' => \sprintf('DeepL-Auth-Key %s', $this->key),
            ],
        ];
    }

    /**
     * @throws ExceptionInterface
     */
    #[\Override]
    protected function loadLanguages(): array|false
    {
        $response = $this->requestPost(self::URI_LANGUAGE);
        if (Response::HTTP_OK !== $response->getStatusCode()) {
            return $this->parseError($response);
        }

        /** @var array<array{laguage: string}> $values */
        $values = $response->toArray();
        if ([] === $values) {
            return false;
        }

        $languages = $this->cleanLanguages($values);
        if ([] === $languages) {
            return false;
        }
        if (!\in_array('en', $languages, true)) {
            $languages[] = 'en';
        }

        $result = [];
        foreach ($languages as $language) {
            $result[$this->getLanguageName($language)] = $language;
        }
        \ksort($result);

        return $result;
    }

    /**
     * @param array<array{laguage: string}> $values
     *
     * @return string[]
     */
    private function cleanLanguages(array $values): array
    {
        /** @psalm-var string[] $languages */
        $languages = \array_column($values, 'language');
        $languages = \array_map(strtolower(...), $languages);

        return \array_filter($languages, fn (string $language): bool => Languages::exists($language));
    }

    private function getLanguageName(string $language): string
    {
        return \ucfirst(Languages::getName($language));
    }

    /**
     * @throws ExceptionInterface
     */
    private function parseError(ResponseInterface $response): false
    {
        $content = $response->getContent(false);
        if ('' === $content) {
            return false;
        }
        $values = \json_decode($content, true);
        if ([] === $values || !isset($values['message'])) {
            return false;
        }

        return $this->setLastError($response->getStatusCode(), (string) $values['message']);
    }
}
