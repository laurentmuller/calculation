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

use App\Generator\CustomerGenerator;

/**
 * @extends GeneratorTestCase<CustomerGenerator>
 */
final class CustomerGeneratorTest extends GeneratorTestCase
{
    public function testNegativeCount(): void
    {
        $generator = $this->createGenerator();
        $actual = $generator->generate(-1, true);
        self::assertValidateResponse($actual, false, 0);
    }

    public function testNotSimulate(): void
    {
        $generator = $this->createGenerator();
        $actual = $generator->generate(1, false);
        self::assertValidateResponse($actual, true, 1);
    }

    public function testOne(): void
    {
        $generator = $this->createGenerator();
        $actual = $generator->generate(1, true);
        self::assertValidateResponse($actual, true, 1);
    }

    public function testType(): void
    {
        $generator = $this->createGenerator();
        $actual = $generator->generate(10, true);
        self::assertValidateResponse($actual, true, 10);
    }

    #[\Override]
    protected function createGenerator(): CustomerGenerator
    {
        $generator = new CustomerGenerator($this->manager, $this->fakerService);

        return $this->updateGenerator($generator);
    }
}
