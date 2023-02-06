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
use Symfony\Component\Form\Test\TypeTestCase;

/**
 * Unit test for the {@link EntityAction} enumeration.
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class EntityActionTest extends TypeTestCase
{
    public function testCount(): void
    {
        self::assertCount(3, EntityAction::cases());
        self::assertCount(3, EntityAction::sorted());
    }

    public function testDefault(): void
    {
        $expected = EntityAction::EDIT;
        $default = EntityAction::getDefault();
        self::assertEquals($expected, $default);
        $default = PropertyServiceInterface::DEFAULT_ACTION;
        self::assertEquals($expected, $default);
    }

    public function testLabel(): void
    {
        self::assertEquals('entity_action.edit', EntityAction::EDIT->getReadable());
        self::assertEquals('entity_action.show', EntityAction::SHOW->getReadable());
        self::assertEquals('entity_action.none', EntityAction::NONE->getReadable());
    }

    public function testSorted(): void
    {
        $expected = [
            EntityAction::EDIT,
            EntityAction::SHOW,
            EntityAction::NONE,
        ];
        $sorted = EntityAction::sorted();
        self::assertEquals($expected, $sorted);
    }

    public function testValue(): void
    {
        self::assertEquals('edit', EntityAction::EDIT->value);
        self::assertEquals('show', EntityAction::SHOW->value);
        self::assertEquals('none', EntityAction::NONE->value);
    }
}
