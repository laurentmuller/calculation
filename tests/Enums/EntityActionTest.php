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
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

#[\PHPUnit\Framework\Attributes\CoversClass(EntityAction::class)]
class EntityActionTest extends TestCase
{
    public static function getDefault(): \Iterator
    {
        yield [EntityAction::getDefault(), EntityAction::EDIT];
        yield [PropertyServiceInterface::DEFAULT_ACTION, EntityAction::EDIT];
    }

    public static function getLabel(): \Iterator
    {
        yield ['entity_action.edit', EntityAction::EDIT];
        yield ['entity_action.show', EntityAction::SHOW];
        yield ['entity_action.none', EntityAction::NONE];
    }

    public static function getValues(): \Iterator
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

    #[\PHPUnit\Framework\Attributes\DataProvider('getDefault')]
    public function testDefault(EntityAction $value, EntityAction $expected): void
    {
        self::assertSame($expected, $value);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getLabel')]
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

    /**
     * @throws Exception
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('getLabel')]
    public function testTranslate(string $expected, EntityAction $action): void
    {
        $translator = $this->createTranslator();
        $actual = $action->trans($translator);
        self::assertSame($expected, $actual);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getValues')]
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

    /**
     * @throws Exception
     */
    private function createTranslator(): TranslatorInterface
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->method('trans')
            ->willReturnArgument(0);

        return $translator;
    }
}
