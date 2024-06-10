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

namespace App\Tests\Generator;

use App\Generator\AbstractEntityGenerator;
use App\Generator\CustomerGenerator;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * @extends GeneratorTestCase<CustomerGenerator>
 */
#[CoversClass(AbstractEntityGenerator::class)]
#[CoversClass(CustomerGenerator::class)]
class CustomerGeneratorTest extends GeneratorTestCase
{
    public function testNegativeCount(): void
    {
        $generator = $this->createGenerator();
        $actual = $generator->generate(-1, true);
        self::assertValidateResults($actual, false, 0);
    }

    public function testNotSimulate(): void
    {
        $generator = $this->createGenerator();
        $actual = $generator->generate(1, false);
        self::assertValidateResults($actual, true, 1);
    }

    public function testOne(): void
    {
        $generator = $this->createGenerator();
        $actual = $generator->generate(1, true);
        self::assertValidateResults($actual, true, 1);
    }

    protected function createGenerator(): CustomerGenerator
    {
        $generator = new CustomerGenerator($this->manager, $this->fakerService);

        return $this->updateGenerator($generator);
    }
}
