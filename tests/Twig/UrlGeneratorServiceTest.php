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

use App\Service\UrlGeneratorService;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * @extends RuntimeTestCase<UrlGeneratorService>
 */
final class UrlGeneratorServiceTest extends RuntimeTestCase
{
    #[\Override]
    protected function createService(): UrlGeneratorService
    {
        $generator = $this->createMock(UrlGeneratorInterface::class);
        $generator->method('generate')
            ->willReturnArgument(0);

        return new UrlGeneratorService($generator);
    }

    #[\Override]
    protected function getFixturesDir(): string
    {
        return __DIR__ . '/Fixtures/UrlGeneratorService';
    }
}
