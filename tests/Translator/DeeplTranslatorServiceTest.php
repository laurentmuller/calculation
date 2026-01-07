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

namespace App\Tests\Translator;

use App\Model\TranslateQuery;
use App\Translator\DeeplTranslatorService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\JsonMockResponse;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Contracts\Cache\CacheInterface;

final class DeeplTranslatorServiceTest extends TestCase
{
    public function testDetectFalse(): void
    {
        $translator = $this->createTranslator();
        $actual = $translator->detect('fake');
        self::assertFalse($actual);
    }

    public function testGetApiURL(): void
    {
        $translator = $this->createTranslator();
        $actual = $translator->getApiUrl();
        self::assertSame('https://developers.deepl.com/docs', $actual);
    }

    /**
     * @throws \ReflectionException
     */
    public function testGetDefaultOptions(): void
    {
        $client = new MockHttpClient();
        $translator = $this->createTranslator();
        $translator->setClient($client);
        $class = new \ReflectionClass($translator);
        $method = $class->getMethod('getDefaultOptions');
        $actual = $method->invoke($translator);
        self::assertIsArray($actual);
    }

    public function testGetLanguagesInvalidArray(): void
    {
        $translator = $this->createTranslator(new JsonMockResponse());
        $actual = $translator->getLanguages();
        self::assertFalse($actual);
    }

    public function testGetName(): void
    {
        $service = new DeeplTranslatorService(
            'apikey',
            $this->createMock(CacheInterface::class),
            $this->createMock(LoggerInterface::class),
        );
        $actual = $service->getName();
        self::assertSame('DeepL', $actual);
    }

    public function testLoadLanguagesFalse(): void
    {
        $translator = $this->createTranslator();
        $actual = $translator->getLanguages();
        self::assertFalse($actual);
    }

    public function testLoadLanguagesInvalidCode(): void
    {
        $response = new JsonMockResponse(
            [],
            [
                'http_code' => 403,
            ]
        );

        $translator = $this->createTranslator($response);
        $actual = $translator->getLanguages();
        self::assertFalse($actual);
    }

    public function testLoadLanguagesSuccess(): void
    {
        $response = new JsonMockResponse(
            [
                [
                    'language' => 'BG',
                    'name' => 'Bulgarian',
                    'supports_formality' => false,
                ],
            ]
        );

        $translator = $this->createTranslator($response);
        $actual = $translator->getLanguages();
        self::assertIsArray($actual);
        self::assertCount(2, $actual);
    }

    public function testTranslateEmptyBody(): void
    {
        $response = new MockResponse(
            body: '',
            info: ['http_code' => 403]
        );
        $translator = $this->createTranslator($response);
        $query = new TranslateQuery('en', 'fr', 'text');
        $actual = $translator->translate($query);
        self::assertFalse($actual);
    }

    public function testTranslateFalseWithMessage(): void
    {
        $response = new JsonMockResponse(
            [
                'message' => 'Wrong endpoint.',
            ],
            [
                'http_code' => 403,
            ]
        );

        $translator = $this->createTranslator($response);
        $query = new TranslateQuery('', 'fr', 'text');
        $actual = $translator->translate($query);
        self::assertFalse($actual);
    }

    public function testTranslateFalseWithoutMessage(): void
    {
        $response = new JsonMockResponse(
            [],
            [
                'http_code' => 403,
            ]
        );

        $translator = $this->createTranslator($response);
        $query = new TranslateQuery('', 'fr', 'text');
        $actual = $translator->translate($query);
        self::assertFalse($actual);
    }

    public function testTranslateNotString(): void
    {
        $response = new JsonMockResponse(
            [
                'data' => [
                    'translations' => [
                        [],
                    ],
                ],
            ]
        );
        $translator = $this->createTranslator($response);
        $query = new TranslateQuery('en', 'fr', 'text');
        $actual = $translator->translate($query);
        self::assertFalse($actual);
    }

    public function testTranslateSuccess(): void
    {
        $response = $this->getLanguagesResponse();
        $translator = $this->createTranslator($response, $response);
        $query = new TranslateQuery('', 'fr', 'text');
        $actual = $translator->translate($query);
        self::assertIsArray($actual);
    }

    private function createTranslator(MockResponse ...$responses): DeeplTranslatorService
    {
        $key = 'fake';
        $cache = new ArrayAdapter();
        $logger = $this->createMock(LoggerInterface::class);

        $service = new DeeplTranslatorService($key, $cache, $logger);
        if ([] !== $responses) {
            $client = new MockHttpClient($responses);
            $service->setClient($client);
        }

        return $service;
    }

    private function getLanguagesResponse(): JsonMockResponse
    {
        return new JsonMockResponse(
            [
                'translations' => [
                    [
                        'text' => 'French',
                        'detected_source_language' => 'FR',
                    ],
                ],
            ]
        );
    }
}
