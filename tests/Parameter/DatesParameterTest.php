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

use App\Parameter\DatesParameter;
use Symfony\Component\Clock\DatePoint;

/**
 * @extends ParameterTestCase<DatesParameter>
 */
final class DatesParameterTest extends ParameterTestCase
{
    #[\Override]
    public static function getParameterNames(): \Generator
    {
        yield ['archiveCalculations', 'archive_calculation'];
        yield ['lastImport', 'last_import'];
        yield ['updateCalculations', 'update_calculation'];
        yield ['updateProducts', 'update_product'];
    }

    #[\Override]
    public static function getParameterValues(): \Generator
    {
        yield ['archiveCalculations', null];
        yield ['lastImport', null];
        yield ['updateCalculations', null];
        yield ['updateProducts', null];
    }

    public function testDefaultValue(): void
    {
        self::assertNull($this->parameter->getArchiveCalculations());
        self::assertNull($this->parameter->getLastImport());
        self::assertNull($this->parameter->getUpdateCalculations());
        self::assertNull($this->parameter->getUpdateProducts());

        self::assertSame('parameter_dates', $this->parameter::getCacheKey());
    }

    public function testSetValue(): void
    {
        $date = new DatePoint();
        $this->parameter->setArchiveCalculations($date);
        self::assertSame($date, $this->parameter->getArchiveCalculations());
        $this->parameter->setLastImport($date);
        self::assertSame($date, $this->parameter->getLastImport());
        $this->parameter->setUpdateCalculations($date);
        self::assertSame($date, $this->parameter->getUpdateCalculations());
        $this->parameter->setUpdateProducts($date);
        self::assertSame($date, $this->parameter->getUpdateProducts());
    }

    #[\Override]
    protected function createParameter(): DatesParameter
    {
        return new DatesParameter();
    }
}
