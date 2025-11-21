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
final class DefaultParameterTest extends ParameterTestCase
{
    #[\Override]
    public static function getParameterNames(): \Generator
    {
        yield ['categoryId', 'default_category'];
        yield ['minMargin', 'minimum_margin'];
        yield ['stateId', 'default_state'];
    }

    #[\Override]
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
        self::assertFalse($this->parameter->isMarginBelow(2.0));
        self::assertTrue($this->parameter->isMarginBelow(0.1));
        self::assertFalse($this->parameter->isMarginBelow(new Calculation()));
    }

    public function testResetCategoryId(): void
    {
        $this->parameter->setCategoryId(24);
        self::assertSame(24, $this->parameter->getCategoryId());
        $this->parameter->resetCategoryId();
        self::assertNull($this->parameter->getCategoryId());
    }

    public function testResetStateId(): void
    {
        $this->parameter->setStateId(24);
        self::assertSame(24, $this->parameter->getStateId());
        $this->parameter->resetStateId();
        self::assertNull($this->parameter->getStateId());
    }

    public function testSetValue(): void
    {
        $categoryId = 12;
        $minMargin = 2.0;
        $stateId = 24;

        $this->parameter->setCategoryId($categoryId);
        self::assertSame($categoryId, $this->parameter->getCategoryId());
        $this->parameter->setMinMargin($minMargin);
        self::assertSame($minMargin, $this->parameter->getMinMargin());
        $this->parameter->setStateId($stateId);
        self::assertSame($stateId, $this->parameter->getStateId());
    }

    #[\Override]
    protected function createParameter(): DefaultParameter
    {
        return new DefaultParameter();
    }
}
