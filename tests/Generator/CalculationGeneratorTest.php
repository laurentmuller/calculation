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

use App\Entity\CalculationState;
use App\Generator\CalculationGenerator;
use App\Service\CalculationUpdateService;
use App\Tests\EntityTrait\CalculationStateTrait;
use App\Tests\EntityTrait\ProductTrait;

/**
 * @extends GeneratorTestCase<CalculationGenerator>
 */
class CalculationGeneratorTest extends GeneratorTestCase
{
    use CalculationStateTrait;
    use ProductTrait;

    private CalculationUpdateService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = $this->getService(CalculationUpdateService::class);
    }

    public function testNegativeCount(): void
    {
        $generator = $this->createGenerator();
        $actual = $generator->generate(-1, true);
        self::assertValidateResponse($actual, false, 0);
    }

    public function testNotSimulate(): void
    {
        $this->getCalculationState();
        $product = $this->getProduct();
        $product->setPrice(0.0);
        $this->manager->flush();

        $generator = $this->createGenerator();
        $actual = $generator->generate(1, false);
        self::assertValidateResponse($actual, true, 1);
    }

    public function testNotSimulateError(): void
    {
        $this->deleteCalculationState();
        $generator = $this->createGenerator();
        $actual = $generator->generate(1, false);
        $actual = self::assertValidateResponse($actual, false, 0);
        self::assertArrayHasKey('exception', $actual);
        self::assertIsArray($actual['exception']);

        $actual = $actual['exception'];
        self::assertArrayHasKey('message', $actual);
        self::assertArrayHasKey('code', $actual);
        self::assertArrayHasKey('file', $actual);
        self::assertArrayHasKey('line', $actual);
        self::assertArrayHasKey('class', $actual);
        self::assertArrayHasKey('trace', $actual);
    }

    public function testOne(): void
    {
        $generator = $this->createGenerator();
        $actual = $generator->generate(1, true);
        self::assertValidateResponse($actual, true, 1);
    }

    protected function createGenerator(): CalculationGenerator
    {
        $generator = new CalculationGenerator($this->manager, $this->fakerService, $this->service);

        return $this->updateGenerator($generator);
    }

    private function deleteCalculationState(): void
    {
        $repository = $this->manager->getRepository(CalculationState::class);
        $entities = $repository->findAll();
        foreach ($entities as $entity) {
            $repository->remove($entity);
        }
        $this->manager->flush();
    }
}
