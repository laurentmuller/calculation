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

namespace App\Tests\Model;

use App\Entity\Calculation;
use App\Entity\CalculationState;
use App\Model\CalculationArchiveResult;
use App\Tests\Entity\IdTrait;
use PHPUnit\Framework\TestCase;

class CalculationArchiveResultTest extends TestCase
{
    use IdTrait;

    /**
     * @throws \ReflectionException
     */
    public function testAddCalculationAndReset(): void
    {
        $result = new CalculationArchiveResult();
        $oldState = $this->createState(1, 'old', true);
        $newState = $this->createState(2, 'new', false);
        $calculation = $this->createCalculation($oldState);
        $result->addCalculation($newState, $calculation);
        self::assertCount(1, $result);
        self::assertTrue($result->isValid());

        $expected = [
            'new' => [
                'state' => $newState,
                'calculations' => [$calculation],
            ],
        ];
        $actual = $result->getResults();
        self::assertSame($expected, $actual);

        $result->reset();
        self::assertCount(0, $result);
        self::assertCount(0, $result->getResults());
        self::assertFalse($result->isValid());

        $expected = [];
        $actual = $result->getResults();
        self::assertSame($expected, $actual);
    }

    public function testConstructor(): void
    {
        $result = new CalculationArchiveResult();
        self::assertCount(0, $result);
        self::assertCount(0, $result->getResults());
        self::assertFalse($result->isValid());
    }

    /**
     * @throws \ReflectionException
     */
    private function createCalculation(CalculationState $state): Calculation
    {
        $calculation = new Calculation();
        $calculation->setState($state);

        return self::setId($calculation);
    }

    /**
     * @throws \ReflectionException
     */
    private function createState(int $id, string $code, bool $editable): CalculationState
    {
        $state = new CalculationState();
        $state->setCode($code)
            ->setEditable($editable);

        return self::setId($state, $id);
    }
}
