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

namespace App\Tests\Traits;

use App\Traits\ExceptionContextTrait;
use PHPUnit\Framework\TestCase;

#[\PHPUnit\Framework\Attributes\CoversClass(ExceptionContextTrait::class)]
class ExceptionContextTraitTest extends TestCase
{
    use ExceptionContextTrait;

    public function testExceptionContext(): void
    {
        $code = 200;
        $message = 'My message';
        $file = __FILE__;
        $line = __LINE__ + 1;
        $e = new \Exception($message, $code);

        $result = $this->getExceptionContext($e);

        self::assertArrayHasKey('message', $result);
        self::assertArrayHasKey('code', $result);
        self::assertArrayHasKey('file', $result);
        self::assertArrayHasKey('line', $result);
        self::assertArrayHasKey('trace', $result);

        self::assertSame($message, $result['message']);
        self::assertSame($code, $result['code']);
        self::assertSame($file, $result['file']);
        self::assertSame($line, $result['line']);
    }
}
