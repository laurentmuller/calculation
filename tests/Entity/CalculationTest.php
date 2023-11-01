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

#[\PHPUnit\Framework\Attributes\CoversClass(Calculation::class)]
class CalculationTest extends AbstractEntityValidatorTestCase
{
    public function testInvalidAll(): void
    {
        $calculation = new Calculation();
        $results = $this->validate($calculation, 3);
        $this->validatePaths($results, 'customer', 'description', 'state');
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
        $results = $this->validate($calculation, 1);
        $this->validatePaths($results, 'description');
    }

    public function testInvalidState(): void
    {
        $calculation = new Calculation();
        $calculation->setDescription('my description')
            ->setCustomer('my customer');
        $results = $this->validate($calculation, 1);
        $this->validatePaths($results, 'state');
    }

    public function testValid(): void
    {
        $calculation = new Calculation();
        $calculation->setDescription('my description')
            ->setCustomer('my customer')
            ->setState($this->getState());
        $this->validate($calculation);
    }

    private function getState(): CalculationState
    {
        $state = new CalculationState();
        $state->setCode('my code');

        return $state;
    }
}
