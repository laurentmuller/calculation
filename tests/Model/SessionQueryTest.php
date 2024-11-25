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

use App\Model\SessionQuery;
use PHPUnit\Framework\TestCase;

class SessionQueryTest extends TestCase
{
    /**
     * @throws \JsonException
     */
    public function testDecodeArray(): void
    {
        $query = new SessionQuery('name', '{"key": "value"}');
        $actual = $query->decode(true);
        self::assertIsArray($actual);
        self::assertArrayHasKey('key', $actual);
        self::assertSame('value', $actual['key']);
    }

    public function testDecodeInvalid(): void
    {
        self::expectException(\JsonException::class);
        $query = new SessionQuery('name', '{"key": "value"');
        $query->decode();
    }

    /**
     * @throws \JsonException
     */
    public function testDecodeValid(): void
    {
        $query = new SessionQuery('name', '{"key": "value"}');
        $actual = $query->decode();
        self::assertInstanceOf(\stdClass::class, $actual);
        self::assertObjectHasProperty('key', $actual);
        self::assertSame('value', $actual->key);
    }

    public function testProperties(): void
    {
        $actual = new SessionQuery('name', 'value');
        self::assertSame('name', $actual->name);
        self::assertSame('value', $actual->value);
    }
}
