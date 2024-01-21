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
    public static function getLabel(): \Iterator
    {
        yield ['importance.high', Importance::HIGH];
        yield ['importance.low', Importance::LOW];
        yield ['importance.medium', Importance::MEDIUM];
        yield ['importance.urgent', Importance::URGENT];
    }

    public static function getValues(): \Iterator
    {
        yield [Importance::HIGH, 'high'];
        yield [Importance::LOW, 'low'];
        yield [Importance::MEDIUM, 'medium'];
        yield [Importance::URGENT, 'urgent'];
    }

    public function testCount(): void
    {
        self::assertCount(4, Importance::cases());
        self::assertCount(4, Importance::sorted());
    }

    public function testDefault(): void
    {
        $expected = Importance::LOW;
        $actual = Importance::getDefault();
        self::assertSame($expected, $actual);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getLabel')]
    public function testLabel(string $expected, Importance $importance): void
    {
        $actual = $importance->getReadable();
        self::assertSame($expected, $actual);
    }

    public function testSorted(): void
    {
        $expected = [
            Importance::LOW,
            Importance::MEDIUM,
            Importance::HIGH,
            Importance::URGENT,
        ];
        $actual = Importance::sorted();
        self::assertSame($expected, $actual);
    }

    /**
     * @throws Exception
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('getLabel')]
    public function testTranslate(string $expected, Importance $importance): void
    {
        $translator = $this->createTranslator();
        $actual = $importance->trans($translator);
        self::assertSame($expected, $actual);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getValues')]
    public function testValue(Importance $importance, string $expected): void
    {
        $actual = $importance->value;
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
