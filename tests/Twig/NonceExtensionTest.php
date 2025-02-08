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

namespace App\Tests\Twig;

use App\Service\NonceService;
use App\Twig\NonceExtension;

class NonceExtensionTest extends IntegrationTestCase
{
    protected function getExtensions(): array
    {
        $service = $this->createMock(NonceService::class);
        $service->method('getNonce')
            ->willReturn('nonce');

        return [new NonceExtension($service)];
    }

    protected function getFixturesDir(): string
    {
        return __DIR__ . '/Fixtures/NonceExtension';
    }
}
