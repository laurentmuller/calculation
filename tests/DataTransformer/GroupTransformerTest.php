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
use App\Form\DataTransformer\GroupTransformer;
use App\Tests\DatabaseTrait;
use App\Tests\ServiceTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Form\Exception\TransformationFailedException;

/**
 * Test for the {@link GroupTransformer} class.
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class GroupTransformerTest extends KernelTestCase
{
    use DatabaseTrait;
    use ServiceTrait;

    private ?Group $group = null;
    private ?GroupTransformer $transformer = null;

    /**
     * {@inheritDoc}
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\Exception\ORMException
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->group = $this->createGroup();
        $this->transformer = new GroupTransformer($this->getManager());
    }

    /**
     * {@inheritDoc}
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\Exception\ORMException
     */
    protected function tearDown(): void
    {
        $this->group = $this->deleteGroup();
        $this->transformer = null;
        parent::tearDown();
    }

    public function getReverseTransformValues(): \Generator
    {
        yield [null, null];
        yield [true, null, true];
    }

    public function getTransformValues(): \Generator
    {
        yield [null, null];
        yield [true, null, true];
    }

    public function testGroupNotNull(): void
    {
        self::assertNotNull($this->group);
    }

    /**
     * @psalm-param int|string|null $value
     *
     * @dataProvider getReverseTransformValues
     */
    public function testReverseTransform(mixed $value, mixed $expected, bool $exception = false): void
    {
        if ($exception) {
            $this->expectException(TransformationFailedException::class);
        }
        self::assertNotNull($this->transformer);
        $actual = $this->transformer->reverseTransform($value);
        self::assertEquals($expected, $actual);
    }

    public function testReverseTransformInvalid(): void
    {
        self::assertNotNull($this->transformer);
        $this->expectException(TransformationFailedException::class);
        $actual = $this->transformer->reverseTransform(-1);
        self::assertEquals($this->group, $actual);
    }

    public function testReverseTransformValid(): void
    {
        self::assertNotNull($this->group);
        self::assertNotNull($this->transformer);
        $actual = $this->transformer->reverseTransform($this->group->getId());
        self::assertEquals($this->group, $actual);
    }

    /**
     * @dataProvider getTransformValues
     */
    public function testTransform(mixed $value, mixed $expected, bool $exception = false): void
    {
        if ($exception) {
            $this->expectException(TransformationFailedException::class);
        }
        self::assertNotNull($this->transformer);
        $actual = $this->transformer->transform($value);
        self::assertEquals($expected, $actual);
    }

    public function testTransformerNotNull(): void
    {
        self::assertNotNull($this->transformer);
    }

    public function testTransformValid(): void
    {
        self::assertNotNull($this->group);
        self::assertNotNull($this->transformer);
        $actual = $this->transformer->transform($this->group);
        self::assertEquals($this->group->getId(), $actual);
    }

    /**
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\Exception\ORMException
     */
    protected function createGroup(): Group
    {
        $group = new Group();
        $group->setCode('Test');

        $manager = $this->getManager();
        $manager->persist($group);
        $manager->flush();

        return $group;
    }

    /**
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\Exception\ORMException
     */
    protected function deleteGroup(): ?Group
    {
        if (null !== $this->group) {
            $manager = $this->getManager();
            $manager->remove($this->group);
            $manager->flush();
            $this->group = null;
        }

        return $this->group;
    }
}
