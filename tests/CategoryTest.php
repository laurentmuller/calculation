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

namespace App\Tests;

use App\Entity\Category;
use App\Entity\CategoryMargin;
use Symfony\Component\Validator\Constraints\NotNullValidator;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * Unit test for Category entity.
 *
 * @author Laurent Muller
 */
class CategoryTest extends ConstraintValidatorTestCase
{
    public function testCategory(): void
    {
        $category = new Category();
        $category->addMargin($this->createMargin(0, 100, 0.1));

        $this->assertNotNull($category->findMargin(0));
        $this->assertNull($category->findMargin(100));

        $this->assertEqualsWithDelta(0.1, $category->findPercent(50), 0.01);
        $this->assertEqualsWithDelta(0, $category->findPercent(100), 0.01);
    }

    public function testCategoryInvalidMaximum(): void
    {
        $category = new Category();
        $category->addMargin($this->createMargin(0, 100, 0.1));
        $category->addMargin($this->createMargin(100, 99, 0.2));

        $context = $this->context;
        $category->validate($context, null);
        $violations = $context->getViolations();
        $this->assertEquals(1, $violations->count());

        $violation = $violations->get(0);
        $this->assertEquals('property.path.margins[1].maximum', $violation->getPropertyPath());
    }

    public function testCategoryInvalidMinimum(): void
    {
        $category = new Category();
        $category->addMargin($this->createMargin(0, 100, 0.1));
        $category->addMargin($this->createMargin(99, 200, 0.2));

        $context = $this->context;
        $category->validate($context, null);
        $violations = $context->getViolations();
        $this->assertEquals(1, $violations->count());

        $violation = $violations->get(0);
        $this->assertEquals('property.path.margins[1].minimum', $violation->getPropertyPath());
    }

    public function testCategoryValid(): void
    {
        $category = new Category();
        $category->addMargin($this->createMargin(0, 100, 0.1));
        $category->addMargin($this->createMargin(100, 200, 0.2));

        $context = $this->context;
        $category->validate($context, null);
        $violations = $context->getViolations();
        $this->assertEquals(0, $violations->count());
    }

    //private $container;

    public function testMargins(): void
    {
        $margin = $this->createMargin(0, 100, 0.1);
        $this->assertTrue($margin->containsAmount(0));
        $this->assertFalse($margin->containsAmount(100));
        $this->assertEqualsWithDelta(1.0, $margin->getMarginAmount(10), 0.1);
    }

    protected function createValidator()
    {
        // not used
        return new NotNullValidator();
    }

    private function createMargin(float $minimum, float $maximum, float $margin): CategoryMargin
    {
        $cm = new CategoryMargin();
        $cm->setValues($minimum, $maximum, $margin);

        return $cm;
    }
}
