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

namespace App\Tests\Service;

use App\Service\CaptchaImageService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

class CaptchaImageServiceTest extends TestCase
{
    private Session $session;

    #[\Override]
    protected function setUp(): void
    {
        $this->session = new Session(new MockArraySessionStorage());
    }

    public function testClear(): void
    {
        $service = $this->createService();
        $service->clear();
        self::assertNull($this->session->get('captcha_data'));
        self::assertNull($this->session->get('captcha_text'));
        self::assertNull($this->session->get('captcha_time'));
    }

    /**
     * @throws \Exception
     */
    public function testGenerateImageForce(): void
    {
        $service = $this->createService();
        $actual = $service->generateImage(true);
        self::assertIsString($actual);
        self::assertIsString($this->session->get('captcha_data'));
        self::assertIsString($this->session->get('captcha_text'));
        self::assertIsInt($this->session->get('captcha_time'));
    }

    /**
     * @throws \Exception
     */
    public function testGenerateImageNotForce(): void
    {
        $service = $this->createService();
        $actual = $service->generateImage();
        self::assertIsString($actual);
        self::assertIsString($this->session->get('captcha_data'));
        self::assertIsString($this->session->get('captcha_text'));
        self::assertIsInt($this->session->get('captcha_time'));
    }

    /**
     * @throws \Exception
     */
    public function testGenerateImageNotForceWithData(): void
    {
        $expected = 'data';
        $this->session->set('captcha_data', $expected);
        $this->session->set('captcha_time', \time() + 1_000);

        $service = $this->createService();
        $actual = $service->generateImage();
        self::assertIsString($actual);
        self::assertIsString($this->session->get('captcha_data'));
        self::assertSame($expected, $this->session->get('captcha_data'));
    }

    public function testSetTimeout(): void
    {
        $service = $this->createService();
        $service->setTimeout(60);
        $actual = $service->getTimeout();
        self::assertSame(60, $actual);
    }

    public function testValidateTimeoutError(): void
    {
        $service = $this->createService();
        $actual = $service->validateTimeout();
        self::assertFalse($actual);
    }

    public function testValidateTimeoutSuccess(): void
    {
        $this->session->set('captcha_time', \time() + 1_000);
        $service = $this->createService();
        $actual = $service->validateTimeout();
        self::assertTrue($actual);
    }

    public function testValidateTokenDataEmpty(): void
    {
        $service = $this->createService();
        $actual = $service->validateToken('');
        self::assertFalse($actual);
    }

    public function testValidateTokenSessionEmpty(): void
    {
        $service = $this->createService();
        $actual = $service->validateToken('token');
        self::assertFalse($actual);
    }

    public function testValidateTokenSuccess(): void
    {
        $token = 'token';
        $this->session->set('captcha_text', $token);
        $service = $this->createService();
        $actual = $service->validateToken($token);
        self::assertTrue($actual);
    }

    private function createRequestStack(): MockObject&RequestStack
    {
        $request = new Request();
        $request->setSession($this->session);
        $requestStack = $this->createMock(RequestStack::class);
        $requestStack->method('getCurrentRequest')
            ->willReturn($request);
        $requestStack->method('getSession')
            ->willReturn($this->session);

        return $requestStack;
    }

    private function createService(): CaptchaImageService
    {
        $font = __DIR__ . '/../../resources/fonts/captcha.ttf';
        $service = new CaptchaImageService($font);
        $service->setRequestStack($this->createRequestStack());

        return $service;
    }
}
