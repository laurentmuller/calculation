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
use App\Model\CalculationUpdateResult;
use App\Tests\Entity\IdTrait;
use App\Utils\DateUtils;
use PHPUnit\Framework\TestCase;

class CalculationUpdateResultTest extends TestCase
{
    use IdTrait;

    public function testEmpty(): void
    {
        $result = new CalculationUpdateResult();
        self::assertCount(0, $result);
        self::assertEmpty($result->getResults());
        self::assertFalse($result->isValid());
    }

    /**
     * @throws \ReflectionException
     */
    public function testOne(): void
    {
        $state = new CalculationState();
        $state->setCode('code');
        self::setId($state);

        $date = DateUtils::removeTime();
        $calculation = new Calculation();
        $calculation->setState($state)
            ->setDate($date)
            ->setCustomer('customer')
            ->setDescription('description')
            ->setOverallTotal(100.0);
        self::setId($calculation);

        $result = new CalculationUpdateResult();
        $result->addCalculation(50.0, $calculation);
        self::assertCount(1, $result);
        self::assertCount(1, $result->getResults());
        self::assertTrue($result->isValid());

        $expected = [
            [
                'id' => 1,
                'stateId' => 1,
                'date' => $date,
                'customer' => 'customer',
                'description' => 'description',
                'old_value' => 50.0,
                'new_value' => 100.0,
                'delta' => -50.0,
            ],
        ];
        $actual = $result->getResults();
        self::assertSame($expected, $actual);
    }
}
