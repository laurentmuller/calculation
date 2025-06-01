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

use App\Service\CountryFlagService;
use Twig\Extension\AttributeExtension;
use Twig\RuntimeLoader\RuntimeLoaderInterface;

class CountryFlagServiceTest extends IntegrationTestCase implements RuntimeLoaderInterface
{
    private CountryFlagService $service;

    #[\Override]
    protected function setUp(): void
    {
        $this->service = new CountryFlagService();
    }

    #[\Override]
    public function load(string $class): ?object
    {
        if (CountryFlagService::class === $class) {
            return $this->service;
        }

        return null;
    }

    #[\Override]
    protected function getExtensions(): array
    {
        return [new AttributeExtension(CountryFlagService::class)];
    }

    #[\Override]
    protected function getFixturesDir(): string
    {
        return __DIR__ . '/Fixtures/CountryFlagService';
    }

    #[\Override]
    protected function getRuntimeLoaders(): array
    {
        return [$this];
    }
}
