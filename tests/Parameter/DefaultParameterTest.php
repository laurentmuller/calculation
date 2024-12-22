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

namespace App\Tests\Parameter;

use App\Entity\Calculation;
use App\Parameter\DefaultParameter;

/**
 * @extends ParameterTestCase<DefaultParameter>
 */
class DefaultParameterTest extends ParameterTestCase
{
    public static function getParameterNames(): \Generator
    {
        yield ['categoryId', 'default_category'];
        yield ['minMargin', 'minimum_margin'];
        yield ['stateId', 'default_state'];
    }

    public static function getParameterValues(): \Generator
    {
        yield ['categoryId', null];
        yield ['minMargin', 1.1];
        yield ['stateId', null];
    }

    public function testDefaultValue(): void
    {
        self::assertNull($this->parameter->getCategoryId());
        self::assertSame(1.1, $this->parameter->getMinMargin());
        self::assertNull($this->parameter->getStateId());

        self::assertSame('parameter_default_value', $this->parameter::getCacheKey());
    }

    public function testMarginBelow(): void
    {
        self::assertFalse($this->parameter->isMarginBelow(0.0));
        self::assertTrue($this->parameter->isMarginBelow(0.1));
        $calculation = new Calculation();
        self::assertFalse($this->parameter->isMarginBelow($calculation));
    }

    public function testSetValue(): void
    {
        $categoryId = 12;
        $stateId = 24;

        $this->parameter->setCategoryId($categoryId);
        self::assertSame($categoryId, $this->parameter->getCategoryId());
        $this->parameter->setMinMargin(2.0);
        self::assertSame(2.0, $this->parameter->getMinMargin());
        $this->parameter->setStateId($stateId);
        self::assertSame($stateId, $this->parameter->getStateId());
    }

    protected function createParameter(): DefaultParameter
    {
        return new DefaultParameter();
    }
}
