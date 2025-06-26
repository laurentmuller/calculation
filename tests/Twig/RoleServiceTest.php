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

use App\Service\RoleService;
use App\Tests\TranslatorMockTrait;
use Symfony\Component\Security\Core\Role\RoleHierarchyInterface;
use Twig\Extension\AttributeExtension;
use Twig\RuntimeLoader\RuntimeLoaderInterface;

class RoleServiceTest extends IntegrationTestCase implements RuntimeLoaderInterface
{
    use TranslatorMockTrait;

    private RoleService $service;

    #[\Override]
    protected function setUp(): void
    {
        $this->service = new RoleService(
            $this->createMock(RoleHierarchyInterface::class),
            $this->createMockTranslator()
        );
    }

    #[\Override]
    public function load(string $class): ?object
    {
        if (RoleService::class === $class) {
            return $this->service;
        }

        return null;
    }

    #[\Override]
    protected function getExtensions(): array
    {
        return [new AttributeExtension(RoleService::class)];
    }

    #[\Override]
    protected function getFixturesDir(): string
    {
        return __DIR__ . '/Fixtures/RoleService';
    }

    #[\Override]
    protected function getRuntimeLoaders(): array
    {
        return [$this];
    }
}
