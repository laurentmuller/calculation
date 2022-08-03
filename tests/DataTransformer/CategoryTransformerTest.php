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

use App\Entity\Category;
use App\Entity\Group;
use App\Form\DataTransformer\CategoryTransformer;
use App\Tests\DatabaseTrait;
use App\Tests\ServiceTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Form\Exception\TransformationFailedException;

/**
 * Test for the {@link CategoryTransformer} class.
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class CategoryTransformerTest extends KernelTestCase
{
    use DatabaseTrait;
    use ServiceTrait;

    private ?Category $category = null;
    private ?Group $group = null;
    private ?CategoryTransformer $transformer = null;

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
        $this->category = $this->createCategory($this->group);
        $this->transformer = new CategoryTransformer($this->getManager());
    }

    /**
     * {@inheritDoc}
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\Exception\ORMException
     */
    protected function tearDown(): void
    {
        $this->category = $this->deleteCategory();
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

    public function testCategoryNotNull(): void
    {
        $this->assertNotNull($this->category);
    }

    public function testGroupNotNull(): void
    {
        $this->assertNotNull($this->group);
    }

    /**
     * @psalm-param int|string|null $value
     * @dataProvider getReverseTransformValues
     */
    public function testReverseTransform(mixed $value, mixed $expected, bool $exception = false): void
    {
        if ($exception) {
            $this->expectException(TransformationFailedException::class);
        }
        $this->assertNotNull($this->transformer);
        $actual = $this->transformer->reverseTransform($value);
        $this->assertEquals($expected, $actual);
    }

    public function testReverseTransformInvalid(): void
    {
        $this->assertNotNull($this->transformer);
        $this->expectException(TransformationFailedException::class);
        $actual = $this->transformer->reverseTransform(-1);
        $this->assertEquals($this->category, $actual);
    }

    public function testReverseTransformValid(): void
    {
        $this->assertNotNull($this->category);
        $this->assertNotNull($this->transformer);
        $actual = $this->transformer->reverseTransform($this->category->getId());
        $this->assertEquals($this->category, $actual);
    }

    /**
     * @dataProvider getTransformValues
     */
    public function testTransform(mixed $value, mixed $expected, bool $exception = false): void
    {
        if ($exception) {
            $this->expectException(TransformationFailedException::class);
        }
        $this->assertNotNull($this->transformer);
        $actual = $this->transformer->transform($value);
        $this->assertEquals($expected, $actual);
    }

    public function testTransformerNotNull(): void
    {
        $this->assertNotNull($this->transformer);
    }

    public function testTransformValid(): void
    {
        $this->assertNotNull($this->category);
        $this->assertNotNull($this->transformer);
        $actual = $this->transformer->transform($this->category);
        $this->assertEquals($this->category->getId(), $actual);
    }

    /**
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\Exception\ORMException
     */
    protected function createCategory(Group $group): Category
    {
        $category = new Category();
        $category->setCode('Test')
            ->setGroup($group);

        $manager = $this->getManager();
        $manager->persist($category);
        $manager->flush();

        return $category;
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
    protected function deleteCategory(): ?Category
    {
        if (null !== $this->category) {
            $manager = $this->getManager();
            $manager->remove($this->category);
            $manager->flush();
            $this->category = null;
        }

        return $this->category;
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
