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

class ConstantExtensionTest extends TestCase
{
    /**
     * @psalm-return \Generator<int, array{string, string}>
     */
    public static function getAuthenticatedVoterConstants(): \Generator
    {
        yield ['IS_AUTHENTICATED_FULLY', 'IS_AUTHENTICATED_FULLY'];
        yield ['IS_AUTHENTICATED_REMEMBERED', 'IS_AUTHENTICATED_REMEMBERED'];
        yield ['IS_AUTHENTICATED', 'IS_AUTHENTICATED'];
        yield ['IS_IMPERSONATOR', 'IS_IMPERSONATOR'];
        yield ['IS_REMEMBERED', 'IS_REMEMBERED'];
        yield ['PUBLIC_ACCESS', 'PUBLIC_ACCESS'];
    }

    /**
     * @psalm-return \Generator<int, array{string, int}>
     */
    public static function getCalculationServiceConstants(): \Generator
    {
        yield ['ROW_EMPTY', -1];
        yield ['ROW_GROUP', -2];
        yield ['ROW_TOTAL_GROUP', -3];
        yield ['ROW_GLOBAL_MARGIN', -4];
        yield ['ROW_TOTAL_NET', -5];
        yield ['ROW_USER_MARGIN', -6];
        yield ['ROW_OVERALL_TOTAL', -7];
    }

    /**
     * @psalm-return \Generator<int, array{string, string}>
     */
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

    /**
     * @psalm-return \Generator<int, array{string, string}>
     */
    public static function getRoleConstants(): \Generator
    {
        yield ['ROLE_ADMIN', 'ROLE_ADMIN'];
        yield ['ROLE_SUPER_ADMIN', 'ROLE_SUPER_ADMIN'];
        yield ['ROLE_USER', 'ROLE_USER'];
    }

    #[DataProvider('getAuthenticatedVoterConstants')]
    public function testAuthenticatedVoterConstants(string $key, string $expected): void
    {
        self::assertIsSameConstant($key, $expected);
    }

    #[DataProvider('getCalculationServiceConstants')]
    public function testCalculationServiceConstants(string $key, int $expected): void
    {
        self::assertIsSameConstant($key, $expected);
    }

    #[DataProvider('getEntityVoterConstants')]
    public function testEntityVoterConstants(string $key, string $expected): void
    {
        self::assertIsSameConstant($key, $expected);
    }

    #[DataProvider('getRoleConstants')]
    public function testRoleConstants(string $key, string $expected): void
    {
        self::assertIsSameConstant($key, $expected);
    }

    protected static function assertIsSameConstant(string $key, string|int $expected): void
    {
        $extension = new ConstantExtension(new NullAdapter());
        $globals = $extension->getGlobals();
        self::assertArrayHasKey($key, $globals);

        $actual = $globals[$key];
        self::assertSame($expected, $actual);
    }
}
