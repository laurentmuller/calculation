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
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(CalculationState::class)]
class CalculationStateTest extends AbstractEntityValidatorTestCase
{
    public function testCalculations(): void
    {
        $state = new CalculationState();
        self::assertSame(0, $state->countCalculations());
        self::assertCount(0, $state->getCalculations());
        self::assertFalse($state->hasCalculations());
    }

    public function testClone(): void
    {
        $state = new CalculationState();
        $state->setCode('code');

        $clone = $state->clone();
        self::assertSame($state->getCode(), $clone->getCode());

        $clone = $state->clone('new-state');
        self::assertNotSame($state->getCode(), $clone->getCode());
        self::assertSame('new-state', $clone->getCode());
    }

    public function testColor(): void
    {
        $state = new CalculationState();
        self::assertSame('#000000', $state->getColor());

        $state->setColor('#010203');
        self::assertSame('#010203', $state->getColor());
    }

    public function testDescription(): void
    {
        $state = new CalculationState();
        self::assertNull($state->getDescription());

        $state->setDescription('description');
        self::assertSame('description', $state->getDescription());
    }

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

    public function testEditable(): void
    {
        $state = new CalculationState();
        self::assertTrue($state->isEditable());

        $state->setEditable(false);
        self::assertFalse($state->isEditable());
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
