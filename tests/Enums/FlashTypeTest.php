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
use App\Tests\TranslatorMockTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class FlashTypeTest extends TestCase
{
    use TranslatorMockTrait;

    /**
     * @phpstan-return \Generator<int, array{FlashType, string}>
     */
    public static function getColors(): \Generator
    {
        yield [FlashType::DANGER, 'text-danger'];
        yield [FlashType::INFO, 'text-info'];
        yield [FlashType::SUCCESS, 'text-success'];
        yield [FlashType::WARNING, 'text-warning'];
    }

    /**
     * @phpstan-return \Generator<int, array{FlashType, string}>
     */
    public static function getIcons(): \Generator
    {
        yield [FlashType::DANGER, 'fas fa-lg fa-exclamation-triangle'];
        yield [FlashType::INFO, 'fas fa-lg fa-info-circle'];
        yield [FlashType::SUCCESS, 'fas fa-lg fa-check-circle'];
        yield [FlashType::WARNING, 'fas fa-lg fa-exclamation-circle'];
    }

    /**
     * @phpstan-return \Generator<int, array{FlashType, string}>
     */
    public static function getLabels(): \Generator
    {
        yield [FlashType::DANGER, 'flash_bag.danger'];
        yield [FlashType::INFO, 'flash_bag.info'];
        yield [FlashType::SUCCESS, 'flash_bag.success'];
        yield [FlashType::WARNING, 'flash_bag.warning'];
    }

    /**
     * @phpstan-return \Generator<int, array{FlashType, string}>
     */
    public static function getValues(): \Generator
    {
        yield [FlashType::DANGER, 'danger'];
        yield [FlashType::INFO, 'info'];
        yield [FlashType::SUCCESS, 'success'];
        yield [FlashType::WARNING, 'warning'];
    }

    #[DataProvider('getColors')]
    public function testColor(FlashType $type, string $expected): void
    {
        $actual = $type->getColor();
        self::assertSame($expected, $actual);
    }

    public function testCount(): void
    {
        self::assertCount(4, FlashType::cases());
    }

    #[DataProvider('getIcons')]
    public function testIcon(FlashType $type, string $expected): void
    {
        $actual = $type->getIcon();
        self::assertSame($expected, $actual);
    }

    #[DataProvider('getLabels')]
    public function testLabel(FlashType $type, string $expected): void
    {
        $actual = $type->getReadable();
        self::assertSame($expected, $actual);
    }

    #[DataProvider('getLabels')]
    public function testTranslate(FlashType $type, string $expected): void
    {
        $translator = $this->createMockTranslator();
        $actual = $type->trans($translator);
        self::assertSame($expected, $actual);
    }

    #[DataProvider('getValues')]
    public function testValue(FlashType $type, string $expected): void
    {
        $actual = $type->value;
        self::assertSame($expected, $actual);
    }
}
