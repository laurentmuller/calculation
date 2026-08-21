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

use App\Controller\CaptchaController;
use App\Service\CaptchaImageService;
use App\Tests\MockStubTrait;
use App\Tests\TranslatorStubTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authorization\Voter\AuthenticatedVoter;

final class CaptchaControllerTest extends ControllerTestCase
{
    use MockStubTrait;
    use TranslatorStubTrait;

    #[\Override]
    public static function getRoutes(): \Generator
    {
        yield ['/captcha/image', AuthenticatedVoter::PUBLIC_ACCESS, Response::HTTP_OK,  Request::METHOD_GET, true];
        yield ['/captcha/image', self::ROLE_USER];
        yield ['/captcha/image', self::ROLE_ADMIN];
        yield ['/captcha/image', self::ROLE_SUPER_ADMIN];

        yield ['/captcha/validate', AuthenticatedVoter::PUBLIC_ACCESS, Response::HTTP_OK,  Request::METHOD_GET, true];
        yield ['/captcha/validate', self::ROLE_USER];
        yield ['/captcha/validate', self::ROLE_ADMIN];
        yield ['/captcha/validate', self::ROLE_SUPER_ADMIN];
    }

    /**
     * @throws \Exception
     */
    public function testInvalidImage(): void
    {
        $controller = $this->getController();
        $service = $this->createService(
            mock: false,
            generateImage: null,
            validateTimeout: false,
            validateToken: false
        );

        $response = $controller->image($service);
        $content = (string) $response->getContent();
        $actual = (array) \json_decode(json: $content, associative: true, flags: \JSON_THROW_ON_ERROR);
        self::assertFalse($actual['result']);
        self::assertSame('captcha.generate', $actual['message']);
    }

    public function testInvalidTimeout(): void
    {
        $controller = $this->getController();
        $service = $this->createService(
            mock: true,
            generateImage: null,
            validateTimeout: false,
            validateToken: false
        );
        $service->expects(self::once())
            ->method('validateTimeout')
            ->willReturn(false);

        $response = $controller->validate($service);
        $actual = $response->getContent();
        self::assertSame('"captcha.timeout"', $actual);
    }

    public function testInvalidToken(): void
    {
        $controller = $this->getController();
        $service = $this->createService(
            mock: false,
            generateImage: null,
            validateTimeout: true,
            validateToken: false
        );

        $response = $controller->validate($service);
        $actual = $response->getContent();
        self::assertSame('"captcha.invalid"', $actual);
    }

    public function testValid(): void
    {
        $controller = $this->getController();
        $service = $this->createService(
            mock: false,
            generateImage: null,
            validateTimeout: true,
            validateToken: true
        );

        $response = $controller->validate($service);
        $actual = $response->getContent();
        self::assertSame('true', $actual);
    }

    /**
     * @phpstan-return ($mock is true ? MockObject&CaptchaImageService : Stub&CaptchaImageService)
     */
    private function createService(
        bool $mock,
        ?string $generateImage,
        bool $validateTimeout,
        bool $validateToken
    ): CaptchaImageService {
        $service = $this->createMockOrStub(CaptchaImageService::class, $mock);
        $service->method('generateImage')
            ->willReturn($generateImage);
        $service->method('validateTimeout')
            ->willReturn($validateTimeout);
        $service->method('validateToken')
            ->willReturn($validateToken);

        return $service;
    }

    private function getController(): CaptchaController
    {
        $controller = $this->getService(CaptchaController::class);
        $controller->setTranslator($this->createStubTranslator());

        return $controller;
    }
}
