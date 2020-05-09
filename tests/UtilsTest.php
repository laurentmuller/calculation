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

use App\Entity\Calculation;
use App\Utils\Utils;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for Utils.
 *
 * @author Laurent Muller
 *
 * @see Utils
 */
class UtilsTest extends TestCase
{
    public function testCapitalize(): void
    {
        $this->assertEquals('Home', Utils::capitalize('hOMe'));
        $this->assertEquals('My home', Utils::capitalize('my hOMe'));
    }

    public function testCompare(): void
    {
        $accessor = Utils::getAccessor();
        $this->assertNotNull($accessor);

        $a = new \stdClass();
        $a->value = 0;
        $a->str = 'd';

        $b = new \stdClass();
        $b->value = 0;
        $b->str = 'd';

        $this->assertEquals(0, Utils::compare($a, $b, 'value', $accessor));
        $this->assertEquals(0, Utils::compare($a, $b, 'str', $accessor));
        //$this->assertEquals(0, Utils::compare($a, $b, 'fake', $accessor));

        $b->value = 10;
        $b->str = 'z';
        $this->assertEquals(-1, Utils::compare($a, $b, 'value', $accessor));
        $this->assertEquals(1, Utils::compare($a, $b, 'value', $accessor, false));
        $this->assertEquals(-1, Utils::compare($a, $b, 'str', $accessor));
        $this->assertEquals(1, Utils::compare($a, $b, 'str', $accessor, false));

        $b->value = -10;
        $b->str = 'a';
        $this->assertEquals(1, Utils::compare($a, $b, 'value', $accessor));
        $this->assertEquals(-1, Utils::compare($a, $b, 'value', $accessor, false));
        $this->assertEquals(1, Utils::compare($a, $b, 'str', $accessor));
        $this->assertEquals(-1, Utils::compare($a, $b, 'str', $accessor, false));

        $a->str = 'fake';
        $b->str = 'FAKE';
        $this->assertEquals(0, Utils::compare($a, $b, 'str', $accessor));
    }

    public function testContains(): void
    {
        $haystack = 'fake';
        $needle = '';
        $this->assertFalse(Utils::contains($haystack, $needle));

        $needle = 'ab';
        $haystack = 'before ab after';
        $this->assertTrue(Utils::contains($haystack, $needle));

        $haystack = 'before AB after';
        $this->assertFalse(Utils::contains($haystack, $needle));
        $this->assertTrue(Utils::contains($haystack, $needle, true));
    }

    public function testEndwith(): void
    {
        $haystack = 'fake';
        $needle = '';
        $this->assertFalse(Utils::endwith($haystack, $needle));

        $needle = 'ke';
        $this->assertTrue(Utils::endwith($haystack, $needle));

        $needle = 'KE';
        $this->assertTrue(Utils::endwith($haystack, $needle, true));

        $haystack = 'faKe';
        $this->assertTrue(Utils::endwith($haystack, $needle, true));
    }

    public function testExportVar(): void
    {
        $this->assertEquals('true', Utils::exportVar(true));
        $this->assertEquals('false', Utils::exportVar(false));

        $this->assertEquals('0', Utils::exportVar(0));
        $this->assertEquals('1000', Utils::exportVar(1000));

        $this->assertEquals('0.0', Utils::exportVar(0.0));
        $this->assertEquals('0.01', Utils::exportVar(0.01));

        $this->assertEquals('"fake"', Utils::exportVar('fake'));
    }

    public function testGetArrayValue(): void
    {
        $this->assertNull(Utils::getArrayValue([], 'fake'));
        $this->assertNull(Utils::getArrayValue([], null));

        $this->assertNull(Utils::getArrayValue(['a'], 'fake'));
        $this->assertNull(Utils::getArrayValue(['a'], null));

        $this->assertEquals('a', Utils::getArrayValue(['k' => 'a'], 'k'));
        $this->assertEquals('b', Utils::getArrayValue(['k' => 'a'], 'f', 'b'));
    }

    public function testGetShortName(): void
    {
        $this->expectException(\ReflectionException::class);
        $this->assertNull(Utils::getShortName('aasassa'));

        $this->assertNull(Utils::getShortName(null));
        $this->assertEquals('UtilsTest', Utils::getShortName($this));
        $this->assertEquals('Calculation', Utils::getShortName(Calculation::class));
    }

    public function testIsString(): void
    {
        $this->assertFalse(Utils::isString(null));
        $this->assertFalse(Utils::isString(''));
        $this->assertTrue(Utils::isString('a'));
    }

    public function testStartwith(): void
    {
        $haystack = 'fake';
        $needle = '';
        $this->assertFalse(Utils::startwith($haystack, $needle));

        $needle = 'fa';
        $this->assertTrue(Utils::startwith($haystack, $needle));

        $needle = 'FA';
        $this->assertTrue(Utils::startwith($haystack, $needle, true));

        $haystack = 'faKe';
        $this->assertTrue(Utils::startwith($haystack, $needle, true));
    }

    public function testToFloat(): void
    {
        $this->assertEquals(0, Utils::toFloat(null));
        $this->assertEquals(0, Utils::toFloat(0));

        $this->assertNotEquals(0, Utils::toFloat(1.0));
        $this->assertNotEquals(1, Utils::toFloat('a'));
    }

    public function testToInt(): void
    {
        $this->assertEquals(0, Utils::toInt(null));
        $this->assertEquals(0, Utils::toInt(0));

        $this->assertNotEquals(0, Utils::toInt(1.0));
        $this->assertNotEquals(1, Utils::toInt('a'));
    }

    public function testToString(): void
    {
        $this->assertEquals('', Utils::toString(null));
        $this->assertEquals('0', Utils::toString(0));

        $this->assertNotEquals(0, Utils::toString(1.0));
        $this->assertNotEquals(1, Utils::toString('a'));
    }
}
