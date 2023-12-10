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
    public static function getLabels(): array
    {
        return [
            ['environment.dev', Environment::DEVELOPMENT],
            ['environment.prod', Environment::PRODUCTION],
            ['environment.test', Environment::TEST],
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
        self::assertCount(3, Environment::cases());
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getLabels')]
    public function testLabel(string $expected, Environment $environment): void
    {
        self::assertSame($expected, $environment->getReadable());
    }

    /**
     * @throws Exception
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('getLabels')]
    public function testTranslate(string $expected, Environment $environment): void
    {
        $translator = $this->createTranslator();
        self::assertSame($expected, $environment->trans($translator));
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getValues')]
    public function testValue(Environment $environment, string $expected): void
    {
        self::assertSame($expected, $environment->value);
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
