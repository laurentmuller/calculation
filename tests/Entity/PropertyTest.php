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

/**
 * Unit test for {@link Property} class.
 */
class PropertyTest extends AbstractEntityValidatorTest
{
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

            $this->validate($second, 1);
        } finally {
            $this->deleteEntity($first);
        }
    }

    public function testInvalidBoth(): void
    {
        $object = new Property();

        $result = $this->validator->validate($object);
        $this->assertEquals(2, $result->count());
    }

    public function testInvalidName(): void
    {
        $object = new Property();
        $object->setValue('value');
        $this->validate($object, 1);
    }

    public function testInvalidValue(): void
    {
        $object = new Property();
        $object->setName('name');
        $this->validate($object, 1);
    }

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

            $this->validate($second, 0);
        } finally {
            $this->deleteEntity($first);
        }
    }
}
