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

use App\Enums\MessagePosition;
use Symfony\Component\Form\Test\TypeTestCase;

/**
 * Unit test for the {@link MessagePosition} enumeration.
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class MessagePositionTest extends TypeTestCase
{
    public function testCount(): void
    {
        self::assertCount(9, MessagePosition::cases());
        self::assertCount(9, MessagePosition::sorted());
    }

    public function testDefault(): void
    {
        $default = MessagePosition::getDefault();
        self::assertEquals(MessagePosition::BOTTOM_RIGHT, $default);
    }

    public function testLabel(): void
    {
        self::assertEquals('message_position.top_left', MessagePosition::TOP_LEFT->getReadable());
        self::assertEquals('message_position.top_center', MessagePosition::TOP_CENTER->getReadable());
        self::assertEquals('message_position.top_right', MessagePosition::TOP_RIGHT->getReadable());

        self::assertEquals('message_position.center_left', MessagePosition::CENTER_LEFT->getReadable());
        self::assertEquals('message_position.center_center', MessagePosition::CENTER_CENTER->getReadable());
        self::assertEquals('message_position.center_right', MessagePosition::CENTER_RIGHT->getReadable());

        self::assertEquals('message_position.bottom_left', MessagePosition::BOTTOM_LEFT->getReadable());
        self::assertEquals('message_position.bottom_center', MessagePosition::BOTTOM_CENTER->getReadable());
        self::assertEquals('message_position.bottom_right', MessagePosition::BOTTOM_RIGHT->getReadable());
    }

    public function testSorted(): void
    {
        $expected = [
            MessagePosition::TOP_LEFT,
            MessagePosition::TOP_CENTER,
            MessagePosition::TOP_RIGHT,

            MessagePosition::CENTER_LEFT,
            MessagePosition::CENTER_CENTER,
            MessagePosition::CENTER_RIGHT,

            MessagePosition::BOTTOM_LEFT,
            MessagePosition::BOTTOM_CENTER,
            MessagePosition::BOTTOM_RIGHT,
        ];
        $sorted = MessagePosition::sorted();
        self::assertEquals($expected, $sorted);
    }

    public function testValue(): void
    {
        self::assertEquals('top_left', MessagePosition::TOP_LEFT->value);
        self::assertEquals('top_center', MessagePosition::TOP_CENTER->value);
        self::assertEquals('top_right', MessagePosition::TOP_RIGHT->value);

        self::assertEquals('center_left', MessagePosition::CENTER_LEFT->value);
        self::assertEquals('center_center', MessagePosition::CENTER_CENTER->value);
        self::assertEquals('center_right', MessagePosition::CENTER_RIGHT->value);

        self::assertEquals('bottom_left', MessagePosition::BOTTOM_LEFT->value);
        self::assertEquals('bottom_center', MessagePosition::BOTTOM_CENTER->value);
        self::assertEquals('bottom_right', MessagePosition::BOTTOM_RIGHT->value);
    }
}
