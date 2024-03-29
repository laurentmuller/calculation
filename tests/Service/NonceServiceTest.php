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
    private ?NonceService $service = null;

    /**
     * @throws \Exception
     */
    protected function setUp(): void
    {
        $this->service = new NonceService();
    }

    /**
     * @throws \Exception
     */
    public function testCsp(): void
    {
        $nonce = $this->service?->getNonce();
        self::assertIsString($nonce);

        $csp = $this->service?->getCspNonce();
        self::assertIsString($csp);

        self::stringStartsWith("'nonce-")->evaluate($csp);
        self::stringEndsWith("'")->evaluate($csp);
        self::stringContains($nonce)->evaluate($csp);
        self::assertSame("'nonce-" . $nonce . "'", $csp);
    }

    /**
     * @throws \Exception
     */
    public function testLength32(): void
    {
        $nonce = $this->service?->getNonce(32);
        self::assertIsString($nonce);
        self::assertSame(64, \strlen($nonce));
    }

    /**
     * @throws \Exception
     */
    public function testLengthDefault(): void
    {
        $nonce = $this->service?->getNonce();
        self::assertIsString($nonce);
        self::assertSame(32, \strlen($nonce));
    }

    public function testServiceNotNull(): void
    {
        self::assertNotNull($this->service);
    }
}
