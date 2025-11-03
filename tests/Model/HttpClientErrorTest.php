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

namespace App\Tests\Model;

use App\Model\HttpClientError;
use PHPUnit\Framework\TestCase;

final class HttpClientErrorTest extends TestCase
{
    public function testConstructor(): void
    {
        $error = new HttpClientError(0, 'message');
        self::assertSame(0, $error->getCode());
        self::assertSame('message', $error->getMessage());
        self::assertNull($error->getException());

        $exception = new \Exception();
        $error = new HttpClientError(0, 'message', $exception);
        self::assertSame(0, $error->getCode());
        self::assertSame('message', $error->getMessage());
        self::assertSame($exception, $error->getException());
    }

    public function testSerialize(): void
    {
        $expected = [
            'result' => false,
            'code' => 10,
            'message' => 'message',
        ];
        $error = new HttpClientError(10, 'message');
        $actual = $error->jsonSerialize();
        self::assertSame($expected, $actual);

        $exception = new \Exception();
        $error = new HttpClientError(10, 'message', $exception);
        $expected += [
            'exception' => [
                'message' => $exception->getMessage(),
                'code' => $exception->getCode(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'class' => $exception::class,
                'trace' => $exception->getTraceAsString(),
            ],
        ];
        $actual = $error->jsonSerialize();
        self::assertSame($expected, $actual); // @phpstan-ignore staticMethod.impossibleType
    }

    public function testSetMessage(): void
    {
        $error = new HttpClientError(10, 'message');
        $error->setMessage('custom message');
        self::assertSame('custom message', $error->getMessage());
    }

    public function testToString(): void
    {
        $error = new HttpClientError(10, 'message');
        $actual = (string) $error;
        self::assertSame('10. message', $actual);
    }
}
