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
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Exception\TransformationFailedException;

#[\PHPUnit\Framework\Attributes\CoversClass(EntityTransformer::class)]
class EntityTransformerTest extends TestCase
{
    public static function getReverseTransformValues(): \Generator
    {
        yield [null, null];
        yield ['', null];
        yield [true, null, true];
        yield [-1, null, true];
        yield ['fake', null, true];
    }

    public static function getTransformValues(): \Generator
    {
        yield [null, null];
        yield ['', null, true];
        yield [true, null, true];
        yield ['fake', null, true];
    }

    /**
     * @psalm-param string|int|null $value
     *
     * @throws Exception
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('getReverseTransformValues')]
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
     * @throws Exception
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
     * @psalm-param EntityInterface|null $value
     *
     * @throws Exception
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('getTransformValues')]
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
     * @throws Exception
     */
    public function testTransformGroup(): void
    {
        $group = $this->createGroup();
        $transformer = $this->createTransformer($group);
        $actual = $transformer->transform($group);
        $expected = $group->getId();
        self::assertSame($expected, $actual);
    }

    private function createGroup(): Group
    {
        $group = new Group();
        $property = new \ReflectionProperty(Group::class, 'id');
        $property->setValue($group, 1);

        return $group;
    }

    /**
     * @throws Exception
     */
    private function createRepository(Group $group = null): GroupRepository
    {
        $repository = $this->createMock(GroupRepository::class);
        $repository->method('find')
            ->willReturn($group);
        $repository->method('getClassName')
            ->willReturn(Group::class);

        return $repository;
    }

    /**
     * @psalm-return EntityTransformer<Group>
     *
     * @throws Exception
     */
    private function createTransformer(Group $group = null): EntityTransformer
    {
        $repository = $this->createRepository($group);

        return new EntityTransformer($repository);
    }
}
