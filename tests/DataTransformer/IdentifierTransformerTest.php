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
use App\Form\DataTransformer\IdentifierTransformer;
use App\Interfaces\EntityInterface;
use App\Repository\GroupRepository;
use App\Tests\Entity\IdTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Exception\InvalidArgumentException;

class IdentifierTransformerTest extends TestCase
{
    use IdTrait;

    /**
     * @phpstan-return \Generator<array-key, array<string|bool>>
     *
     * @psalm-return \Generator<int, array<EntityInterface|null>>
     *
     * @psalm-suppress InvalidReturnType
     */
    public static function getReverseInvalid(): \Generator
    {
        yield [''];
        yield [true];
        yield ['fake'];
    }

    /**
     * @phpstan-return \Generator<int, array{?EntityInterface, mixed}>
     */
    public static function getReverseValid(): \Generator
    {
        yield [null, null];
    }

    /**
     * @phpstan-return \Generator<array-key, array<bool|int|string>>
     *
     * @psalm-return \Generator<int, array{int|string|null}>
     *
     * @psalm-suppress InvalidReturnType
     */
    public static function getTransformInvalid(): \Generator
    {
        yield [true];
        yield [-1];
        yield ['fake'];
    }

    /**
     * @phpstan-return \Generator<int, array{int|string|null, mixed}>
     */
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

    /**
     * @throws \ReflectionException
     */
    public function testReverseTransformGroup(): void
    {
        $group = $this->createGroup();
        $transformer = $this->createTransformer($group);
        $actual = $transformer->reverseTransform($group);
        self::assertSame($group->getId(), $actual);
    }

    /**
     * @phpstan-param EntityInterface|null $value
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
     * @phpstan-return IdentifierTransformer<Group>
     */
    private function createTransformer(?Group $group = null): IdentifierTransformer
    {
        return new IdentifierTransformer($this->createRepository($group));
    }
}
