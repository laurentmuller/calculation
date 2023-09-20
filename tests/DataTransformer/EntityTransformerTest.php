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
use App\Repository\GroupRepository;
use App\Tests\DatabaseTrait;
use App\Tests\ServiceTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Form\Exception\TransformationFailedException;

#[\PHPUnit\Framework\Attributes\CoversClass(EntityTransformer::class)]
class EntityTransformerTest extends KernelTestCase
{
    use DatabaseTrait;
    use ServiceTrait;

    private ?Group $group = null;
    private ?GroupRepository $repository = null;
    /** @psalm-var EntityTransformer<Group>|null */
    private ?EntityTransformer $transformer = null;

    /**
     * @throws \Doctrine\ORM\Exception\ORMException
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->group = $this->createGroup();
        $this->transformer = new EntityTransformer($this->getRepository());
    }

    /**
     * @throws \Doctrine\ORM\Exception\ORMException
     */
    protected function tearDown(): void
    {
        $this->group = $this->deleteGroup();
        $this->transformer = null;
        $this->repository = null;
        parent::tearDown();
    }

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
        yield [true, null, true];
    }

    /**
     * @psalm-param string|int|null $value
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('getReverseTransformValues')]
    public function testReverseTransform(mixed $value, mixed $expected, bool $exception = false): void
    {
        if ($exception) {
            $this->expectException(TransformationFailedException::class);
        }
        self::assertNotNull($this->transformer);
        $actual = $this->transformer->reverseTransform($value);
        self::assertSame($expected, $actual);
        if ($exception) {
            self::fail('A exception must be raised.');
        }
    }

    public function testReverseTransformGroup(): void
    {
        self::assertNotNull($this->group);
        self::assertNotNull($this->transformer);
        $actual = $this->transformer->reverseTransform($this->group->getId());
        self::assertSame($this->group, $actual);
    }

    /**
     * @psalm-param \App\Entity\AbstractEntity|null $value
     */
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

    public function testTransformGroup(): void
    {
        self::assertNotNull($this->group);
        self::assertNotNull($this->transformer);
        $actual = $this->transformer->transform($this->group);
        self::assertSame($this->group->getId(), $actual);
    }

    /**
     * @throws \Doctrine\ORM\Exception\ORMException
     */
    private function createGroup(): Group
    {
        $group = new Group();
        $group->setCode('Test');

        $manager = $this->getManager();
        $manager->persist($group);
        $manager->flush();

        return $group;
    }

    /**
     * @throws \Doctrine\ORM\Exception\ORMException
     */
    private function deleteGroup(): null
    {
        if ($this->group instanceof Group) {
            $manager = $this->getManager();
            $manager->remove($this->group);
            $manager->flush();
            $this->group = null;
        }

        return $this->group;
    }

    /**
     * @throws \Doctrine\ORM\Exception\ORMException
     */
    private function getRepository(): GroupRepository
    {
        if (!$this->repository instanceof GroupRepository) {
            /** @psalm-var GroupRepository $repository */
            $repository = $this->getManager()->getRepository(Group::class);
            $this->repository = $repository;
        }

        return $this->repository;
    }
}
