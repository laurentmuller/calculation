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

use App\Parameter\DateParameter;

/**
 * @extends ParameterTestCase<DateParameter>
 */
class DateParameterTest extends ParameterTestCase
{
    #[\Override]
    public static function getParameterNames(): \Generator
    {
        yield ['archive', 'archive_calculation'];
        yield ['import', 'last_import'];
        yield ['updateCalculations', 'update_calculation'];
        yield ['updateProducts', 'update_product'];
    }

    #[\Override]
    public static function getParameterValues(): \Generator
    {
        yield ['archive', null];
        yield ['import', null];
        yield ['updateCalculations', null];
        yield ['updateProducts', null];
    }

    public function testDefaultValue(): void
    {
        self::assertNull($this->parameter->getArchive());
        self::assertNull($this->parameter->getImport());
        self::assertNull($this->parameter->getUpdateCalculations());
        self::assertNull($this->parameter->getUpdateProducts());

        self::assertSame('parameter_date', $this->parameter::getCacheKey());
    }

    public function testSetValue(): void
    {
        $date = new \DateTime();
        $this->parameter->setArchive($date);
        self::assertSame($date, $this->parameter->getArchive());
        $this->parameter->setImport($date);
        self::assertSame($date, $this->parameter->getImport());
        $this->parameter->setUpdateCalculations($date);
        self::assertSame($date, $this->parameter->getUpdateCalculations());
        $this->parameter->setUpdateProducts($date);
        self::assertSame($date, $this->parameter->getUpdateProducts());
    }

    #[\Override]
    protected function createParameter(): DateParameter
    {
        return new DateParameter();
    }
}
