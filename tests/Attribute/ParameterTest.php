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

namespace App\Tests\Attribute;

use App\Attribute\Parameter;
use PHPUnit\Framework\TestCase;

final class ParameterTest extends TestCase
{
    /**
     * @throws \ReflectionException
     */
    public function testAttributeFromProperty(): void
    {
        $testedClass = new class {
            #[Parameter('parameter_name', 12346)]
            public string $field = '';
        };
        $class = new \ReflectionClass($testedClass);
        $property = $class->getProperty('field');
        $actual = Parameter::getAttributeFromProperty($property);
        self::assertNotNull($actual);
        self::assertSame(12346, $actual->default);
    }

    /**
     * @throws \ReflectionException
     */
    public function testGetAttributInstance(): void
    {
        $testedClass = new class {
            #[Parameter('parameter_name', 12346)]
            public string $field = '';
        };

        $actual = Parameter::getAttributInstance($testedClass, 'field');
        self::assertInstanceOf(Parameter::class, $actual);
    }

    /**
     * @throws \ReflectionException
     */
    public function testGetAttributInstanceNoAttribute(): void
    {
        $testedClass = new class {
            public string $field = '';
        };

        $actual = Parameter::getAttributInstance($testedClass, 'field');
        self::assertNull($actual);
    }

    /**
     * @throws \ReflectionException
     */
    public function testGetAttributInstancePropertyNoFound(): void
    {
        $testedClass = new class {
            #[Parameter('parameter_name')]
            public string $field = '';
        };

        $actual = Parameter::getAttributInstance($testedClass, 'fake');
        self::assertNull($actual);
    }

    /**
     * @throws \ReflectionException
     */
    public function testGetDefaultValueNotNull(): void
    {
        $testedClass = new class {
            #[Parameter('parameter_name', 123456)]
            public string $field = '';
        };

        $actual = Parameter::getDefaultValue($testedClass, 'field');
        self::assertSame(123456, $actual);
    }

    /**
     * @throws \ReflectionException
     */
    public function testGetDefaultValueNull(): void
    {
        $testedClass = new class {
            #[Parameter('parameter_name')]
            public string $field = '';
        };

        $actual = Parameter::getDefaultValue($testedClass, 'field');
        self::assertNull($actual);
    }

    /**
     * @throws \ReflectionException
     */
    public function testGetName(): void
    {
        $testedClass = new class {
            #[Parameter('parameter_name')]
            public string $field = '';
        };

        $actual = Parameter::getName($testedClass, 'field');
        self::assertSame('parameter_name', $actual);
    }

    /**
     * @throws \ReflectionException
     */
    public function testIsDefaultValueFalse(): void
    {
        $testedClass = new class {
            #[Parameter('parameter_name', 12346)]
            public string $field = '';
        };

        $actual = Parameter::isDefaultValue($testedClass, 'field');
        self::assertFalse($actual);
    }

    /**
     * @throws \ReflectionException
     */
    public function testIsDefaultValueTrue(): void
    {
        $testedClass = new class {
            #[Parameter('parameter_name', 12346)]
            public string $field = '';
        };

        $actual = Parameter::isDefaultValue($testedClass, 'field', 12346);
        self::assertTrue($actual);
    }
}
