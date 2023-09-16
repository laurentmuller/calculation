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
use App\Form\DataTransformer\EntityTransformer;
use App\Repository\CategoryRepository;
use App\Tests\DatabaseTrait;
use App\Tests\ServiceTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Form\Exception\TransformationFailedException;

#[\PHPUnit\Framework\Attributes\CoversClass(EntityTransformer::class)]
class EntityTransformerTest extends KernelTestCase
{
    use DatabaseTrait;
    use ServiceTrait;

    private ?Category $category = null;
    private ?Group $group = null;
    private ?CategoryRepository $repository = null;
    /** @psalm-var EntityTransformer<Category>|null */
    private ?EntityTransformer $transformer = null;

    /**
     * @throws \Doctrine\ORM\Exception\ORMException
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->group = $this->createGroup();
        $this->category = $this->createCategory($this->group);
        $this->transformer = new EntityTransformer($this->getRepository());
    }

    /**
     * @throws \Doctrine\ORM\Exception\ORMException
     */
    protected function tearDown(): void
    {
        $this->category = $this->deleteCategory();
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
    }

    public function testReverseTransformCategory(): void
    {
        self::assertNotNull($this->group);
        self::assertNotNull($this->category);
        self::assertNotNull($this->transformer);
        $actual = $this->transformer->reverseTransform($this->category->getId());
        self::assertSame($this->category, $actual);
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
    }

    public function testTransformCategory(): void
    {
        self::assertNotNull($this->group);
        self::assertNotNull($this->category);
        self::assertNotNull($this->transformer);
        $actual = $this->transformer->transform($this->category);
        self::assertSame($this->category->getId(), $actual);
    }

    /**
     * @throws \Doctrine\ORM\Exception\ORMException
     */
    private function createCategory(Group $group): Category
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
    private function deleteCategory(): null
    {
        if ($this->category instanceof Category) {
            $manager = $this->getManager();
            $manager->remove($this->category);
            $manager->flush();
            $this->category = null;
        }

        return $this->category;
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
    private function getRepository(): CategoryRepository
    {
        if (!$this->repository instanceof CategoryRepository) {
            /** @psalm-var CategoryRepository $repository */
            $repository = $this->getManager()->getRepository(Category::class);
            $this->repository = $repository;
        }

        return $this->repository;
    }
}
