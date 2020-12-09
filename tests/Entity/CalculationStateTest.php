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

namespace App\Tests\Entity;

use App\Entity\CalculationState;

/**
 * Unit test for validate CalculationState constraints.
 *
 * @author Laurent Muller
 */
class CalculationStateTest extends EntityValidatorTest
{
    public function testDuplicate(): void
    {
        $first = new CalculationState();
        $first->setCode('code');

        try {
            $this->saveEntity($first);

            $second = new CalculationState();
            $second->setCode('code');

            $this->validate($second, 1);
        } finally {
            $this->deleteEntity($first);
        }
    }

    public function testInvalidAll(): void
    {
        $state = new CalculationState();
        $this->validate($state, 1);
    }

    public function testNotDuplicate(): void
    {
        $first = new CalculationState();
        $first->setCode('code');

        try {
            $this->saveEntity($first);

            $second = new CalculationState();
            $second->setCode('code 2');

            $this->validate($second, 0);
        } finally {
            $this->deleteEntity($first);
        }
    }

    public function testValid(): void
    {
        $state = new CalculationState();
        $state->setCode('code');
        $this->validate($state, 0);
    }
}
