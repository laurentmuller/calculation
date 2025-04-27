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

use App\Entity\Calculation;
use App\Entity\CalculationState;
use App\Service\CalculationUpdateService;

/**
 * Trait to manage a calculation.
 */
trait CalculationTrait
{
    use CalculationStateTrait;

    private ?Calculation $calculation = null;

    public function getCalculation(
        ?CalculationState $state = null,
        string $customer = 'Test Customer',
        string $description = 'Test Description'
    ): Calculation {
        if ($this->calculation instanceof Calculation) {
            return $this->calculation;
        }

        $this->calculation = new Calculation();
        $this->calculation->setState($state ?? $this->getCalculationState())
            ->setCustomer($customer)
            ->setDescription($description);

        return $this->addEntity($this->calculation);
    }

    /**
     * @throws \Exception
     */
    public function updateCalculation(): void
    {
        if ($this->calculation instanceof Calculation) {
            $service = self::getContainer()->get(CalculationUpdateService::class);
            self::assertInstanceOf(CalculationUpdateService::class, $service);
            // @phpstan-ignore-next-line
            $service->updateCalculation($this->calculation);
            // @phpstan-ignore-next-line
            $this->addEntity($this->calculation);
        }
    }

    protected function deleteCalculation(): void
    {
        $this->calculation = $this->deleteEntity($this->calculation);
        $this->deleteCalculationState();
    }
}
