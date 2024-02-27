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

namespace App\Tests\Entity;

use App\Entity\Property;

#[\PHPUnit\Framework\Attributes\CoversClass(Property::class)]
class PropertyTest extends AbstractEntityValidatorTestCase
{
    /**
     * @throws \Doctrine\ORM\Exception\ORMException
     */
    public function testDuplicate(): void
    {
        $first = new Property();
        $first->setName('name')
            ->setValue('value');

        try {
            $this->saveEntity($first);
            $second = new Property();
            $second->setName('name')
                ->setValue('value');
            $results = $this->validate($second, 1);
            $this->validatePaths($results, 'name');
        } finally {
            $this->deleteEntity($first);
        }
    }

    public function testInstance(): void
    {
        $object = Property::instance('name');
        self::assertSame('name', $object->getName());
    }

    public function testInvalidBoth(): void
    {
        $object = new Property();
        $results = $this->validate($object, 2);
        $this->validatePaths($results, 'name', 'value');
    }

    public function testInvalidName(): void
    {
        $object = new Property();
        $object->setValue('value');
        $results = $this->validate($object, 1);
        $this->validatePaths($results, 'name');
    }

    public function testInvalidValue(): void
    {
        $object = new Property();
        $object->setName('name');
        $results = $this->validate($object, 1);
        $this->validatePaths($results, 'value');
    }

    /**
     * @throws \Doctrine\ORM\Exception\ORMException
     */
    public function testNotDuplicate(): void
    {
        $first = new Property();
        $first->setName('name')
            ->setValue('value');

        try {
            $this->saveEntity($first);
            $second = new Property();
            $second->setName('name2')
                ->setValue('value');
            $this->validate($second);
        } finally {
            $this->deleteEntity($first);
        }
    }
}
