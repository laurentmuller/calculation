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
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

#[\PHPUnit\Framework\Attributes\CoversClass(MessagePosition::class)]
class MessagePositionTest extends TestCase
{
    public static function getAngle(): array
    {
        return [
            [MessagePosition::TOP_LEFT, 315],
            [MessagePosition::TOP_CENTER, 0],
            [MessagePosition::TOP_RIGHT, 45],

            [MessagePosition::CENTER_LEFT, 270],
            [MessagePosition::CENTER_CENTER, 0],
            [MessagePosition::CENTER_RIGHT, 90],

            [MessagePosition::BOTTOM_LEFT, 225],
            [MessagePosition::BOTTOM_CENTER, 180],
            [MessagePosition::BOTTOM_RIGHT, 135],
        ];
    }

    public static function getDefault(): array
    {
        return [
              [MessagePosition::getDefault(), MessagePosition::BOTTOM_RIGHT],
              [PropertyServiceInterface::DEFAULT_MESSAGE_POSITION, MessagePosition::BOTTOM_RIGHT],
        ];
    }

    public static function getIcon(): array
    {
        return [
            [MessagePosition::TOP_LEFT, 'fa-solid fa-arrow-up fa-rotate-by'],
            [MessagePosition::TOP_CENTER, 'fa-solid fa-arrow-up fa-rotate-by'],
            [MessagePosition::TOP_RIGHT, 'fa-solid fa-arrow-up fa-rotate-by'],

            [MessagePosition::CENTER_LEFT, 'fa-solid fa-arrow-up fa-rotate-by'],
            [MessagePosition::CENTER_CENTER, 'fa-solid fa-location-crosshairs'],
            [MessagePosition::CENTER_RIGHT, 'fa-solid fa-arrow-up fa-rotate-by'],

            [MessagePosition::BOTTOM_LEFT, 'fa-solid fa-arrow-up fa-rotate-by'],
            [MessagePosition::BOTTOM_CENTER, 'fa-solid fa-arrow-up fa-rotate-by'],
            [MessagePosition::BOTTOM_RIGHT, 'fa-solid fa-arrow-up fa-rotate-by'],
        ];
    }

    public static function getLabel(): array
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

    public static function getTranslation(): array
    {
        return [
            [MessagePosition::TOP_LEFT, 'message_position.top-left'],
            [MessagePosition::TOP_CENTER, 'message_position.top-center'],
            [MessagePosition::TOP_RIGHT, 'message_position.top-right'],

            [MessagePosition::CENTER_LEFT, 'message_position.center-left'],
            [MessagePosition::CENTER_CENTER, 'message_position.center-center'],
            [MessagePosition::CENTER_RIGHT, 'message_position.center-right'],

            [MessagePosition::BOTTOM_LEFT, 'message_position.bottom-left'],
            [MessagePosition::BOTTOM_CENTER, 'message_position.bottom-center'],
            [MessagePosition::BOTTOM_RIGHT, 'message_position.bottom-right'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getAngle')]
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

    #[\PHPUnit\Framework\Attributes\DataProvider('getDefault')]
    public function testDefault(MessagePosition $value, MessagePosition $expected): void
    {
        self::assertSame($expected, $value);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getIcon')]
    public function testIcon(MessagePosition $position, string $expected): void
    {
        $actual = $position->getIcon();
        self::assertSame($expected, $actual);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getLabel')]
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

    /**
     * @throws Exception
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('getTranslation')]
    public function testTranslate(MessagePosition $position, string $expected): void
    {
        $translator = $this->createTranslator();
        $actual = $position->trans($translator);
        self::assertSame($expected, $actual);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getLabel')]
    public function testValue(MessagePosition $position, string $expected): void
    {
        $actual = $position->value;
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
