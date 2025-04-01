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

use App\Enums\EntityAction;
use App\Interfaces\PropertyServiceInterface;
use App\Tests\TranslatorMockTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class EntityActionTest extends TestCase
{
    use TranslatorMockTrait;

    /**
     * @psalm-return \Generator<int, array{EntityAction, EntityAction}>
     */
    public static function getDefault(): \Generator
    {
        yield [EntityAction::getDefault(), EntityAction::EDIT];
        yield [PropertyServiceInterface::DEFAULT_ACTION, EntityAction::EDIT];
    }

    /**
     * @psalm-return \Generator<int, array{string, EntityAction}>
     */
    public static function getLabel(): \Generator
    {
        yield ['entity_action.edit', EntityAction::EDIT];
        yield ['entity_action.show', EntityAction::SHOW];
        yield ['entity_action.none', EntityAction::NONE];
    }

    /**
     * @psalm-return \Generator<int, array{EntityAction, string}>
     */
    public static function getValues(): \Generator
    {
        yield [EntityAction::NONE, 'none'];
        yield [EntityAction::EDIT, 'edit'];
        yield [EntityAction::SHOW, 'show'];
    }

    public function testCount(): void
    {
        $expected = 3;
        self::assertCount($expected, EntityAction::cases());
        self::assertCount($expected, EntityAction::sorted());
    }

    #[DataProvider('getDefault')]
    public function testDefault(EntityAction $value, EntityAction $expected): void
    {
        self::assertSame($expected, $value);
    }

    #[DataProvider('getLabel')]
    public function testLabel(string $expected, EntityAction $action): void
    {
        $actual = $action->getReadable();
        self::assertSame($expected, $actual);
    }

    public function testSorted(): void
    {
        $expected = [
            EntityAction::EDIT,
            EntityAction::SHOW,
            EntityAction::NONE,
        ];
        $actual = EntityAction::sorted();
        self::assertSame($expected, $actual);
    }

    #[DataProvider('getLabel')]
    public function testTranslate(string $expected, EntityAction $action): void
    {
        $translator = $this->createMockTranslator();
        $actual = $action->trans($translator);
        self::assertSame($expected, $actual);
    }

    #[DataProvider('getValues')]
    public function testValue(EntityAction $action, string $expected): void
    {
        $actual = $action->value;
        self::assertSame($expected, $actual);
    }

    public function testValues(): void
    {
        $actual = EntityAction::values();
        $expected = [
            'edit',
            'show',
            'none',
        ];
        self::assertSame($expected, $actual);
    }
}
