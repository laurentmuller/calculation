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
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\PropertyAccess\PropertyAccess;

/**
 * Test for entity type class.
 *
 * @template TEntity of EntityInterface
 * @template TForm of \App\Form\AbstractEntityType<TEntity>
 */
abstract class EntityTypeTestCase extends TypeTestCase
{
    use PreloadedExtensionsTrait;

    /**
     * Test.
     */
    public function testSubmitValidData(): void
    {
        $this->submitValidData();
    }

    protected static function assertDateEquals(\DateTimeInterface $expected, \DateTimeInterface $actual): void
    {
        self::assertSame($expected->format('Y-m-d'), $actual->format('Y-m-d'));
    }

    /**
     * Gets the data under test.
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
     */
    protected function populate(string $entityClass, array $data): EntityInterface
    {
        $entity = new $entityClass();
        $accessor = PropertyAccess::createPropertyAccessor();
        /** @psalm-var mixed $value */
        foreach ($data as $key => $value) {
            $accessor->setValue($entity, $key, $value);
        }

        /** @psalm-var TEntity */
        return $entity;
    }

    protected function submitValidData(): void
    {
        $entityClass = $this->getEntityClass();
        $formTypeClass = $this->getFormTypeClass();

        // create model and form
        $model = new $entityClass();
        $form = $this->factory->create($formTypeClass, $model);

        // populate entity
        $data = $this->getData();
        $entity = $this->populate($entityClass, $data);

        // submit the data to the form directly
        $form->submit($data);

        // check form
        self::assertTrue($form->isSynchronized());

        // check data
        $keys = \array_keys($data);
        $accessor = PropertyAccess::createPropertyAccessor();
        foreach ($keys as $field) {
            /** @psalm-var mixed $expected */
            $expected = $accessor->getValue($entity, $field);
            /** @psalm-var mixed $actual */
            $actual = $accessor->getValue($model, $field);
            $this->validate($expected, $actual);
        }

        // check view
        $children = $form->createView()->children;
        foreach ($keys as $key) {
            self::assertArrayHasKey($key, $children);
        }
    }

    protected function validate(mixed $expected, mixed $actual): void
    {
        if ($expected instanceof \DateTimeInterface && $actual instanceof \DateTimeInterface) {
            self::assertDateEquals($expected, $actual);
        } else {
            self::assertEqualsCanonicalizing($expected, $actual);
        }
    }
}
