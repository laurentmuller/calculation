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

namespace App\Tests\Form;

use App\Form\Extension\FileTypeExtension;
use App\Form\Extension\TextTypeExtension;
use App\Form\Extension\UrlTypeExtension;
use App\Form\Extension\VichImageTypeExtension;
use App\Interfaces\EntityInterface;
use App\Repository\AbstractRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\PropertyAccess\PropertyAccess;

/**
 * Test for entity type class.
 *
 * @template TEntity of EntityInterface
 * @template TForm of \App\Form\AbstractEntityType<TEntity>
 */
abstract class AbstractEntityTypeTestCase extends TypeTestCase
{
    /**
     * Test.
     */
    public function testSubmitValidData(): void
    {
        $entityClass = $this->getEntityClass();
        $formTypeClass = $this->getFormTypeClass();

        // create model and form
        $model = new $entityClass();
        $form = $this->factory->create($formTypeClass, $model);

        // populate form data
        $data = $this->getData();
        $expected = $this->populate($entityClass, $data);

        // submit the data to the form directly
        $form->submit($data);

        // check form
        self::assertTrue($form->isSynchronized());

        // check data
        self::assertEqualsCanonicalizing($expected, $model);

        // check view
        $view = $form->createView();
        $children = $view->children;
        foreach (\array_keys($data) as $key) {
            self::assertArrayHasKey($key, $children);
        }
    }

    /**
     * @throws Exception
     */
    protected function createEntityManager(): MockObject&EntityManager
    {
        $manager = $this->createMock(EntityManager::class);
        $manager->method('getClassMetadata')
            ->willReturn(new ClassMetadata($this->getEntityClass()));

        return $manager;
    }

    /**
     * @throws Exception
     */
    protected function createQuery(): MockObject&AbstractQuery
    {
        $query = $this->createMock(AbstractQuery::class);
        $query->method('getSQL')
            ->willReturn('FakeSQL');

        return $query;
    }

    /**
     * @throws Exception
     */
    protected function createQueryBuilder(AbstractQuery $query): MockObject&QueryBuilder
    {
        $parameters = new ArrayCollection();
        $builder = $this->createMock(QueryBuilder::class);
        $builder->method('getParameters')
            ->willReturn($parameters);
        $builder->method('getQuery')
            ->willReturn($query);

        return $builder;
    }

    /**
     * @throws Exception
     */
    protected function createRegistry(EntityManager $manager): MockObject&ManagerRegistry
    {
        $registry = $this->createMock(ManagerRegistry::class);
        $registry->method('getManagerForClass')
            ->willReturn($manager);

        return $registry;
    }

    /**
     * @template TRepository of AbstractRepository
     *
     * @param class-string<TRepository> $repositoryClass
     *
     * @return MockObject&TRepository
     *
     * @throws Exception
     */
    protected function createRepository(string $repositoryClass): MockObject&AbstractRepository
    {
        return $this->createMock($repositoryClass);
    }

    /**
     * Gets the data to test.
     *
     * @return array<string, mixed> an array where keys are field names
     */
    abstract protected function getData(): array;

    /**
     * Gets the entity class name.
     *
     * @return class-string<TEntity>
     */
    abstract protected function getEntityClass(): string;

    /**
     * Gets the form type class name.
     *
     * @return class-string<TForm>
     */
    abstract protected function getFormTypeClass(): string;

    protected function getTypeExtensions(): array
    {
        return [
            new FileTypeExtension(),
            new TextTypeExtension(),
            new UrlTypeExtension(),
            new VichImageTypeExtension(),
        ];
    }

    /**
     * Update the given entity with the given data.
     *
     * @psalm-param class-string<TEntity> $entityClass
     * @psalm-param array<string, mixed>  $data
     *
     * @psalm-return TEntity
     *
     * @psalm-suppress InvalidReturnStatement
     * @psalm-suppress InvalidReturnType
     */
    protected function populate(string $entityClass, array $data): EntityInterface
    {
        $entity = new $entityClass();
        $accessor = PropertyAccess::createPropertyAccessor();
        /** @psalm-var mixed $value */
        foreach ($data as $key => $value) {
            $accessor->setValue($entity, $key, $value);
        }

        return $entity;
    }
}
