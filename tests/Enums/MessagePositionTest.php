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
use App\Interfaces\PropertyServiceInterface;
use Symfony\Component\Form\Test\TypeTestCase;

/**
 * Unit test for the {@link MessagePosition} enumeration.
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class MessagePositionTest extends TypeTestCase
{
    public function getLabel(): array
    {
        return [
            [MessagePosition::TOP_LEFT, 'top-left'],
            [MessagePosition::TOP_CENTER, 'top-center'],
            [MessagePosition::TOP_RIGHT, 'top-right'],

            [MessagePosition::CENTER_LEFT, 'center-left'],
            [MessagePosition::CENTER_CENTER, 'center-center'],
            [MessagePosition::CENTER_RIGHT, 'center-right'],

            [MessagePosition::BOTTOM_LEFT, 'bottom-left'],
            [MessagePosition::BOTTOM_CENTER, 'bottom-center'],
            [MessagePosition::BOTTOM_RIGHT, 'bottom-right'],
        ];
    }

    public function testCount(): void
    {
        self::assertCount(9, MessagePosition::cases());
        self::assertCount(9, MessagePosition::sorted());
    }

    public function testDefault(): void
    {
        $expected = MessagePosition::BOTTOM_RIGHT;
        $default = MessagePosition::getDefault();
        self::assertEquals($expected, $default);
        $default = PropertyServiceInterface::DEFAULT_MESSAGE_POSITION;
        self::assertEquals($expected, $default);
    }

    /**
     * @dataProvider getLabel
     */
    public function testLabel(MessagePosition $position, string $value): void
    {
        $result = $position->getReadable();
        $expected = 'message_position.' . $value;
        self::assertEquals($expected, $result);
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

    /**
     * @dataProvider getLabel
     */
    public function testValue(MessagePosition $position, string $expected): void
    {
        $value = $position->value;
        self::assertEquals($expected, $value);
    }
}
