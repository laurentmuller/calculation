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

use App\Twig\ConstantExtension;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\NullAdapter;

final class ConstantExtensionTest extends TestCase
{
    private array $globals;

    #[\Override]
    protected function setUp(): void
    {
        $extension = new ConstantExtension(new NullAdapter());
        $this->globals = $extension->getGlobals();
    }

    public static function getAuthenticatedVoterConstants(): \Generator
    {
        $values = [
            'IS_AUTHENTICATED_FULLY',
            'IS_AUTHENTICATED_REMEMBERED',
            'IS_AUTHENTICATED',
            'IS_IMPERSONATOR',
            'IS_REMEMBERED',
            'PUBLIC_ACCESS',
        ];
        foreach ($values as $value) {
            yield [$value, $value];
        }
    }

    public static function getEntityVoterConstants(): \Generator
    {
        yield ['PERMISSION_ADD', 'ADD'];
        yield ['PERMISSION_DELETE', 'DELETE'];
        yield ['PERMISSION_EDIT', 'EDIT'];
        yield ['PERMISSION_EXPORT', 'EXPORT'];
        yield ['PERMISSION_LIST', 'LIST'];
        yield ['PERMISSION_SHOW', 'SHOW'];
        yield ['ENTITY_CALCULATION', 'EntityCalculation'];
        yield ['ENTITY_CALCULATION_STATE', 'EntityCalculationState'];
        yield ['ENTITY_CATEGORY', 'EntityCategory'];
        yield ['ENTITY_CUSTOMER', 'EntityCustomer'];
        yield ['ENTITY_GLOBAL_MARGIN', 'EntityGlobalMargin'];
        yield ['ENTITY_GROUP', 'EntityGroup'];
        yield ['ENTITY_LOG', 'EntityLog'];
        yield ['ENTITY_PRODUCT', 'EntityProduct'];
        yield ['ENTITY_TASK', 'EntityTask'];
        yield ['ENTITY_USER', 'EntityUser'];
    }

    public static function getIconsConstants(): \Generator
    {
        // entity
        yield ['ICON_CALCULATION', 'fa-solid fa-calculator'];
        yield ['ICON_CALCULATION_STATE', 'fa-regular fa-flag'];
        yield ['ICON_CATEGORY', 'fa-regular fa-folder'];
        yield ['ICON_CUSTOMER', 'fa-regular fa-address-card'];
        yield ['ICON_GLOBAL_MARGIN', 'fa-solid fa-percent'];
        yield ['ICON_GROUP', 'fa-regular fa-folder-closed'];
        yield ['ICON_LOG', 'fa-solid fa-book'];
        yield ['ICON_PRODUCT', 'fa-regular fa-file-alt'];
        yield ['ICON_TASK', 'fa-solid fa-tasks'];
        yield ['ICON_USER', 'fa-regular fa-user'];
        // action
        yield ['ICON_SHOW', 'fa-solid fa-wrench'];
        yield ['ICON_ADD', 'fa-regular fa-file'];
        yield ['ICON_EDIT', 'fa-solid fa-pencil'];
        yield ['ICON_DELETE', 'fa-solid fa-eraser'];
        yield ['ICON_COPY', 'fa-regular fa-copy'];
        // export
        yield ['ICON_PDF', 'fa-regular fa-file-pdf'];
        yield ['ICON_EXCEL', 'fa-regular fa-file-excel'];
        yield ['ICON_WORD', 'fa-regular fa-file-word'];
        // view
        yield ['ICON_VIEW_TABLE', 'fa-solid fa-table-list'];
        yield ['ICON_VIEW_CUSTOM', 'fa-solid fa-grip-horizontal'];
    }

    public static function getRoleConstants(): \Generator
    {
        $values = [
            'ROLE_USER',
            'ROLE_ADMIN',
            'ROLE_SUPER_ADMIN',
        ];
        foreach ($values as $value) {
            yield [$value, $value];
        }
    }

    #[DataProvider('getRoleConstants')]
    #[DataProvider('getIconsConstants')]
    #[DataProvider('getEntityVoterConstants')]
    #[DataProvider('getAuthenticatedVoterConstants')]
    public function testConstants(string $key, string $expected): void
    {
        self::assertArrayHasKey($key, $this->globals);
        $actual = $this->globals[$key];
        self::assertSame($expected, $actual);
    }
}
