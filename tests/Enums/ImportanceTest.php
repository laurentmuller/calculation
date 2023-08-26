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

use App\Enums\Importance;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

#[\PHPUnit\Framework\Attributes\CoversClass(Importance::class)]
class ImportanceTest extends TestCase
{
    public static function getLabel(): array
    {
        return [
            ['importance.high', Importance::HIGH],
            ['importance.low', Importance::LOW],
            ['importance.medium', Importance::MEDIUM],
            ['importance.urgent', Importance::URGENT],
        ];
    }

    public static function getValues(): array
    {
        return [
            [Importance::HIGH, 'high'],
            [Importance::LOW, 'low'],
            [Importance::MEDIUM, 'medium'],
            [Importance::URGENT, 'urgent'],
        ];
    }

    public function testCount(): void
    {
        self::assertCount(4, Importance::cases());
        self::assertCount(4, Importance::sorted());
    }

    public function testDefault(): void
    {
        $default = Importance::getDefault();
        $expected = Importance::LOW;
        self::assertSame($expected, $default);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getLabel')]
    public function testLabel(string $expected, Importance $importance): void
    {
        self::assertSame($expected, $importance->getReadable());
    }

    public function testSorted(): void
    {
        $expected = [
            Importance::LOW,
            Importance::MEDIUM,
            Importance::HIGH,
            Importance::URGENT,
        ];
        $sorted = Importance::sorted();
        self::assertSame($expected, $sorted);
    }

    /**
     * @throws Exception
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('getLabel')]
    public function testTranslate(string $expected, Importance $importance): void
    {
        $translator = $this->createTranslator();
        self::assertSame($expected, $importance->trans($translator));
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getValues')]
    public function testValue(Importance $importance, string $expected): void
    {
        self::assertSame($expected, $importance->value);
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
