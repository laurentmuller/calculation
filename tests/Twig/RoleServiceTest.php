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

/**
 * @extends RuntimeTestCase<RoleService>
 */
class RoleServiceTest extends RuntimeTestCase
{
    use TranslatorMockTrait;

    #[\Override]
    protected function createService(): object
    {
        return new RoleService(
            $this->createMock(RoleHierarchyInterface::class),
            $this->createMockTranslator()
        );
    }

    #[\Override]
    protected function getFixturesDir(): string
    {
        return __DIR__ . '/Fixtures/RoleService';
    }
}
