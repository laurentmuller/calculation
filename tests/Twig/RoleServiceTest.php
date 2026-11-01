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
use Symfony\Component\Security\Core\Role\RoleHierarchyInterface;
use Symfony\Component\Translation\IdentityTranslator;

/**
 * @extends RuntimeTestCase<RoleService>
 */
final class RoleServiceTest extends RuntimeTestCase
{
    #[\Override]
    protected function createService(): RoleService
    {
        return new RoleService(
            self::createStub(RoleHierarchyInterface::class),
            new IdentityTranslator()
        );
    }

    #[\Override]
    protected static function getFixturesDirectory(): string
    {
        return __DIR__ . '/Fixtures/RoleService';
    }
}
