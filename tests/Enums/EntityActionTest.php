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
    private ?TranslatorInterface $translator = null;

    public static function getLabel(): array
    {
        return [
            ['entity_action.edit', EntityAction::EDIT],
            ['entity_action.show', EntityAction::SHOW],
            ['entity_action.none', EntityAction::NONE],
        ];
    }

    public function testCount(): void
    {
        self::assertCount(3, EntityAction::cases());
        self::assertCount(3, EntityAction::sorted());
    }

    public function testDefault(): void
    {
        $expected = EntityAction::EDIT;
        $default = EntityAction::getDefault();
        self::assertSame($expected, $default);
        $default = PropertyServiceInterface::DEFAULT_ACTION;
        self::assertSame($expected, $default); // @phpstan-ignore-line
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

    public function testValue(): void
    {
        self::assertSame('edit', EntityAction::EDIT->value); // @phpstan-ignore-line
        self::assertSame('show', EntityAction::SHOW->value); // @phpstan-ignore-line
        self::assertSame('none', EntityAction::NONE->value); // @phpstan-ignore-line
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
