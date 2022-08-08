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

namespace App\Tests\Enums;

use App\Entity\Calculation;
use App\Entity\CalculationState;
use App\Entity\Category;
use App\Entity\Customer;
use App\Entity\GlobalMargin;
use App\Entity\Group;
use App\Entity\Log;
use App\Entity\Product;
use App\Entity\Task;
use App\Entity\User;
use App\Enums\EntityName;
use App\Model\Role;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for {@link EntityName} enumeration.
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class EntityNameTest extends TestCase
{
    public function getOffsets(): array
    {
        return [
            [EntityName::CALCULATION, 0],
            [EntityName::CALCULATION_STATE, 1],
            [EntityName::CATEGORY, 2],
            [EntityName::CUSTOMER, 3],
            [EntityName::GLOBAL_MARGIN, 4],
            [EntityName::GROUP, 5],
            [EntityName::LOG, 6],
            [EntityName::PRODUCT, 7],
            [EntityName::TASK, 8],
            [EntityName::USER, 9],

            ['Calculation', 0],
            ['EntityCalculation', 0],
            [Calculation::class, 0],
        ];
    }

    public function getTryFinds(): array
    {
        return [
            [null, null],
            [1456, null],
            ['fake', null],
            [new Role(''), null],
            ['Calculation', EntityName::CALCULATION],
            ['\Calculation', EntityName::CALCULATION],
            ['\Fake\Calculation', EntityName::CALCULATION],
            [new Calculation(), EntityName::CALCULATION],
            [Calculation::class, EntityName::CALCULATION],

            ['Product', EntityName::PRODUCT],
            [Product::class, EntityName::PRODUCT],
            [new Product(), EntityName::PRODUCT],

            ['Task', EntityName::TASK],
            [Task::class, EntityName::TASK],
            [new Task(), EntityName::TASK],

            ['Category', EntityName::CATEGORY],
            [Category::class, EntityName::CATEGORY],
            [new Category(), EntityName::CATEGORY],

            ['Group', EntityName::GROUP],
            [Group::class, EntityName::GROUP],
            [new Group(), EntityName::GROUP],

            ['CalculationState', EntityName::CALCULATION_STATE],
            [CalculationState::class, EntityName::CALCULATION_STATE],
            [new CalculationState(), EntityName::CALCULATION_STATE],

            ['GlobalMargin', EntityName::GLOBAL_MARGIN],
            [GlobalMargin::class, EntityName::GLOBAL_MARGIN],
            [new GlobalMargin(), EntityName::GLOBAL_MARGIN],

            ['User', EntityName::USER],
            [User::class, EntityName::USER],
            [new User(), EntityName::USER],

            ['Customer', EntityName::CUSTOMER],
            [Customer::class, EntityName::CUSTOMER],
            [new Customer(), EntityName::CUSTOMER],

            ['Log', EntityName::LOG],
            [Log::class, EntityName::LOG],
            [new Log(), EntityName::LOG],
        ];
    }

    public function testConstants(): void
    {
        $cases = EntityName::cases();
        $constants = EntityName::constants();
        self::assertSameSize($cases, $constants);

        foreach ($constants as $key => $value) {
            self::assertStringStartsWith('ENTITY_', $key);
            self::assertNotNull(EntityName::tryFrom($value));
        }
    }

    public function testCount(): void
    {
        self::assertCount(10, EntityName::cases());
        self::assertCount(10, EntityName::sorted());
    }

    /**
     * @dataProvider getOffsets
     */
    public function testOffset(EntityName|string $e, int $expected): void
    {
        if (\is_string($e)) {
            $e = EntityName::tryFromMixed($e);
        }
        self::assertNotNull($e);
        self::assertEquals($expected, $e->offset());
    }

    public function testSorted(): void
    {
        $expected = [
            EntityName::CALCULATION,
            EntityName::PRODUCT,
            EntityName::TASK,
            EntityName::CATEGORY,
            EntityName::GROUP,
            EntityName::CALCULATION_STATE,
            EntityName::GLOBAL_MARGIN,
            EntityName::USER,
            EntityName::CUSTOMER,
            EntityName::LOG,
        ];
        $sorted = EntityName::sorted();
        self::assertEquals($expected, $sorted);
    }

    /**
     * @dataProvider getTryFinds
     */
    public function testTryFind(mixed $subject, mixed $expected): void
    {
        $result = EntityName::tryFromMixed($subject);
        self::assertEquals($expected, $result);
    }
}
