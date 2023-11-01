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

use App\Entity\CalculationState;

#[\PHPUnit\Framework\Attributes\CoversClass(CalculationState::class)]
class CalculationStateTest extends AbstractEntityValidatorTestCase
{
    /**
     * @throws \Doctrine\ORM\Exception\ORMException
     */
    public function testDuplicate(): void
    {
        $first = new CalculationState();
        $first->setCode('code');

        try {
            $this->saveEntity($first);
            $second = new CalculationState();
            $second->setCode('code');
            $results = $this->validate($second, 1);
            $this->validatePaths($results, 'code');
        } finally {
            $this->deleteEntity($first);
        }
    }

    public function testInvalidCode(): void
    {
        $state = new CalculationState();
        $results = $this->validate($state, 1);
        $this->validatePaths($results, 'code');
    }

    /**
     * @throws \Doctrine\ORM\Exception\ORMException
     */
    public function testNotDuplicate(): void
    {
        $first = new CalculationState();
        $first->setCode('code');

        try {
            $this->saveEntity($first);
            $second = new CalculationState();
            $second->setCode('code 2');
            $this->validate($second);
        } finally {
            $this->deleteEntity($first);
        }
    }

    public function testValid(): void
    {
        $state = new CalculationState();
        $state->setCode('code');
        $this->validate($state);
    }
}
