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
final class RoleServiceTest extends RuntimeTestCase
{
    use TranslatorMockTrait;

    #[\Override]
    protected function createService(): RoleService
    {
        return new RoleService(
            self::createStub(RoleHierarchyInterface::class),
            $this->createMockTranslator()
        );
    }

    #[\Override]
    protected function getFixturesDir(): string
    {
        return __DIR__ . '/Fixtures/RoleService';
    }
}
