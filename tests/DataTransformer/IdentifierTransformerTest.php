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
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Exception\TransformationFailedException;

class IdentifierTransformerTest extends TestCase
{
    use IdTrait;

    public static function getReverseTransformValues(): \Generator
    {
        yield [null, null];
        yield ['', null, true];
        yield [true, null, true];
        yield ['fake', null, true];
    }

    public static function getTransformValues(): \Generator
    {
        yield [null, null];
        yield ['', null];
        yield [true, null, true];
        yield [-1, null, true];
        yield ['fake', null, true];
    }

    /**
     * @psalm-param EntityInterface|null $value
     *
     * @throws Exception|\ReflectionException
     */
    #[DataProvider('getReverseTransformValues')]
    public function testReverseTransform(mixed $value, mixed $expected, bool $exception = false): void
    {
        $group = null;
        if ($exception) {
            $this->expectException(TransformationFailedException::class);
        } else {
            $group = $this->createGroup();
        }
        $transformer = $this->createTransformer($group);
        $actual = $transformer->reverseTransform($value);
        self::assertSame($expected, $actual);
        if ($exception) {
            self::fail('A exception must be raised.');
        }
    }

    /**
     * @throws Exception|\ReflectionException
     */
    public function testReverseTransformGroup(): void
    {
        $group = $this->createGroup();
        $transformer = $this->createTransformer($group);
        $actual = $transformer->reverseTransform($group);
        self::assertSame($group->getId(), $actual);
    }

    /**
     * @psalm-param int|string|null $value
     *
     * @throws Exception|\ReflectionException
     */
    #[DataProvider('getTransformValues')]
    public function testTransform(mixed $value, mixed $expected, bool $exception = false): void
    {
        $group = null;
        if ($exception) {
            $this->expectException(TransformationFailedException::class);
        } else {
            $group = $this->createGroup();
        }
        $transformer = $this->createTransformer($group);
        $actual = $transformer->transform($value);
        self::assertSame($expected, $actual);
        if ($exception) {
            self::fail('A exception must be raised.');
        }
    }

    /**
     * @throws Exception|\ReflectionException
     */
    public function testTransformGroup(): void
    {
        $group = $this->createGroup();
        $transformer = $this->createTransformer($group);
        $actual = $transformer->transform($group->getId());
        self::assertSame($group, $actual);
    }

    /**
     * @throws \ReflectionException
     */
    private function createGroup(): Group
    {
        $group = new Group();

        return self::setId($group);
    }

    /**
     * @throws Exception
     */
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
     * @psalm-return IdentifierTransformer<Group>
     *
     * @throws Exception
     */
    private function createTransformer(?Group $group = null): IdentifierTransformer
    {
        return new IdentifierTransformer($this->createRepository($group));
    }
}
