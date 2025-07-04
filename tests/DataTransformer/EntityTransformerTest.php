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

use App\Entity\Group;
use App\Form\DataTransformer\EntityTransformer;
use App\Interfaces\EntityInterface;
use App\Repository\GroupRepository;
use App\Tests\Entity\IdTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Exception\InvalidArgumentException;

class EntityTransformerTest extends TestCase
{
    use IdTrait;

    /**
     * @phpstan-return \Generator<array-key, array<bool|int|string>>
     *
     * @psalm-return \Generator<int, array<string|int|null>>
     *
     * @psalm-suppress InvalidReturnType
     */
    public static function getReverseInvalid(): \Generator
    {
        yield [true];
        yield [-1];
        yield ['fake'];
    }

    /**
     * @phpstan-return \Generator<int, array{string|int|null, mixed}>
     */
    public static function getReverseValid(): \Generator
    {
        yield [null, null];
        yield ['', null];
    }

    /**
     * @phpstan-return \Generator<array-key, array<string|bool>>
     *
     * @psalm-suppress InvalidReturnType
     *
     * @psalm-return \Generator<int, array{EntityInterface|null}>
     */
    public static function getTransformInvalid(): \Generator
    {
        yield [''];
        yield [true];
        yield ['fake'];
    }

    /**
     * @phpstan-return \Generator<int, array{EntityInterface|null, mixed}>
     */
    public static function getTransformValid(): \Generator
    {
        yield [null, null];
    }

    /**
     * @phpstan-param string|int|null $value
     */
    #[DataProvider('getReverseInvalid')]
    public function testReverseInvalid(mixed $value): void
    {
        $this->expectException(InvalidArgumentException::class);
        $transformer = $this->createTransformer();
        $transformer->reverseTransform($value);
    }

    /**
     * @throws \ReflectionException
     */
    public function testReverseTransformGroup(): void
    {
        $group = $this->createGroup();
        $transformer = $this->createTransformer($group);
        $value = $group->getId();
        $actual = $transformer->reverseTransform($value);
        self::assertSame($group, $actual);
    }

    /**
     * @phpstan-param string|int|null $value
     *
     * @throws \ReflectionException
     */
    #[DataProvider('getReverseValid')]
    public function testReverseValid(mixed $value, mixed $expected): void
    {
        $group = $this->createGroup();
        $transformer = $this->createTransformer($group);
        $actual = $transformer->reverseTransform($value);
        self::assertSame($expected, $actual);
    }

    /**
     * @throws \ReflectionException
     */
    public function testTransformGroup(): void
    {
        $group = $this->createGroup();
        $transformer = $this->createTransformer($group);
        $actual = $transformer->transform($group);
        $expected = $group->getId();
        self::assertSame($expected, $actual);
    }

    /**
     * @phpstan-param EntityInterface|null $value
     */
    #[DataProvider('getTransformInvalid')]
    public function testTransformInvalid(mixed $value): void
    {
        $this->expectException(InvalidArgumentException::class);
        $transformer = $this->createTransformer();
        $transformer->transform($value);
    }

    /**
     * @phpstan-param EntityInterface|null $value
     *
     * @throws \ReflectionException
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
     * @throws \ReflectionException
     */
    private function createGroup(): Group
    {
        $group = new Group();

        return self::setId($group);
    }

    private function createRepository(?Group $group = null): GroupRepository
    {
        $repository = $this->createMock(GroupRepository::class);
        $repository->method('find')
            ->willReturn($group);
        $repository->method('getClassName')
            ->willReturn(Group::class);

        return $repository;
    }

    /**
     * @phpstan-return EntityTransformer<Group>
     */
    private function createTransformer(?Group $group = null): EntityTransformer
    {
        return new EntityTransformer($this->createRepository($group));
    }
}
