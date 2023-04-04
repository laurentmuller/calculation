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
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

#[\PHPUnit\Framework\Attributes\CoversClass(Importance::class)]
class ImportanceTest extends TypeTestCase
{
    private ?TranslatorInterface $translator = null;

    public static function getLabel(): array
    {
        return [
            ['importance.high', Importance::HIGH],
            ['importance.low', Importance::LOW],
            ['importance.medium', Importance::MEDIUM],
            ['importance.urgent', Importance::URGENT],
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

    public function testValue(): void
    {
        self::assertSame('high', Importance::HIGH->value); // @phpstan-ignore-line
        self::assertSame('low', Importance::LOW->value); // @phpstan-ignore-line
        self::assertSame('medium', Importance::MEDIUM->value); // @phpstan-ignore-line
        self::assertSame('urgent', Importance::URGENT->value); // @phpstan-ignore-line
    }

    /**
     * @throws Exception
     */
    private function createTranslator(): TranslatorInterface
    {
        if (null === $this->translator) {
            $this->translator = $this->createMock(TranslatorInterface::class);
            $this->translator->method('trans')
                ->willReturnArgument(0);
        }

        return $this->translator;
    }
}
