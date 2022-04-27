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

use App\Entity\Calculation;
use App\Entity\CalculationState;

/**
 * Unit test for {@link Calculation} class.
 */
class CalculationTest extends AbstractEntityValidatorTest
{
    public function testInvalidAll(): void
    {
        $calculation = new Calculation();
        $this->validate($calculation, 3);
    }

    public function testInvalidCustomer(): void
    {
        $calculation = new Calculation();
        $calculation->setDescription('my description')
            ->setState($this->getState());
        $this->validate($calculation, 1);
    }

    public function testInvalidDescription(): void
    {
        $calculation = new Calculation();
        $calculation->setCustomer('my customer')
            ->setState($this->getState());
        $this->validate($calculation, 1);
    }

    public function testInvalidState(): void
    {
        $calculation = new Calculation();
        $calculation->setDescription('my description')
            ->setCustomer('my customer');
        $this->validate($calculation, 1);
    }

    public function testValid(): void
    {
        $calculation = new Calculation();
        $calculation->setDescription('my description')
            ->setCustomer('my customer')
            ->setState($this->getState());
        $this->validate($calculation, 0);
    }

    private function getState(): CalculationState
    {
        $state = new CalculationState();
        $state->setCode('my code');

        return $state;
    }
}
