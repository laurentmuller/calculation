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

use App\Service\RecaptchaResponseService;
use PHPUnit\Framework\TestCase;
use ReCaptcha\Response;

class RecaptchaResponseServiceTest extends TestCase
{
    public function testFormatSuccess(): void
    {
        $response = new Response(true);
        $service = new RecaptchaResponseService();
        $actual = $service->format($response);
        self::assertStringContainsString('success', $actual);
        self::assertStringContainsString('true', $actual);
    }

    public function testFormatWithChallenge(): void
    {
        $response = new Response(true, challengeTs: '2024-10-02');
        $service = new RecaptchaResponseService();
        $actual = $service->format($response);
        self::assertStringContainsString('success', $actual);
        self::assertStringContainsString('challengeTs', $actual);
    }

    public function testFormatWithErrorCodes(): void
    {
        $response = new Response(true, ['Fake Error']);
        $service = new RecaptchaResponseService();
        $actual = $service->format($response);
        self::assertStringContainsString('error-codes', $actual);
        self::assertStringContainsString('Fake Error', $actual);
    }
}
