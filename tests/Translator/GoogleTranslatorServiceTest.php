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
use App\Translator\GoogleTranslatorService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\JsonMockResponse;
use Symfony\Contracts\Cache\CacheInterface;

final class GoogleTranslatorServiceTest extends TestCase
{
    public function testDetectFalse(): void
    {
        $response = new JsonMockResponse(
            [
                'error' => [
                    'code' => 404,
                    'message' => 'Error Message',
                ],
            ],
        );
        $translator = $this->createTranslator($response);
        $actual = $translator->detect('Bonjour');
        self::assertFalse($actual);
    }

    public function testDetectNotString(): void
    {
        $response = new JsonMockResponse(
            [
                'data' => [
                    'detections' => [],
                ],
            ]
        );
        $translator = $this->createTranslator($response);
        $actual = $translator->detect('Bonjour');
        self::assertFalse($actual);
    }

    public function testDetectSuccess(): void
    {
        $response = new JsonMockResponse(
            [
                'data' => [
                    'detections' => [
                        [
                            [
                                'language' => 'fr',
                                'confidence' => 1.0,
                            ],
                        ],
                    ],
                ],
            ]
        );
        $translator = $this->createTranslator($response, $this->getLanguagesResponse());
        $actual = $translator->detect('Bonjour');
        self::assertIsArray($actual);
        self::assertArrayHasKey('tag', $actual);
        self::assertArrayHasKey('name', $actual);
        self::assertSame('fr', $actual['tag']);
        self::assertSame('French', $actual['name']);
    }

    public function testGetApiURL(): void
    {
        $translator = $this->createTranslator();
        $actual = $translator->getApiUrl();
        self::assertSame('https://cloud.google.com/translate/docs/translating-text', $actual);
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

    public function testGetLanguagesInvalidCode(): void
    {
        $expected_code = 404;
        $expected_message = 'Error Message';
        $response = new JsonMockResponse(
            [
                'error' => [
                    'code' => $expected_code,
                    'message' => $expected_message,
                ],
            ],
            ['http_code' => $expected_code]
        );
        $translator = $this->createTranslator($response);
        $actual = $translator->getLanguages();
        self::assertFalse($actual);
        $actual = $translator->getLastError();
        self::assertNotNull($actual);
        self::assertSame($expected_code, $actual->getCode());
        self::assertSame($expected_message, $actual->getMessage());
    }

    public function testGetLanguagesInvalidMessage(): void
    {
        $expected_code = 404;
        $expected_message = 'Error Message';
        $response = new JsonMockResponse(
            [
                'error' => [
                    'code' => $expected_code,
                    'message' => $expected_message,
                ],
            ],
        );
        $translator = $this->createTranslator($response);
        $actual = $translator->getLanguages();
        self::assertFalse($actual);
        $actual = $translator->getLastError();
        self::assertNotNull($actual);
        self::assertSame($expected_code, $actual->getCode());
        self::assertSame($expected_message, $actual->getMessage());
    }

    public function testGetLanguagesSuccess(): void
    {
        $response = $this->getLanguagesResponse();
        $translator = $this->createTranslator($response);
        $actual = $translator->getLanguages();
        self::assertSame(['French' => 'fr'], $actual);
    }

    public function testGetName(): void
    {
        $service = new GoogleTranslatorService(
            'apikey',
            self::createStub(CacheInterface::class),
            self::createStub(LoggerInterface::class),
        );
        $actual = $service->getName();
        self::assertSame('Google', $actual);
    }

    public function testTranslateFalse(): void
    {
        $response = new JsonMockResponse(
            [
                'error' => [
                    'code' => 404,
                    'message' => 'Error Message',
                ],
            ],
        );
        $translator = $this->createTranslator($response);
        $query = new TranslateQuery('en', 'fr', 'text');
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
        $response = new JsonMockResponse(
            [
                'data' => [
                    'translations' => [
                        [
                            'translatedText' => 'Text',
                            'detectedSourceLanguage' => 'fr',
                        ],
                    ],
                ],
            ]
        );
        $translator = $this->createTranslator($response, $this->getLanguagesResponse());
        $query = new TranslateQuery('en', 'fr', 'text');
        $actual = $translator->translate($query);
        self::assertIsArray($actual);
    }

    private function createTranslator(JsonMockResponse ...$responses): GoogleTranslatorService
    {
        $service = new GoogleTranslatorService(
            'fake',
            new ArrayAdapter(),
            self::createStub(LoggerInterface::class)
        );
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
                'data' => [
                    'languages' => [
                        'language' => [
                            'language' => 'fr',
                            'name' => 'French',
                        ],
                    ],
                ],
            ]
        );
    }
}
