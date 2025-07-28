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

use App\Form\DataTransformer\DatePointToDateTimeTransformer;
use App\Tests\DateAssertTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Clock\DatePoint;
use Symfony\Component\Form\Exception\UnexpectedTypeException;

class DatePointToDateTimeTransformerTest extends TestCase
{
    use DateAssertTrait;

    private DatePointToDateTimeTransformer $transformer;

    #[\Override]
    protected function setUp(): void
    {
        $this->transformer = new DatePointToDateTimeTransformer();
    }

    public function testReverseEmpty(): void
    {
        $actual = $this->transformer->reverseTransform('');
        self::assertNull($actual);
    }

    public function testReverseInvalid(): void
    {
        self::expectException(UnexpectedTypeException::class);
        $this->transformer->reverseTransform(new DatePoint());
    }

    public function testReverseNull(): void
    {
        $actual = $this->transformer->reverseTransform(null);
        self::assertNull($actual);
    }

    public function testReverseValid(): void
    {
        $expected = new \DateTime();
        $actual = $this->transformer->reverseTransform($expected);
        self::assertInstanceOf(\DateTimeInterface::class, $actual);
        self::assertDateTimeEquals($expected, $actual);
    }

    public function testTransformEmpty(): void
    {
        $actual = $this->transformer->transform('');
        self::assertNull($actual);
    }

    public function testTransformInvalid(): void
    {
        self::expectException(UnexpectedTypeException::class);
        $this->transformer->transform(new \DateTime());
    }

    public function testTransformNull(): void
    {
        $actual = $this->transformer->transform(null);
        self::assertNull($actual);
    }

    public function testTransformValid(): void
    {
        $expected = new DatePoint();
        $actual = $this->transformer->transform($expected);
        self::assertInstanceOf(\DateTime::class, $actual);
        self::assertDateTimeEquals($expected, $actual);
    }
}
