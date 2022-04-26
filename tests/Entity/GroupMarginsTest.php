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

use App\Entity\Group;
use App\Entity\GroupMargin;
use Symfony\Component\Validator\Constraints\NotNullValidator;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * Unit test for {@link App\Entity\Group} class.
 *
 * @see Category
 */
class GroupMarginsTest extends ConstraintValidatorTestCase
{
    public function testInvalidMaximum(): void
    {
        $group = new Group();
        $group->addMargin($this->createMargin(0, 100, 0.1));
        $group->addMargin($this->createMargin(100, 99, 0.2));

        $context = $this->context;
        $group->validateMargins($context);
        $violations = $context->getViolations();
        $this->assertEquals(1, $violations->count());

        $violation = $violations->get(0);
        $this->assertEquals('property.path.margins[1].maximum', $violation->getPropertyPath());
    }

    public function testInvalidMinimum(): void
    {
        $group = new Group();
        $group->addMargin($this->createMargin(0, 100, 0.1));
        $group->addMargin($this->createMargin(99, 200, 0.2));

        $context = $this->context;
        $group->validateMargins($context);
        $violations = $context->getViolations();
        $this->assertEquals(1, $violations->count());

        $violation = $violations->get(0);
        $this->assertEquals('property.path.margins[1].minimum', $violation->getPropertyPath());
    }

    public function testValid(): void
    {
        $group = new Group();
        $group->addMargin($this->createMargin(0, 100, 0.1));
        $group->addMargin($this->createMargin(100, 200, 0.2));

        $context = $this->context;
        $group->validateMargins($context);
        $violations = $context->getViolations();
        $this->assertEquals(0, $violations->count());
    }

    protected function createValidator(): NotNullValidator
    {
        // not used
        return new NotNullValidator();
    }

    private function createMargin(float $minimum, float $maximum, float $margin): GroupMargin
    {
        $entity = new GroupMargin();
        $entity->setValues($minimum, $maximum, $margin);

        return $entity;
    }
}
