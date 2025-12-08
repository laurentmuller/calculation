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

namespace App\Tests\Form\DataTransformer;

use App\Entity\Group;
use App\Form\DataTransformer\IdentifierTransformer;
use App\Interfaces\EntityInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Form\Exception\InvalidArgumentException;

/**
 * @extends EntityTransformerTestCase<int, Group>
 */
final class IdentifierTransformerTest extends EntityTransformerTestCase
{
    public static function getReverseInvalid(): \Generator
    {
        yield [''];
        yield [true];
        yield ['fake'];
    }

    public static function getReverseValid(): \Generator
    {
        yield [null, null];

        $group = self::setId(new Group());
        yield [$group, $group->getId()];
    }

    public static function getTransformInvalid(): \Generator
    {
        yield [true];
        yield [-1];
        yield ['fake'];
    }

    public static function getTransformValid(): \Generator
    {
        yield [null, null];
        yield ['', null];
    }

    /**
     * @phpstan-param EntityInterface|null $value
     */
    #[DataProvider('getReverseInvalid')]
    public function testReverseInvalid(mixed $value): void
    {
        $this->expectException(InvalidArgumentException::class);
        $transformer = $this->createTransformer();
        $transformer->reverseTransform($value);
    }

    public function testReverseTransformGroup(): void
    {
        $group = $this->createGroup();
        $transformer = $this->createTransformer($group);
        $actual = $transformer->reverseTransform($group);
        self::assertSame($group->getId(), $actual);
    }

    /**
     * @phpstan-param EntityInterface|null $value
     */
    #[DataProvider('getReverseValid')]
    public function testReverseValid(mixed $value, mixed $expected): void
    {
        $group = $this->createGroup();
        $transformer = $this->createTransformer($group);
        $actual = $transformer->reverseTransform($value);
        self::assertSame($expected, $actual);
    }

    public function testTransformGroup(): void
    {
        $group = $this->createGroup();
        $transformer = $this->createTransformer($group);
        $actual = $transformer->transform($group->getId());
        self::assertSame($group, $actual);
    }

    /**
     * @phpstan-param int|string|null $value
     */
    #[DataProvider('getTransformInvalid')]
    public function testTransformInvalid(mixed $value): void
    {
        $this->expectException(InvalidArgumentException::class);
        $transformer = $this->createTransformer();
        $transformer->transform($value);
    }

    /**
     * @phpstan-param int|string|null $value
     */
    #[DataProvider('getTransformValid')]
    public function testTransformValid(mixed $value, mixed $expected): void
    {
        $group = $this->createGroup();
        $transformer = $this->createTransformer($group);
        $actual = $transformer->transform($value);
        self::assertSame($expected, $actual);
    }

    /**
     * @phpstan-return IdentifierTransformer<Group>
     */
    #[\Override]
    protected function createTransformer(?Group $group = null): IdentifierTransformer
    {
        return new IdentifierTransformer($this->createRepository($group));
    }
}
