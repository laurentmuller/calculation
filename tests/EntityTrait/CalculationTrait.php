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
use Symfony\Component\Clock\DatePoint;

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
        string $description = 'Test Description',
        ?DatePoint $date = null
    ): Calculation {
        if ($this->calculation instanceof Calculation) {
            return $this->calculation;
        }

        $this->calculation = new Calculation();
        $this->calculation->setState($state ?? $this->getCalculationState())
            ->setCustomer($customer)
            ->setDescription($description);
        if ($date instanceof DatePoint) {
            $this->calculation->setDate($date);
        }

        return $this->addEntity($this->calculation);
    }

    public function updateCalculation(): void
    {
        $calculation = $this->calculation;
        if ($calculation instanceof Calculation) {
            $service = self::getContainer()->get(CalculationUpdateService::class);
            self::assertInstanceOf(CalculationUpdateService::class, $service);
            $service->updateCalculation($calculation);
            $this->addEntity($calculation);
        }
    }

    protected function deleteCalculation(): void
    {
        $this->calculation = $this->deleteEntity($this->calculation);
        $this->deleteCalculationState();
    }
}
