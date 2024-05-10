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

use App\Service\NonceService;
use PHPUnit\Framework\TestCase;

#[\PHPUnit\Framework\Attributes\CoversClass(NonceService::class)]
class NonceServiceTest extends TestCase
{
    private NonceService $service;

    /**
     * @throws \Exception
     */
    protected function setUp(): void
    {
        $this->service = new NonceService();
    }

    public function testCsp(): void
    {
        $nonce = $this->service->getNonce();
        $csp = $this->service->getCspNonce();
        self::stringStartsWith("'nonce-")->evaluate($csp);
        self::stringEndsWith("'")->evaluate($csp);
        self::stringContains($nonce)->evaluate($csp);
        self::assertSame("'nonce-" . $nonce . "'", $csp);
    }

    public function testLength32(): void
    {
        $nonce = $this->service->getNonce(32);
        self::assertSame(64, \strlen($nonce));
    }

    public function testLengthDefault(): void
    {
        $nonce = $this->service->getNonce();
        self::assertSame(32, \strlen($nonce));
    }
}
