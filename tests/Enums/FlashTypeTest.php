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

use App\Enums\FlashType;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

#[\PHPUnit\Framework\Attributes\CoversClass(FlashType::class)]
class FlashTypeTest extends TestCase
{
    public static function getLabels(): \Iterator
    {
        yield [FlashType::DANGER, 'flash_bag.danger'];
        yield [FlashType::INFO, 'flash_bag.info'];
        yield [FlashType::SUCCESS, 'flash_bag.success'];
        yield [FlashType::WARNING, 'flash_bag.warning'];
    }

    public static function getValues(): \Iterator
    {
        yield [FlashType::DANGER, 'danger'];
        yield [FlashType::INFO, 'info'];
        yield [FlashType::SUCCESS, 'success'];
        yield [FlashType::WARNING, 'warning'];
    }

    public function testCount(): void
    {
        self::assertCount(4, FlashType::cases());
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getLabels')]
    public function testLabel(FlashType $type, string $expected): void
    {
        $actual = $type->getReadable();
        self::assertSame($expected, $actual);
    }

    /**
     * @throws Exception
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('getLabels')]
    public function testTranslate(FlashType $type, string $expected): void
    {
        $translator = $this->createTranslator();
        $actual = $type->trans($translator);
        self::assertSame($expected, $actual);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getValues')]
    public function testValue(FlashType $type, string $expected): void
    {
        $actual = $type->value;
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
