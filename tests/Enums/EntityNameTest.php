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
use App\Interfaces\RoleInterface;
use App\Model\Role;
use App\Tests\TranslatorMockTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class EntityNameTest extends TestCase
{
    use TranslatorMockTrait;

    /**
     * @phpstan-return \Generator<int, array{EntityName, string}>
     */
    public static function getFormFields(): \Generator
    {
        yield [EntityName::CALCULATION, 'Calculation'];
        yield [EntityName::CALCULATION_STATE, 'CalculationState'];
        yield [EntityName::CATEGORY, 'Category'];
        yield [EntityName::CUSTOMER, 'Customer'];
        yield [EntityName::GLOBAL_MARGIN, 'GlobalMargin'];
        yield [EntityName::GROUP, 'Group'];
        yield [EntityName::LOG, 'Log'];
        yield [EntityName::PRODUCT, 'Product'];
        yield [EntityName::TASK, 'Task'];
        yield [EntityName::USER, 'User'];
    }

    /**
     * @phpstan-return \Generator<int, array{string, EntityName}>
     */
    public static function getLabel(): \Generator
    {
        yield ['calculation.name', EntityName::CALCULATION];
        yield ['calculationstate.name', EntityName::CALCULATION_STATE];
        yield ['category.name', EntityName::CATEGORY];
        yield ['customer.name', EntityName::CUSTOMER];
        yield ['globalmargin.name', EntityName::GLOBAL_MARGIN];
        yield ['group.name', EntityName::GROUP];
        yield ['log.name', EntityName::LOG];
        yield ['product.name', EntityName::PRODUCT];
        yield ['task.name', EntityName::TASK];
        yield ['user.name', EntityName::USER];
    }

    /**
     * @phpstan-return \Generator<int, array{EntityName, int}>
     */
    public static function getOffset(): \Generator
    {
        yield [EntityName::CALCULATION, 0];
        yield [EntityName::CALCULATION_STATE, 1];
        yield [EntityName::CATEGORY, 2];
        yield [EntityName::CUSTOMER, 3];
        yield [EntityName::GLOBAL_MARGIN, 4];
        yield [EntityName::GROUP, 5];
        yield [EntityName::LOG, 6];
        yield [EntityName::PRODUCT, 7];
        yield [EntityName::TASK, 8];
        yield [EntityName::USER, 9];
    }

    /**
     * @phpstan-return \Generator<int, array{mixed, mixed}>
     */
    public static function getTryFromMixed(): \Generator
    {
        yield [null, null];
        yield [1456, null];
        yield ['fake', null];
        yield [new Role(RoleInterface::ROLE_USER), null];
        yield ['Calculation', EntityName::CALCULATION];
        yield ['\Calculation', EntityName::CALCULATION];
        yield ['\Fake\Calculation', EntityName::CALCULATION];
        yield [new Calculation(), EntityName::CALCULATION];
        yield [Calculation::class, EntityName::CALCULATION];
        yield ['Product', EntityName::PRODUCT];
        yield [Product::class, EntityName::PRODUCT];
        yield [new Product(), EntityName::PRODUCT];
        yield ['Task', EntityName::TASK];
        yield [Task::class, EntityName::TASK];
        yield [new Task(), EntityName::TASK];
        yield ['Category', EntityName::CATEGORY];
        yield [Category::class, EntityName::CATEGORY];
        yield [new Category(), EntityName::CATEGORY];
        yield ['Group', EntityName::GROUP];
        yield [Group::class, EntityName::GROUP];
        yield [new Group(), EntityName::GROUP];
        yield ['CalculationState', EntityName::CALCULATION_STATE];
        yield [CalculationState::class, EntityName::CALCULATION_STATE];
        yield [new CalculationState(), EntityName::CALCULATION_STATE];
        yield ['GlobalMargin', EntityName::GLOBAL_MARGIN];
        yield [GlobalMargin::class, EntityName::GLOBAL_MARGIN];
        yield [new GlobalMargin(), EntityName::GLOBAL_MARGIN];
        yield ['User', EntityName::USER];
        yield [User::class, EntityName::USER];
        yield [new User(), EntityName::USER];
        yield ['Customer', EntityName::CUSTOMER];
        yield [Customer::class, EntityName::CUSTOMER];
        yield [new Customer(), EntityName::CUSTOMER];
        yield ['Log', EntityName::LOG];
        yield [Log::class, EntityName::LOG];
        yield [new Log(), EntityName::LOG];
        yield [EntityName::CALCULATION, EntityName::CALCULATION];
    }

    /**
     * @phpstan-return \Generator<int, array{EntityName, string}>
     */
    public static function getValue(): \Generator
    {
        yield [EntityName::CALCULATION, 'EntityCalculation'];
        yield [EntityName::CALCULATION_STATE, 'EntityCalculationState'];
        yield [EntityName::CATEGORY, 'EntityCategory'];
        yield [EntityName::CUSTOMER, 'EntityCustomer'];
        yield [EntityName::GLOBAL_MARGIN, 'EntityGlobalMargin'];
        yield [EntityName::GROUP, 'EntityGroup'];
        yield [EntityName::LOG, 'EntityLog'];
        yield [EntityName::PRODUCT, 'EntityProduct'];
        yield [EntityName::TASK, 'EntityTask'];
        yield [EntityName::USER, 'EntityUser'];
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
        $expected = 10;
        self::assertCount($expected, EntityName::cases());
        self::assertCount($expected, EntityName::sorted());
    }

    #[DataProvider('getFormFields')]
    public function testFormField(EntityName $entityName, string $expected): void
    {
        $actual = $entityName->getFormField();
        self::assertSame($expected, $actual);
    }

    #[DataProvider('getLabel')]
    public function testLabel(string $expected, EntityName $entity): void
    {
        $actual = $entity->getReadable();
        self::assertSame($expected, $actual);
    }

    #[DataProvider('getOffset')]
    public function testOffset(EntityName $entityName, int $expected): void
    {
        $actual = $entityName->offset();
        self::assertSame($expected, $actual);
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
        $actual = EntityName::sorted();
        self::assertSame($expected, $actual);
    }

    #[DataProvider('getLabel')]
    public function testTranslate(string $expected, EntityName $entity): void
    {
        $translator = $this->createMockTranslator();
        $actual = $entity->trans($translator);
        self::assertSame($expected, $actual);
    }

    #[DataProvider('getTryFromMixed')]
    public function testTryFromMixed(mixed $subject, mixed $expected): void
    {
        $actual = EntityName::tryFromMixed($subject);
        self::assertSame($expected, $actual);
    }

    #[DataProvider('getValue')]
    public function testValue(EntityName $entityName, string $expected): void
    {
        $actual = $entityName->value;
        self::assertSame($expected, $actual);
    }
}
