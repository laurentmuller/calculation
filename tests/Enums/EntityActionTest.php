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
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

#[\PHPUnit\Framework\Attributes\CoversClass(EntityAction::class)]
class EntityActionTest extends TypeTestCase
{
    public static function getDefault(): array
    {
        return [
            [EntityAction::getDefault(), EntityAction::EDIT],
            [PropertyServiceInterface::DEFAULT_ACTION, EntityAction::EDIT],
        ];
    }

    public static function getLabel(): array
    {
        return [
            ['entity_action.edit', EntityAction::EDIT],
            ['entity_action.show', EntityAction::SHOW],
            ['entity_action.none', EntityAction::NONE],
        ];
    }

    public static function getValues(): array
    {
        return [
            [EntityAction::NONE, 'none'],
            [EntityAction::EDIT, 'edit'],
            [EntityAction::SHOW, 'show'],
        ];
    }

    public function testCount(): void
    {
        self::assertCount(3, EntityAction::cases());
        self::assertCount(3, EntityAction::sorted());
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getDefault')]
    public function testDefault(EntityAction $value, EntityAction $expected): void
    {
        self::assertSame($expected, $value);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getLabel')]
    public function testLabel(string $expected, EntityAction $action): void
    {
        self::assertSame($expected, $action->getReadable());
    }

    public function testSorted(): void
    {
        $expected = [
            EntityAction::EDIT,
            EntityAction::SHOW,
            EntityAction::NONE,
        ];
        $sorted = EntityAction::sorted();
        self::assertSame($expected, $sorted);
    }

    /**
     * @throws Exception
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('getLabel')]
    public function testTranslate(string $expected, EntityAction $action): void
    {
        $translator = $this->createTranslator();
        self::assertSame($expected, $action->trans($translator));
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getValues')]
    public function testValue(EntityAction $action, string $expected): void
    {
        self::assertSame($expected, $action->value);
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
