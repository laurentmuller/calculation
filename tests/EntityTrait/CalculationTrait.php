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
use App\Service\CalculationService;

/**
 * Trait to manage a calculation.
 */
trait CalculationTrait
{
    private ?Calculation $calculation = null;

    /**
     * @throws \Doctrine\ORM\Exception\ORMException
     */
    public function getCalculation(CalculationState $state, string $customer = 'Test Customer', string $description = 'Test Description'): Calculation
    {
        if (!$this->calculation instanceof Calculation) {
            $this->calculation = new Calculation();
            $this->calculation->setState($state)
                ->setCustomer($customer)
                ->setDescription($description);
            $this->addEntity($this->calculation);
        }

        return $this->calculation; // @phpstan-ignore-line
    }

    /**
     * @throws \Doctrine\ORM\Exception\ORMException
     * @throws \Exception
     */
    public function updateCalculation(): void
    {
        if ($this->calculation instanceof Calculation) {
            $service = self::getContainer()->get(CalculationService::class);
            // @phpstan-ignore-next-line
            $service->updateTotal($this->calculation);
            $this->addEntity($this->calculation);
        }
    }

    /**
     * @throws \Doctrine\ORM\Exception\ORMException
     */
    protected function deleteCalculation(): void
    {
        if ($this->calculation instanceof Calculation) {
            $this->calculation = $this->deleteEntity($this->calculation);
        }
    }
}
