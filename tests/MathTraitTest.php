<?php
/*
 * This file is part of the Calculation package.
 *
 * Copyright (c) 2019 bibi.nu. All rights reserved.
 *
 * This computer code is protected by copyright law and international
 * treaties. Unauthorised reproduction or distribution of this code, or
 * any portion of it, may result in severe civil and criminal penalties,
 * and will be prosecuted to the maximum extent possible under the law.
 */

declare(strict_types=1);

namespace App\Tests;

use App\Traits\MathTrait;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for MathTrait.
 *
 * @author Laurent Muller
 *
 * @see MathTrait
 */
class MathTraitTest extends TestCase
{
    use MathTrait;

    public function testBitTest(): void
    {
        $this->assertTrue($this->isBitSet(31, 1));
        $this->assertTrue($this->isBitSet(31, 2));
        $this->assertTrue($this->isBitSet(31, 4));
        $this->assertTrue($this->isBitSet(31, 8));
        $this->assertTrue($this->isBitSet(31, 16));

        $this->assertFalse($this->isBitSet(0, 1));
        $this->assertFalse($this->isBitSet(0, 2));
        $this->assertFalse($this->isBitSet(0, 4));
        $this->assertFalse($this->isBitSet(0, 8));
        $this->assertFalse($this->isBitSet(0, 16));
    }

    public function testFloatEquals(): void
    {
        $this->assertTrue($this->isFloatEquals(0, 0));
        $this->assertFalse($this->isFloatEquals(1, 0));

        $this->assertTrue($this->isFloatZero(0));
        $this->assertFalse($this->isFloatZero(1));
    }

    public function testRound(): void
    {
        $this->assertEquals(0, $this->round(0));
        $this->assertEquals(1.5, $this->round(1.5));
        $this->assertEquals(1.55, $this->round(1.55));
        $this->assertEquals(1.55, $this->round(1.5545));
        $this->assertEquals(1.50, $this->round(1.52, 1));
    }

    public function testSafeDivide(): void
    {
        $this->assertEquals(0, $this->safeDivide(100, 0));
        $this->assertEquals(10, $this->safeDivide(100, 10));
        $this->assertEquals(11, $this->safeDivide(100, 0, 11));
    }

    public function testValidateIntRange(): void
    {
        $this->assertEquals(0, $this->validateIntRange(0, 0, 100));
        $this->assertEquals(0, $this->validateIntRange(-1, 0, 100));
        $this->assertEquals(100, $this->validateIntRange(101, 0, 100));
    }
}
