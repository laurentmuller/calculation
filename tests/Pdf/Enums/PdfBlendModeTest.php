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

namespace App\Tests\Pdf\Enums;

use App\Pdf\Enums\PdfBlendMode;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfBlendMode::class)]
class PdfBlendModeTest extends TestCase
{
    public static function getModes(): \Generator
    {
        $modes = PdfBlendMode::cases();
        foreach ($modes as $mode) {
            yield [$mode];
        }
    }

    #[DataProvider('getModes')]
    public function testCamel(PdfBlendMode $mode): void
    {
        $actual = $mode->camel();
        $expected = $this->expected($mode->name);
        self::assertSame($expected, $actual);
    }

    private function expected(string $name): string
    {
        $values = \explode('_', \strtolower($name));
        $values = \array_map(fn (string $entry): string => \ucfirst($entry), $values);

        return \implode('', $values);
    }
}
