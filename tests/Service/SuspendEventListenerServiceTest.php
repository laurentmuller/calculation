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

use App\Interfaces\DisableListenerInterface;
use App\Service\SuspendEventListenerService;
use App\Traits\DisableListenerTrait;
use PHPUnit\Framework\TestCase;

class SuspendEventListenerServiceTest extends TestCase implements DisableListenerInterface
{
    use DisableListenerTrait;

    private ?SuspendEventListenerService $service = null;

    #[\Override]
    protected function setUp(): void
    {
        $this->service = new SuspendEventListenerService([$this]);
    }

    public function testDestruct(): void
    {
        self::assertNotNull($this->service);
        $this->service = null;
        self::assertNull($this->service);
    }

    public function testDisableListeners(): void
    {
        self::assertNotNull($this->service);
        self::assertFalse($this->service->isDisabled());
        self::assertTrue($this->isEnabled());

        $this->service->disableListeners();

        self::assertTrue($this->service->isDisabled());
    }

    public function testEnableListeners(): void
    {
        self::assertNotNull($this->service);
        self::assertFalse($this->service->isDisabled());
        self::assertTrue($this->isEnabled());

        $this->service->enableListeners();

        self::assertFalse($this->service->isDisabled());
    }

    public function testSuspendListeners(): void
    {
        self::assertNotNull($this->service);
        self::assertFalse($this->service->isDisabled());
        $this->service->suspendListeners(function (): void {
            self::assertNotNull($this->service);
            self::assertTrue($this->service->isDisabled());
        });
        self::assertFalse($this->service->isDisabled());
    }
}
