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
use App\Tests\TranslatorMockTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class MessagePositionTest extends TestCase
{
    use TranslatorMockTrait;

    /**
     * @phpstan-return \Generator<int, array{MessagePosition, int}>
     */
    public static function getAngle(): \Generator
    {
        yield [MessagePosition::TOP_LEFT, 315];
        yield [MessagePosition::TOP_CENTER, 0];
        yield [MessagePosition::TOP_RIGHT, 45];
        yield [MessagePosition::CENTER_LEFT, 270];
        yield [MessagePosition::CENTER_CENTER, 0];
        yield [MessagePosition::CENTER_RIGHT, 90];
        yield [MessagePosition::BOTTOM_LEFT, 225];
        yield [MessagePosition::BOTTOM_CENTER, 180];
        yield [MessagePosition::BOTTOM_RIGHT, 135];
    }

    /**
     * @phpstan-return \Generator<int, array{MessagePosition, MessagePosition}>
     */
    public static function getDefault(): \Generator
    {
        yield [MessagePosition::getDefault(), MessagePosition::BOTTOM_RIGHT];
        yield [PropertyServiceInterface::DEFAULT_MESSAGE_POSITION, MessagePosition::BOTTOM_RIGHT];
    }

    /**
     * @phpstan-return \Generator<int, array{MessagePosition, string}>
     */
    public static function getIcon(): \Generator
    {
        yield [MessagePosition::TOP_LEFT, 'fa-solid fa-arrow-up fa-rotate-by'];
        yield [MessagePosition::TOP_CENTER, 'fa-solid fa-arrow-up fa-rotate-by'];
        yield [MessagePosition::TOP_RIGHT, 'fa-solid fa-arrow-up fa-rotate-by'];
        yield [MessagePosition::CENTER_LEFT, 'fa-solid fa-arrow-up fa-rotate-by'];
        yield [MessagePosition::CENTER_CENTER, 'fa-solid fa-location-crosshairs'];
        yield [MessagePosition::CENTER_RIGHT, 'fa-solid fa-arrow-up fa-rotate-by'];
        yield [MessagePosition::BOTTOM_LEFT, 'fa-solid fa-arrow-up fa-rotate-by'];
        yield [MessagePosition::BOTTOM_CENTER, 'fa-solid fa-arrow-up fa-rotate-by'];
        yield [MessagePosition::BOTTOM_RIGHT, 'fa-solid fa-arrow-up fa-rotate-by'];
    }

    /**
     * @phpstan-return \Generator<int, array{MessagePosition, string}>
     */
    public static function getLabel(): \Generator
    {
        yield [MessagePosition::TOP_LEFT, 'top-left'];
        yield [MessagePosition::TOP_CENTER, 'top-center'];
        yield [MessagePosition::TOP_RIGHT, 'top-right'];
        yield [MessagePosition::CENTER_LEFT, 'center-left'];
        yield [MessagePosition::CENTER_CENTER, 'center-center'];
        yield [MessagePosition::CENTER_RIGHT, 'center-right'];
        yield [MessagePosition::BOTTOM_LEFT, 'bottom-left'];
        yield [MessagePosition::BOTTOM_CENTER, 'bottom-center'];
        yield [MessagePosition::BOTTOM_RIGHT, 'bottom-right'];
    }

    /**
     * @phpstan-return \Generator<int, array{MessagePosition, string}>
     */
    public static function getTranslation(): \Generator
    {
        yield [MessagePosition::TOP_LEFT, 'message_position.top-left'];
        yield [MessagePosition::TOP_CENTER, 'message_position.top-center'];
        yield [MessagePosition::TOP_RIGHT, 'message_position.top-right'];
        yield [MessagePosition::CENTER_LEFT, 'message_position.center-left'];
        yield [MessagePosition::CENTER_CENTER, 'message_position.center-center'];
        yield [MessagePosition::CENTER_RIGHT, 'message_position.center-right'];
        yield [MessagePosition::BOTTOM_LEFT, 'message_position.bottom-left'];
        yield [MessagePosition::BOTTOM_CENTER, 'message_position.bottom-center'];
        yield [MessagePosition::BOTTOM_RIGHT, 'message_position.bottom-right'];
    }

    #[DataProvider('getAngle')]
    public function testAngle(MessagePosition $position, int $expected): void
    {
        $actual = $position->getAngle();
        self::assertSame($expected, $actual);
    }

    public function testCount(): void
    {
        $expected = 9;
        self::assertCount($expected, MessagePosition::cases());
        self::assertCount($expected, MessagePosition::sorted());
    }

    #[DataProvider('getDefault')]
    public function testDefault(MessagePosition $value, MessagePosition $expected): void
    {
        self::assertSame($expected, $value);
    }

    #[DataProvider('getIcon')]
    public function testIcon(MessagePosition $position, string $expected): void
    {
        $actual = $position->getIcon();
        self::assertSame($expected, $actual);
    }

    #[DataProvider('getLabel')]
    public function testLabel(MessagePosition $position, string $value): void
    {
        $expected = 'message_position.' . $value;
        $actual = $position->getReadable();
        self::assertSame($expected, $actual);
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
        self::assertSame($expected, $sorted);
    }

    #[DataProvider('getTranslation')]
    public function testTranslate(MessagePosition $position, string $expected): void
    {
        $translator = $this->createMockTranslator();
        $actual = $position->trans($translator);
        self::assertSame($expected, $actual);
    }

    #[DataProvider('getLabel')]
    public function testValue(MessagePosition $position, string $expected): void
    {
        $actual = $position->value;
        self::assertSame($expected, $actual);
    }
}
