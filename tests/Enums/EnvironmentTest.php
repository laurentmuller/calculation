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

use App\Enums\Environment;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

#[\PHPUnit\Framework\Attributes\CoversClass(Environment::class)]
class EnvironmentTest extends TestCase
{
    public static function getIsDevelopment(): array
    {
        return [
            [Environment::DEVELOPMENT, true],
            [Environment::PRODUCTION, false],
            [Environment::TEST, false],
        ];
    }

    public static function getIsProduction(): array
    {
        return [
            [Environment::DEVELOPMENT, false],
            [Environment::PRODUCTION, true],
            [Environment::TEST, false],
        ];
    }

    public static function getIsTest(): array
    {
        return [
            [Environment::DEVELOPMENT, false],
            [Environment::PRODUCTION, false],
            [Environment::TEST, true],
        ];
    }

    public static function getLabels(): array
    {
        return [
            [Environment::DEVELOPMENT, 'environment.dev'],
            [Environment::PRODUCTION, 'environment.prod'],
            [Environment::TEST, 'environment.test'],
        ];
    }

    public static function getValues(): array
    {
        return [
            [Environment::DEVELOPMENT, 'dev'],
            [Environment::PRODUCTION, 'prod'],
            [Environment::TEST, 'test'],
        ];
    }

    public function testCount(): void
    {
        $expected = 3;
        $actual = Environment::cases();
        self::assertCount($expected, $actual);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getIsDevelopment')]
    public function testIsDevelopment(Environment $environment, bool $expected): void
    {
        $actual = $environment->isDevelopment();
        self::assertSame($expected, $actual);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getIsProduction')]
    public function testIsProduction(Environment $environment, bool $expected): void
    {
        $actual = $environment->isProduction();
        self::assertSame($expected, $actual);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getIsTest')]
    public function testIsTest(Environment $environment, bool $expected): void
    {
        $actual = $environment->isTest();
        self::assertSame($expected, $actual);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getLabels')]
    public function testLabel(Environment $environment, string $expected): void
    {
        $actual = $environment->getReadable();
        self::assertSame($expected, $actual);
    }

    /**
     * @throws Exception
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('getLabels')]
    public function testTranslate(Environment $environment, string $expected): void
    {
        $translator = $this->createTranslator();
        $actual = $environment->trans($translator);
        self::assertSame($expected, $actual);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getValues')]
    public function testValue(Environment $environment, string $expected): void
    {
        $actual = $environment->value;
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
