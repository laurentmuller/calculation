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

namespace App\Tests\DataTransformer;

use App\Form\DataTransformer\AddressTransformer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Mime\Address;

#[\PHPUnit\Framework\Attributes\CoversClass(AddressTransformer::class)]
class AddressTransformerTest extends TestCase
{
    private ?AddressTransformer $transformer = null;

    protected function setUp(): void
    {
        $this->transformer = new AddressTransformer();
    }

    protected function tearDown(): void
    {
        $this->transformer = null;
    }

    public static function getReverseTransformValues(): \Generator
    {
        yield [null, null];
        yield [true, null, true];
        yield [25, null, true];
        yield ['user@root.com', new Address('user@root.com')];
        yield ['username <user@root.com>', new Address('user@root.com', 'username')];
        yield ['email-invalid', null, true];
    }

    public static function getTransformValues(): \Generator
    {
        yield [null, null];
        yield [true, null, true];
        yield [25, null, true];
        yield [new Address('user@root.com'), 'user@root.com'];
        yield [new Address('user@root.com', 'username'), \htmlentities('username <user@root.com>')];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getReverseTransformValues')]
    public function testReverseTransform(mixed $value, mixed $expected, bool $exception = false): void
    {
        if ($exception) {
            $this->expectException(TransformationFailedException::class);
        }
        self::assertNotNull($this->transformer);
        $actual = $this->transformer->reverseTransform($value);
        self::assertEqualsCanonicalizing($expected, $actual);
        if ($exception) {
            self::fail('A exception must be raised.');
        }
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getTransformValues')]
    public function testTransform(mixed $value, mixed $expected, bool $exception = false): void
    {
        if ($exception) {
            $this->expectException(TransformationFailedException::class);
        }
        self::assertNotNull($this->transformer);
        $actual = $this->transformer->transform($value);
        self::assertSame($expected, $actual);
        if ($exception) {
            self::fail('A exception must be raised.');
        }
    }

    public function testTransformerNotNull(): void
    {
        self::assertNotNull($this->transformer);
    }
}
