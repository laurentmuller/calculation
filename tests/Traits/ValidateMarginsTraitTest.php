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

namespace App\Tests\Traits;

use App\Entity\GlobalMargin;
use App\Model\GlobalMargins;
use Symfony\Component\Validator\Constraints\LengthValidator;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * @extends ConstraintValidatorTestCase<LengthValidator>
 */
class ValidateMarginsTraitTest extends ConstraintValidatorTestCase
{
    public function testMaximumGreaterMinimum(): void
    {
        $margin = $this->createMargin(10.0, 5.0);
        $globalMargins = $this->createGlobalMargins($margin);
        $globalMargins->validateMargins($this->context);
        $this->buildViolation('margin.maximum_greater_minimum')
            ->atPath('property.path.margins[0].maximum')
            ->assertRaised();
    }

    public function testMinimumDiscontinued(): void
    {
        $margin1 = $this->createMargin(1.0, 2.0);
        $margin2 = $this->createMargin(2.1, 3.0);
        $globalMargins = $this->createGlobalMargins($margin1, $margin2);
        $globalMargins->validateMargins($this->context);
        $this->buildViolation('margin.minimum_discontinued')
            ->atPath('property.path.margins[1].minimum')
            ->assertRaised();
    }

    public function testMinimumOverlap(): void
    {
        $margin1 = $this->createMargin(1.0, 2.0);
        $margin2 = $this->createMargin(1.9, 3.0);
        $globalMargins = $this->createGlobalMargins($margin1, $margin2);
        $globalMargins->validateMargins($this->context);
        $this->buildViolation('margin.minimum_overlap')
            ->atPath('property.path.margins[1].minimum')
            ->assertRaised();
    }

    public function testValidEmpty(): void
    {
        $globalMargins = $this->createGlobalMargins();
        $globalMargins->validateMargins($this->context);
        self::assertNoViolation();
    }

    public function testValidOneMargin(): void
    {
        $margin = $this->createMargin();
        $globalMargins = $this->createGlobalMargins($margin);
        $globalMargins->validateMargins($this->context);
        self::assertNoViolation();
    }

    public function testValidTwoMargins(): void
    {
        $margin1 = $this->createMargin(1.0, 2.0);
        $margin2 = $this->createMargin(2.0, 3.0);
        $globalMargins = $this->createGlobalMargins($margin1, $margin2);
        $globalMargins->validateMargins($this->context);
        self::assertNoViolation();
    }

    #[\Override]
    protected function createValidator(): LengthValidator
    {
        return new LengthValidator();
    }

    private function createGlobalMargins(GlobalMargin ...$margins): GlobalMargins
    {
        return new GlobalMargins($margins);
    }

    private function createMargin(float $minimum = 0.0, float $maximum = 1.0): GlobalMargin
    {
        $margin = new GlobalMargin();
        $margin->setMinimum($minimum)
            ->setMaximum($maximum);

        return $margin;
    }
}
