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

namespace App\Tests\Enum;

use MyCLabs\Enum\Enum;
use PHPUnit\Framework\TestCase;

/**
 * Abstract unit test for an extended MyCLabs\Enum\Enum class.
 *
 * @author Laurent Muller
 */
abstract class AbstractEnumTest extends TestCase
{
    /**
     * Gets the enumerations to test.
     *
     * Each entry must contains the enumeration value and the enumeration to be tested.
     */
    abstract public function getEnumerations(): array;

    /**
     * Test for an enumeration value.
     *
     * @param mixed $value the expected value for the enumeration
     * @param Enum  $enum  the enumeration value to test
     *
     * @dataProvider getEnumerations
     */
    public function testEnum($value, Enum $enum): void
    {
        // test value
        $this->assertTrue($enum::isValid($value));

        // test value and enum as string
        $this->assertEquals((string) $value, (string) $enum);

        // test search
        $this->assertNotFalse($enum::search($value));

        // test json serialize
        $this->assertEquals($value, $enum->jsonSerialize());

        // test instance creation
        $class = \get_class($enum);
        $instance = new $class($value);
        $this->assertEquals($enum, $instance);
        $this->assertTrue($enum->equals($instance));
    }
}
