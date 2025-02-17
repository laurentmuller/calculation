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
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Mime\Address;

class AddressTransformerTest extends TestCase
{
    private AddressTransformer $transformer;

    #[\Override]
    protected function setUp(): void
    {
        $this->transformer = new AddressTransformer();
    }

    public static function getReverseTransformInvalid(): \Generator
    {
        yield [true];
        yield [25];
        yield ['email-invalid'];
    }

    public static function getReverseTransformValid(): \Generator
    {
        yield [null, null];
        yield ['user@root.com', new Address('user@root.com')];
        yield ['username <user@root.com>', new Address('user@root.com', 'username')];
    }

    public static function getTransformInvalid(): \Generator
    {
        yield [true];
        yield [25];
    }

    public static function getTransformValid(): \Generator
    {
        yield [null, null];
        yield [new Address('user@root.com'), 'user@root.com'];
        yield [new Address('user@root.com', 'username'), \htmlentities('username <user@root.com>')];
    }

    #[DataProvider('getReverseTransformInvalid')]
    public function testReverseTransformInvalid(mixed $value): void
    {
        $this->expectException(TransformationFailedException::class);
        $this->transformer->reverseTransform($value);
    }

    #[DataProvider('getReverseTransformValid')]
    public function testReverseTransformValid(mixed $value, mixed $expected): void
    {
        $actual = $this->transformer->reverseTransform($value);
        self::assertEqualsCanonicalizing($expected, $actual);
    }

    #[DataProvider('getTransformInvalid')]
    public function testTransformInvalid(mixed $value): void
    {
        $this->expectException(TransformationFailedException::class);
        $this->transformer->transform($value);
    }

    #[DataProvider('getTransformValid')]
    public function testTransformValid(mixed $value, mixed $expected): void
    {
        $actual = $this->transformer->transform($value);
        self::assertSame($expected, $actual);
    }
}
