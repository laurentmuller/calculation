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

namespace App\Tests\Controller;

use App\Model\HttpClientError;
use App\Translator\TranslatorFactory;
use App\Translator\TranslatorServiceInterface;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Request;

class AjaxTranslateControllerTest extends ControllerTestCase
{
    public static function getRoutes(): \Iterator
    {
        yield ['/ajax/detect', self::ROLE_USER];
        yield ['/ajax/detect?text=hello', self::ROLE_USER];
        yield ['/ajax/languages', self::ROLE_USER];
    }

    /**
     * @throws Exception
     */
    public function testDetectException(): void
    {
        $service = $this->createMock(TranslatorServiceInterface::class);
        $service->method('detect')
            ->willThrowException(new \Exception('Fake Message'));
        $this->setFactoryService($service);

        $parameters = ['text' => 'hello'];
        $this->checkDetect($parameters);
    }

    /**
     * @throws Exception
     */
    public function testDetectValid(): void
    {
        $result = [
            'text' => 'hello',
        ];
        $service = $this->createMock(TranslatorServiceInterface::class);
        $service->method('detect')
            ->willReturn($result);
        $this->setFactoryService($service);

        $parameters = ['text' => 'hello'];
        $this->checkDetect($parameters);
    }

    /**
     * @throws Exception
     */
    public function testDetectWithError(): void
    {
        $error = new HttpClientError(400000, 'Fake Message');
        $service = $this->createMock(TranslatorServiceInterface::class);
        $service->method('detect')
            ->willReturn(false);
        $service->method('getLastError')
            ->willReturn($error);
        $service->method('getName')
            ->willReturn('Bing');
        $this->setFactoryService($service);

        $parameters = ['text' => 'hello'];
        $this->checkDetect($parameters);
    }

    /**
     * @throws Exception
     */
    public function testLanguageException(): void
    {
        $service = $this->createMock(TranslatorServiceInterface::class);
        $service->method('getLanguages')
            ->willThrowException(new \Exception('Fake Message'));
        $this->setFactoryService($service);

        $this->checkLanguages();
    }

    /**
     * @throws Exception
     */
    public function testLanguageFalse(): void
    {
        $service = $this->createMock(TranslatorServiceInterface::class);
        $service->method('getLanguages')
            ->willReturn(false);
        $this->setFactoryService($service);

        $this->checkLanguages();
    }

    /**
     * @throws Exception
     */
    public function testLanguageValid(): void
    {
        $result = [
            'text' => 'hello',
        ];
        $service = $this->createMock(TranslatorServiceInterface::class);
        $service->method('getLanguages')
            ->willReturn($result);
        $this->setFactoryService($service);

        $this->checkLanguages();
    }

    /**
     * @throws Exception
     */
    public function testLanguageWithError(): void
    {
        $error = new HttpClientError(100, 'Fake Message');
        $service = $this->createMock(TranslatorServiceInterface::class);
        $service->method('getLanguages')
            ->willReturn(false);
        $service->method('getLastError')
            ->willReturn($error);
        $this->setFactoryService($service);

        $this->checkLanguages();
    }

    public function testTranslateEmptyText(): void
    {
        $parameters = [
            'text' => '',
            'from' => '',
            'to' => '',
        ];
        $this->checkTranslate($parameters);
    }

    public function testTranslateEmptyTo(): void
    {
        $parameters = [
            'text' => 'Hello',
            'from' => 'en',
            'to' => '',
        ];
        $this->checkTranslate($parameters);
    }

    /**
     * @throws Exception
     */
    public function testTranslateException(): void
    {
        $service = $this->createMock(TranslatorServiceInterface::class);
        $service->method('translate')
            ->willThrowException(new \Exception('Fake Message'));
        $this->setFactoryService($service);

        $parameters = [
            'text' => 'Hello',
            'from' => 'en',
            'to' => 'fr',
        ];
        $this->checkTranslate($parameters);
    }

    public function testTranslateInvalidService(): void
    {
        $parameters = [
            'text' => 'Hello',
            'from' => 'en',
            'to' => 'fr',
            'service' => 'fake',
        ];
        $this->checkTranslate($parameters);
    }

    public function testTranslateSuccess(): void
    {
        $parameters = [
            'text' => 'Hello',
            'from' => 'en',
            'to' => 'fr',
        ];
        $this->checkTranslate($parameters);
    }

    /**
     * @throws Exception
     */
    public function testTranslateValid(): void
    {
        $result = [
            'source' => 'source',
            'target' => 'target',
        ];
        $service = $this->createMock(TranslatorServiceInterface::class);
        $service->method('translate')
            ->willReturn($result);
        $this->setFactoryService($service);

        $parameters = [
            'text' => 'Hello',
            'from' => 'en',
            'to' => 'fr',
        ];
        $this->checkTranslate($parameters);
    }

    /**
     * @throws Exception
     */
    public function testTranslateWithError(): void
    {
        $error = new HttpClientError(100, 'Fake Message');
        $service = $this->createMock(TranslatorServiceInterface::class);
        $service->method('translate')
            ->willReturn(false);
        $service->method('getLastError')
            ->willReturn($error);
        $this->setFactoryService($service);

        $parameters = [
            'text' => 'Hello',
            'from' => 'en',
            'to' => 'fr',
        ];
        $this->checkTranslate($parameters);
    }

    private function checkDetect(array $parameters): void
    {
        $this->checkRoute(
            '/ajax/detect',
            self::ROLE_USER,
            xmlHttpRequest: true,
            parameters: $parameters
        );
    }

    private function checkLanguages(): void
    {
        $this->checkRoute(
            '/ajax/languages',
            self::ROLE_USER,
            xmlHttpRequest: true
        );
    }

    private function checkTranslate(array $parameters): void
    {
        $this->checkRoute(
            '/ajax/translate',
            self::ROLE_USER,
            method: Request::METHOD_POST,
            xmlHttpRequest: true,
            parameters: $parameters
        );
    }

    /**
     * @throws Exception
     */
    private function setFactoryService(MockObject&TranslatorServiceInterface $service): void
    {
        $factory = $this->createMock(TranslatorFactory::class);
        $factory->method('getService')
            ->willReturn($service);
        $this->setService(TranslatorFactory::class, $factory);
    }
}
