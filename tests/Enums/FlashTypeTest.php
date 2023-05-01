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
use PHPUnit\Framework\TestCase;

#[\PHPUnit\Framework\Attributes\CoversClass(FlashType::class)]
class FlashTypeTest extends TestCase
{
    public static function getValues(): array
    {
        return [
            [FlashType::DANGER, 'danger'],
            [FlashType::INFO, 'info'],
            [FlashType::SUCCESS, 'success'],
            [FlashType::WARNING, 'warning'],
        ];
    }

    public function testCount(): void
    {
        self::assertCount(4, FlashType::cases());
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getValues')]
    public function testValue(FlashType $type, string $expected): void
    {
        self::assertSame($expected, $type->value);
    }
}
