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
        self::assertInstanceOf(Parameter::class, $actual);
        self::assertSame('parameter_name', $actual->name);
        self::assertSame(12346, $actual->default);
    }
}
