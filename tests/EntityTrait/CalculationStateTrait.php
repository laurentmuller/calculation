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

namespace App\Tests\EntityTrait;

use App\Entity\CalculationState;

/**
 * Trait to manage a calculation state.
 */
trait CalculationStateTrait
{
    private ?CalculationState $calculationState = null;

    /**
     * @throws \Doctrine\ORM\Exception\ORMException
     */
    protected function deleteCalculationState(): void
    {
        if ($this->calculationState instanceof CalculationState) {
            $this->calculationState = $this->deleteEntity($this->calculationState);
        }
    }

    /**
     * @throws \Doctrine\ORM\Exception\ORMException
     */
    protected function getCalculationState(string $code = 'Test State'): CalculationState
    {
        if ($this->calculationState instanceof CalculationState) {
            return $this->calculationState;
        }

        $this->calculationState = new CalculationState();
        $this->calculationState->setCode($code);

        return $this->addEntity($this->calculationState);
    }
}
