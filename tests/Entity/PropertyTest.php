<?php
/*
 * This file is part of the Calculation package.
 *
 * Copyright (c) 2019 bibi.nu. All rights reserved.
 *
 * This computer code is protected by copyright law and international
 * treaties. Unauthorised reproduction or distribution of this code, or
 * any portion of it, may result in severe civil and criminal penalties,
 * and will be prosecuted to the maximum extent possible under the law.
 */

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\Property;

/**
 * Unit test for validate property constraints.
 *
 * @author Laurent Muller
 */
class PropertyTest extends EntityValidatorTest
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
        $this->assertSame(2, $result->count());
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
