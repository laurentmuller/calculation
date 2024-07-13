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

use App\Enums\Theme;
use App\Traits\RequestTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

class RequestTraitTest extends TestCase
{
    use RequestTrait;

    public function testRequestAll(): void
    {
        $request = $this->createRequest();
        $actual = $this->getRequestAll($request, 'key');
        self::assertSame([], $actual);

        $request = $this->createRequest();
        $actual = $this->getRequestAll($request, 'key', [1, 2, 3]);
        self::assertSame([1, 2, 3], $actual);

        $request = $this->createRequest(['key' => [1, 2, 3]]);
        $actual = $this->getRequestAll($request, 'key');
        self::assertSame([1, 2, 3], $actual);

        $request = $this->createRequest(['key' => [1, 2, 3]]);
        $actual = $this->getRequestAll($request, 'key', [0]);
        self::assertSame([1, 2, 3], $actual);
    }

    public function testRequestAttributes(): void
    {
        $request = new Request(attributes: ['key' => 'value']);
        $actual = $this->getRequestString($request, 'key');
        self::assertSame('value', $actual);
    }

    public function testRequestBoolean(): void
    {
        $request = $this->createRequest();
        $actual = $this->getRequestBoolean($request, 'key');
        self::assertFalse($actual);

        $request = $this->createRequest(['key' => false]);
        $actual = $this->getRequestBoolean($request, 'key');
        self::assertFalse($actual);

        $request = $this->createRequest(['key' => true]);
        $actual = $this->getRequestBoolean($request, 'key');
        self::assertTrue($actual);
    }

    /**
     * @psalm-suppress RedundantConditionGivenDocblockType
     * @psalm-suppress DocblockTypeContradiction
     */
    public function testRequestEnum(): void
    {
        $request = $this->createRequest();
        $actual = $this->getRequestEnum($request, 'key', Theme::DARK);
        self::assertSame(Theme::DARK, $actual);

        $request = $this->createRequest(['key' => Theme::DARK]);
        $actual = $this->getRequestEnum($request, 'key', Theme::LIGHT);
        self::assertSame(Theme::DARK, $actual);
    }

    public function testRequestFloat(): void
    {
        $request = $this->createRequest();
        $actual = $this->getRequestFloat($request, 'key');
        self::assertSame(0.0, $actual);

        $request = $this->createRequest();
        $actual = $this->getRequestFloat($request, 'key', 1.0);
        self::assertSame(1.0, $actual);

        $request = $this->createRequest(['key' => 1.0]);
        $actual = $this->getRequestFloat($request, 'key');
        self::assertSame(1.0, $actual);

        $request = $this->createRequest(['key' => 1.0]);
        $actual = $this->getRequestFloat($request, 'key', 2.0);
        self::assertSame(1.0, $actual);
    }

    public function testRequestInt(): void
    {
        $request = $this->createRequest();
        $actual = $this->getRequestInt($request, 'key');
        self::assertSame(0, $actual);

        $request = $this->createRequest();
        $actual = $this->getRequestInt($request, 'key', 1);
        self::assertSame(1, $actual);

        $request = $this->createRequest(['key' => 1]);
        $actual = $this->getRequestInt($request, 'key');
        self::assertSame(1, $actual);

        $request = $this->createRequest(['key' => 1.0]);
        $actual = $this->getRequestInt($request, 'key', 2);
        self::assertSame(1, $actual);
    }

    public function testRequestQuery(): void
    {
        $request = new Request(query: ['key' => 'value']);
        $actual = $this->getRequestString($request, 'key');
        self::assertSame('value', $actual);
    }

    public function testRequestString(): void
    {
        $request = $this->createRequest();
        $actual = $this->getRequestString($request, 'key');
        self::assertSame('', $actual);

        $request = $this->createRequest();
        $actual = $this->getRequestString($request, 'key', 'empty');
        self::assertSame('empty', $actual);

        $request = $this->createRequest(['key' => 'empty']);
        $actual = $this->getRequestString($request, 'key', 'empty');
        self::assertSame('empty', $actual);

        $request = $this->createRequest(['key' => 'empty']);
        $actual = $this->getRequestString($request, 'key', 'default');
        self::assertSame('empty', $actual);
    }

    public function testRequestValue(): void
    {
        $request = $this->createRequest();
        $actual = $this->getRequestValue($request, 'key');
        self::assertNull($actual);
    }

    private function createRequest(array $request = []): Request
    {
        /** @psalm-var mixed $value */
        foreach ($request as &$value) {
            if ($value instanceof \BackedEnum) {
                $value = $value->value;
            }
        }

        return new Request(request: $request);
    }
}
