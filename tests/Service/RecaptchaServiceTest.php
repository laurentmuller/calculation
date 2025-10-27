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

use App\Service\RecaptchaService;
use App\Tests\TranslatorMockTrait;
use PHPUnit\Framework\TestCase;
use ReCaptcha\Response;
use Symfony\Component\HttpFoundation\Request;

final class RecaptchaServiceTest extends TestCase
{
    use TranslatorMockTrait;

    private const SITE_KEY = 'site_key';

    public function testDefaultValues(): void
    {
        $service = $this->createService();
        self::assertSame(60, $service->getChallengeTimeout());
        self::assertSame('login', $service->getExpectedAction());
        self::assertNull($service->getLastResponse());
        self::assertSame(0.5, $service->getScoreThreshold());
        self::assertSame(self::SITE_KEY, $service->getSiteKey());
    }

    public function testSetProperties(): void
    {
        $service = $this->createService();
        $service->setChallengeTimeout(120);
        self::assertSame(120, $service->getChallengeTimeout());
        $service->setExpectedAction('action');
        self::assertSame('action', $service->getExpectedAction());
        $service->setScoreThreshold(1.0);
        self::assertSame(1.0, $service->getScoreThreshold());
    }

    public function testTranslateError(): void
    {
        $service = $this->createService();
        $actual = $service->translateError('id');
        self::assertSame('recaptcha.id', $actual);
    }

    public function testTranslateErrorsEmpty(): void
    {
        $service = $this->createService();
        $actual = $service->translateErrors([]);
        self::assertSame(['recaptcha.unknown-error'], $actual);
    }

    public function testTranslateErrorsWithResponse(): void
    {
        $service = $this->createService();
        $response = new Response(false, ['id']);
        $actual = $service->translateErrors($response);
        self::assertSame(['recaptcha.id'], $actual);
    }

    public function testTranslateErrorsWithStrings(): void
    {
        $service = $this->createService();
        $actual = $service->translateErrors(['id']);
        self::assertSame(['recaptcha.id'], $actual);
    }

    public function testVerify(): void
    {
        $request = new Request();
        $service = $this->createService();
        $actual = $service->verify('fake', $request);
        self::assertFalse($actual->isSuccess());
    }

    private function createService(): RecaptchaService
    {
        $translator = $this->createMockTranslator();

        return new RecaptchaService(self::SITE_KEY, 'secretKey', $translator);
    }
}
