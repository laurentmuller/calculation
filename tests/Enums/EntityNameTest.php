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
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

#[\PHPUnit\Framework\Attributes\CoversClass(EntityName::class)]
class EntityNameTest extends TestCase
{
    private ?TranslatorInterface $translator = null;

    public static function getLabel(): array
    {
        return [
            ['calculation.name', EntityName::CALCULATION],
            ['calculationstate.name', EntityName::CALCULATION_STATE],
            ['category.name', EntityName::CATEGORY],
            ['customer.name', EntityName::CUSTOMER],
            ['globalmargin.name', EntityName::GLOBAL_MARGIN],
            ['group.name', EntityName::GROUP],
            ['log.name', EntityName::LOG],
            ['product.name', EntityName::PRODUCT],
            ['task.name', EntityName::TASK],
            ['user.name', EntityName::USER],
        ];
    }

    public static function getMatchValue(): array
    {
        return [
            [EntityName::CALCULATION, 'EntityCalculation'],
            [EntityName::CALCULATION, 'entityCalculation'],
            [EntityName::CALCULATION, 'Fake', false],
        ];
    }

    public static function getOffset(): array
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
        ];
    }

    public static function getTryFindOffset(): array
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

    public static function getTryFindValue(): array
    {
        return [
            ['Calculation', 'EntityCalculation'],
            [Calculation::class, 'EntityCalculation'],
            [null, 'EntityCalculation', 'EntityCalculation'],
        ];
    }

    public static function getTryFromMixed(): array
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

    public static function getValue(): array
    {
        return [
            [EntityName::CALCULATION, 'EntityCalculation'],
            [EntityName::CALCULATION_STATE, 'EntityCalculationState'],
            [EntityName::CATEGORY, 'EntityCategory'],
            [EntityName::CUSTOMER, 'EntityCustomer'],
            [EntityName::GLOBAL_MARGIN, 'EntityGlobalMargin'],
            [EntityName::GROUP, 'EntityGroup'],
            [EntityName::LOG, 'EntityLog'],
            [EntityName::PRODUCT, 'EntityProduct'],
            [EntityName::TASK, 'EntityTask'],
            [EntityName::USER, 'EntityUser'],
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

    #[\PHPUnit\Framework\Attributes\DataProvider('getLabel')]
    public function testLabel(string $expected, EntityName $entity): void
    {
        self::assertSame($expected, $entity->getReadable());
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getMatchValue')]
    public function testMatch(EntityName $name, string $value, bool $expected = true): void
    {
        $result = $name->matchValue($value);
        self::assertSame($expected, $result);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getOffset')]
    public function testOffset(EntityName $entityName, int $expected): void
    {
        $result = $entityName->offset();
        self::assertSame($expected, $result);
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
        self::assertSame($expected, $sorted);
    }

    /**
     * @throws Exception
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('getLabel')]
    public function testTranslate(string $expected, EntityName $entity): void
    {
        $translator = $this->createTranslator();
        self::assertSame($expected, $entity->trans($translator));
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getTryFindOffset')]
    public function testTryFindOffset(mixed $e, int $expected): void
    {
        $result = EntityName::tryFindOffset($e);
        self::assertSame($expected, $result);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getTryFindValue')]
    public function testTryFindValue(mixed $subject, ?string $expected, ?string $default = null): void
    {
        $result = EntityName::tryFindValue($subject, $default);
        self::assertSame($expected, $result);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getTryFromMixed')]
    public function testTryFromMixed(mixed $subject, mixed $expected): void
    {
        $result = EntityName::tryFromMixed($subject);
        self::assertSame($expected, $result);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getValue')]
    public function testValue(EntityName $entityName, string $expected): void
    {
        self::assertSame($expected, $entityName->value);
    }

    /**
     * @throws Exception
     */
    private function createTranslator(): TranslatorInterface
    {
        if (!$this->translator instanceof TranslatorInterface) {
            $this->translator = $this->createMock(TranslatorInterface::class);
            $this->translator->method('trans')
                ->willReturnArgument(0);
        }

        return $this->translator;
    }
}
