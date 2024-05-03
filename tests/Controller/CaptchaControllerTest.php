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
use App\Tests\TranslatorMockTrait;
use PHPUnit\Framework\MockObject\Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authorization\Voter\AuthenticatedVoter;

#[\PHPUnit\Framework\Attributes\CoversClass(CaptchaController::class)]
class CaptchaControllerTest extends AbstractControllerTestCase
{
    use TranslatorMockTrait;

    public static function getRoutes(): \Iterator
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
     * @throws Exception
     * @throws \Exception
     */
    public function testInvalidImage(): void
    {
        $controller = $this->getController();
        $service = $this->createMock(CaptchaImageService::class);
        $service->expects(self::any())
            ->method('generateImage')
            ->willReturn(null);

        $response = $controller->image($service);
        $content = \json_decode($response->getContent(), true, \JSON_THROW_ON_ERROR);
        self::assertFalse($content['result']);
        self::assertSame('captcha.generate', $content['message']);
    }

    /**
     * @throws Exception
     */
    public function testInvalidTimeout(): void
    {
        $controller = $this->getController();
        $service = $this->createMock(CaptchaImageService::class);
        $service->expects(self::any())
            ->method('validateTimeout')
            ->willReturn(false);

        $response = $controller->validate($service);
        self::assertSame('"captcha.timeout"', $response->getContent());
    }

    /**
     * @throws Exception
     */
    public function testInvalidToken(): void
    {
        $controller = $this->getController();
        $service = $this->createMock(CaptchaImageService::class);
        $service->expects(self::any())
            ->method('validateTimeout')
            ->willReturn(true);
        $service->expects(self::any())
            ->method('validateToken')
            ->willReturn(false);

        $response = $controller->validate($service);
        self::assertSame('"captcha.invalid"', $response->getContent());
    }

    /**
     * @throws Exception
     */
    public function testValid(): void
    {
        $controller = $this->getController();
        $service = $this->createMock(CaptchaImageService::class);
        $service->expects(self::any())
            ->method('validateTimeout')
            ->willReturn(true);
        $service->expects(self::any())
            ->method('validateToken')
            ->willReturn(true);

        $response = $controller->validate($service);
        self::assertSame('true', $response->getContent());
    }

    private function getController(): CaptchaController
    {
        $controller = $this->getService(CaptchaController::class);
        $controller->setTranslator($this->createTranslator());

        return $controller;
    }
}
