<?php
/*
 * This file is part of the Calculation package.
 *
 * (c) bibi.nu. <bibi@bibi.nu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace App\Faker;

use App\Entity\CalculationState;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Calculation state provider.
 *
 * @author Laurent Muller
 *
 * @template-extends EntityProvider<CalculationState>
 */
class CalculationStateProvider extends EntityProvider
{
    /**
     * Constructor.
     */
    public function __construct(Generator $generator, EntityManagerInterface $manager)
    {
        parent::__construct($generator, $manager, CalculationState::class);
    }

    /**
     * Gets a random calculation state.
     */
    public function state(): ?CalculationState
    {
        return $this->entity();
    }

    /**
     * Gets the number of calculation states.
     */
    public function statesCount(): int
    {
        return $this->count();
    }

    /**
     * {@inheritDoc}
     */
    protected function getCriteria(): array
    {
        return ['editable' => true];
    }
}
