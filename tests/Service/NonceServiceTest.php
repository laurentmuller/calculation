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
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Unit test for the {@link NonceService} class.
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class NonceServiceTest extends KernelTestCase
{
    /**
     * @var NonceService
     */
    private $service;

    /**
     * {@inheritDoc}
     *
     * @throws \Exception
     */
    protected function setUp(): void
    {
        $service = self::getContainer()->get(NonceService::class);
        if ($service instanceof NonceService) {
            $this->service = $service;
        }
    }

    /**
     * @throws \Exception
     */
    public function testCsp(): void
    {
        $nonce = $this->service->getNonce();
        $csp = $this->service->getCspNonce();
        self::assertIsString($csp);
        self::stringStartsWith("'nonce-")->evaluate($csp);
        self::stringEndsWith("'")->evaluate($csp);
        self::stringContains($nonce)->evaluate($csp);
        self::assertEquals("'nonce-" . $nonce . "'", $csp);
    }

    /**
     * @throws \Exception
     */
    public function testLength32(): void
    {
        $nonce = $this->service->getNonce(32);
        self::assertIsString($nonce);
        self::assertEquals(64, \strlen($nonce));
    }

    /**
     * @throws \Exception
     */
    public function testLengthDefault(): void
    {
        $nonce = $this->service->getNonce();
        self::assertIsString($nonce);
        self::assertEquals(32, \strlen($nonce));
    }

    public function testServiceNotNull(): void
    {
        self::assertNotNull($this->service);
    }
}
