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

use App\Entity\AbstractEntity;
use App\Form\Extension\FileTypeExtension;
use App\Form\Extension\TextTypeExtension;
use App\Form\Extension\UrlTypeExtension;
use App\Form\Extension\VichImageTypeExtension;
use Symfony\Component\Form\FormTypeInterface;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\PropertyAccess\PropertyAccess;

/**
 * Test for entity type class.
 *
 * @template TEntity of AbstractEntity
 * @template TForm of FormTypeInterface
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
abstract class AbstractEntityTypeTestCase extends TypeTestCase
{
    /**
     * Test.
     */
    public function testSubmitValidData(): void
    {
        /** @psalm-var class-string<TEntity> $className */
        $className = $this->getEntityClass();

        // create model and form
        /** @psalm-var TEntity $model */
        $model = new $className();
        $form = $this->factory->create($this->getFormTypeClass(), $model);

        // populate form data
        $data = $this->getData();
        $expected = $this->populate($className, $data);

        // submit the data to the form directly
        $form->submit($data);

        // check form
        self::assertTrue($form->isSynchronized());

        // check data
        self::assertEquals($expected, $model);

        // check view
        $view = $form->createView();
        $children = $view->children;
        foreach (\array_keys($data) as $key) {
            self::assertArrayHasKey($key, $children);
        }
    }

    /**
     * Gets the data to test.
     *
     * @return array<string, mixed> an array where keys are field
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
     * @return class-string<FormTypeInterface>
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
     * @param class-string<TEntity> $className
     * @param array<string, mixed>  $data
     *
     * @return TEntity
     */
    protected function populate(string $className, array $data): mixed
    {
        /** @psalm-var TEntity $entity */
        $entity = new $className();
        $accessor = PropertyAccess::createPropertyAccessor();
        /** @psalm-var  mixed $value */
        foreach ($data as $key => $value) {
            $accessor->setValue($entity, $key, $value);
        }

        return $entity;
    }
}
