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

use App\Entity\CalculationState;
use App\Entity\Category;
use App\Parameter\DefaultParameter;

/**
 * @extends ParameterTestCase<DefaultParameter>
 */
class DefaultParameterTest extends ParameterTestCase
{
    public static function getParameterNames(): \Generator
    {
        yield ['category', 'default_category'];
        yield ['minMargin', 'minimum_margin'];
        yield ['state', 'default_state'];
    }

    public static function getParameterValues(): \Generator
    {
        yield ['category', null];
        yield ['minMargin', 1.1];
        yield ['state', null];
    }

    public function testDefaultValue(): void
    {
        self::assertNull($this->parameter->getCategory());
        self::assertSame(1.1, $this->parameter->getMinMargin());
        self::assertNull($this->parameter->getState());

        self::assertSame('parameter_default_value', $this->parameter::getCacheKey());
    }

    public function testSetValue(): void
    {
        $category = new Category();
        $state = new CalculationState();

        $this->parameter->setCategory($category);
        self::assertSame($category, $this->parameter->getCategory());
        $this->parameter->setMinMargin(2.0);
        self::assertSame(2.0, $this->parameter->getMinMargin());
        $this->parameter->setState($state);
        self::assertSame($state, $this->parameter->getState());
    }

    protected function createParameter(): DefaultParameter
    {
        return new DefaultParameter();
    }
}
