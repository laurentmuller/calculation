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

namespace App\Faker;

use App\Entity\CalculationState;
use App\Repository\CalculationStateRepository;

/**
 * Calculation state provider.
 *
 * @template-extends EntityProvider<CalculationState>
 */
class CalculationStateProvider extends EntityProvider
{
    public function __construct(Generator $generator, CalculationStateRepository $repository)
    {
        parent::__construct($generator, $repository);
    }

    /**
     * Gets a random calculation state.
     */
    public function state(): ?CalculationState
    {
        return $this->entity();
    }

    protected function getCriteria(): array
    {
        return ['editable' => true];
    }
}
