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

use App\Entity\Category;
use App\Entity\CategoryMargin;

/**
 * Unit test for validate category constraints.
 *
 * @author Laurent Muller
 */
class CategoryTest extends EntityValidatorTest
{
    public function testCategoryMargin(): void
    {
        $margin = $this->createMargin(0, 100, 0.1);
        $this->assertTrue($margin->containsAmount(0));
        $this->assertFalse($margin->containsAmount(100));
        $this->assertEqualsWithDelta(1.0, $margin->getMarginAmount(10), 0.1);
    }

    public function testDuplicate(): void
    {
        $first = new Category();
        $first->setCode('code');

        try {
            $this->saveEntity($first);

            $second = new Category();
            $second->setCode('code');

            $this->validate($second, 1);
        } finally {
            $this->deleteEntity($first);
        }
    }

    public function testFindMargin(): void
    {
        $category = new Category();
        $category->addMargin($this->createMargin(0, 100, 0.1));
        $this->assertNotNull($category->findMargin(0));
        $this->assertNull($category->findMargin(100));
    }

    public function testFindPercent(): void
    {
        $category = new Category();
        $category->addMargin($this->createMargin(0, 100, 0.1));
        $this->assertEqualsWithDelta(0.1, $category->findPercent(50), 0.01);
        $this->assertEqualsWithDelta(0, $category->findPercent(100), 0.01);
    }

    public function testInvalidCode(): void
    {
        $object = new Category();
        $this->validate($object, 1);
    }

    public function testNotDuplicate(): void
    {
        $first = new Category();
        $first->setCode('code');

        try {
            $this->saveEntity($first);

            $second = new Category();
            $second->setCode('code2');

            $this->validate($second, 0);
        } finally {
            $this->deleteEntity($first);
        }
    }

    public function testValid(): void
    {
        $object = new Category();
        $object->setCode('code');
        $this->validate($object, 0);
    }

    private function createMargin(float $minimum, float $maximum, float $margin): CategoryMargin
    {
        $cm = new CategoryMargin();
        $cm->setValues($minimum, $maximum, $margin);

        return $cm;
    }
}
