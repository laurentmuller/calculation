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
use App\Translator\BingTranslatorService;
use App\Utils\FormatUtils;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\JsonMockResponse;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;

class BingTranslatorServiceTest extends TestCase
{
    /**
     * @throws Exception
     * @throws ExceptionInterface
     */
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

    /**
     * @throws Exception
     * @throws ExceptionInterface
     */
    public function testDetectNotString(): void
    {
        $response = new JsonMockResponse(
            [
                'language' => [],
            ],
        );
        $translator = $this->createTranslator($response);
        $actual = $translator->detect('Bonjour');
        self::assertFalse($actual);
    }

    /**
     * @throws Exception
     * @throws ExceptionInterface
     */
    public function testDetectSuccess(): void
    {
        $response1 = new JsonMockResponse(
            [
                ['language' => 'fr'],
            ]
        );
        $response2 = $this->getLanguagesResponse();
        $translator = $this->createTranslator($response1, $response2);
        $actual = $translator->detect('Bonjour');
        self::assertIsArray($actual);
        self::assertArrayHasKey('tag', $actual);
        self::assertArrayHasKey('name', $actual);
        self::assertSame('fr', $actual['tag']);
        self::assertSame('French', $actual['name']);
    }

    /**
     * @throws Exception
     */
    public function testFindLanguageNotFound(): void
    {
        $response = $this->getLanguagesResponse();
        $translator = $this->createTranslator($response);
        $actual = $translator->findLanguage('en');
        self::assertNull($actual);
    }

    /**
     * @throws Exception
     */
    public function testFindLanguageNotLanguage(): void
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
        $actual = $translator->findLanguage('en');
        self::assertNull($actual);
    }

    /**
     * @throws Exception
     */
    public function testFindLanguageNull(): void
    {
        $translator = $this->createTranslator();
        $actual = $translator->findLanguage(null);
        self::assertNull($actual);
    }

    /**
     * @throws Exception
     */
    public function testFindLanguageSuccess(): void
    {
        $response = $this->getLanguagesResponse();
        $translator = $this->createTranslator($response);
        $actual = $translator->findLanguage('fr');
        self::assertSame('French', $actual);
    }

    public function testGetAcceptLanguage(): void
    {
        \Locale::setDefault(FormatUtils::DEFAULT_LOCALE);
        $actual = BingTranslatorService::getAcceptLanguage();
        self::assertSame('fr', $actual);
        $actual = BingTranslatorService::getAcceptLanguage(false);
        self::assertSame('fr_CH', $actual);
    }

    public function testGetApiURL(): void
    {
        $actual = BingTranslatorService::getApiUrl();
        self::assertSame('https://docs.microsoft.com/en-us/azure/cognitive-services/translator/translator-info-overview', $actual);
    }

    /**
     * @throws Exception
     * @throws \ReflectionException
     *
     * @psalm-suppress UnusedMethodCall
     */
    public function testGetDefaultOptions(): void
    {
        $client = new MockHttpClient();
        $translator = $this->createTranslator();
        $translator->setClient($client);
        $class = new \ReflectionClass($translator);
        $method = $class->getMethod('getDefaultOptions');
        $method->setAccessible(true);
        $actual = $method->invoke($translator);
        self::assertIsArray($actual);
    }

    /**
     * @throws Exception
     */
    public function testGetLanguagesInvalidArray(): void
    {
        $translator = $this->createTranslator(new JsonMockResponse());
        $actual = $translator->getLanguages();
        self::assertFalse($actual);
    }

    /**
     * @throws Exception
     */
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

    /**
     * @throws Exception
     */
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

    /**
     * @throws Exception
     */
    public function testGetLanguagesSuccess(): void
    {
        $response = $this->getLanguagesResponse();
        $translator = $this->createTranslator($response);
        $actual = $translator->getLanguages();
        self::assertSame(['French' => 'fr'], $actual);
    }

    /**
     * @throws Exception
     */
    public function testGetName(): void
    {
        $service = new BingTranslatorService(
            'apikey',
            $this->createMock(CacheInterface::class),
            $this->createMock(LoggerInterface::class),
        );
        $actual = $service->getName();
        self::assertSame('Bing', $actual);
    }

    /**
     * @throws Exception
     * @throws ExceptionInterface
     */
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

    /**
     * @throws Exception
     * @throws ExceptionInterface
     */
    public function testTranslateNotString(): void
    {
        $response = new JsonMockResponse(
            [
                'detectedLanguage' => [],
            ],
        );
        $translator = $this->createTranslator($response);
        $query = new TranslateQuery('en', 'fr', 'text');
        $actual = $translator->translate($query);
        self::assertFalse($actual);
    }

    /**
     * @throws Exception
     * @throws ExceptionInterface
     */
    public function testTranslateSuccess(): void
    {
        $response = new JsonMockResponse(
            [
                [
                    'detectedLanguage' => [
                        'language' => 'en',
                        'score' => 1.0,
                    ],
                    'translations' => [
                        [
                            'text' => 'Text',
                            'to' => 'fr',
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

    /**
     * @throws Exception
     */
    private function createTranslator(JsonMockResponse ...$responses): BingTranslatorService
    {
        $key = 'fake';
        $cache = new ArrayAdapter();
        $logger = $this->createMock(LoggerInterface::class);

        $service = new BingTranslatorService($key, $cache, $logger);
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
                'translation' => [
                    'fr' => [
                        'name' => 'French',
                        'nativeName' => 'Français',
                        'dir' => 'ltr',
                    ],
                ],
            ]
        );
    }
}
