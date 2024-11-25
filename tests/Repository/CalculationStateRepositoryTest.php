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

namespace App\Tests\Repository;

use App\Entity\Calculation;
use App\Entity\CalculationState;
use App\Repository\CalculationStateRepository;
use App\Tests\DatabaseTrait;
use App\Tests\EntityTrait\CalculationStateTrait;
use App\Tests\EntityTrait\CalculationTrait;
use App\Tests\KernelServiceTestCase;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\QueryBuilder;

class CalculationStateRepositoryTest extends KernelServiceTestCase
{
    use CalculationStateTrait;
    use CalculationTrait;
    use DatabaseTrait;

    private CalculationStateRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = $this->getService(CalculationStateRepository::class);
    }

    /**
     * @throws ORMException
     */
    protected function tearDown(): void
    {
        $this->deleteCalculation();
        parent::tearDown();
    }

    /**
     * @throws ORMException
     */
    public function testGetCalculations(): void
    {
        $actual = $this->repository->getCalculations();
        self::assertCount(0, $actual);

        $this->getCalculation();
        $actual = $this->repository->getCalculations();
        self::assertCount(1, $actual);
    }

    /**
     * @throws ORMException
     */
    public function testGetDropDown(): void
    {
        $actual = $this->repository->getDropDown();
        self::assertCount(0, $actual);

        $this->getCalculation();
        $actual = $this->repository->getDropDown();
        self::assertCount(0, $actual);

        $state = new CalculationState();
        $state->setCode('My Code');
        $this->addEntity($state);

        $calculation = new Calculation();
        $calculation->setState($state)
            ->setCustomer('customer')
            ->setDescription('description');
        $this->addEntity($calculation);

        try {
            $actual = $this->repository->getDropDown();
            self::assertCount(1, $actual);
        } finally {
            $this->deleteEntity($calculation);
            $this->deleteEntity($state);
        }
    }

    /**
     * @throws ORMException
     */
    public function testGetDropDownBelow(): void
    {
        $actual = $this->repository->getDropDownBelow(0.0);
        self::assertCount(0, $actual);

        $this->getCalculation();
        $actual = $this->repository->getDropDownBelow(0.0);
        self::assertCount(0, $actual);
    }

    /**
     * @throws ORMException
     */
    public function testGetEditableCount(): void
    {
        $actual = $this->repository->getEditableCount();
        self::assertSame(0, $actual);

        $state = $this->getCalculationState();
        $actual = $this->repository->getEditableCount();
        self::assertSame(1, $actual);

        $state->setEditable(false);
        $this->addEntity($state);
        $actual = $this->repository->getEditableCount();
        self::assertSame(0, $actual);
    }

    /**
     * @throws ORMException
     */
    public function testGetNotEditableCount(): void
    {
        $actual = $this->repository->getNotEditableCount();
        self::assertSame(0, $actual);

        $state = $this->getCalculationState();
        $actual = $this->repository->getNotEditableCount();
        self::assertSame(0, $actual);

        $state->setEditable(false);
        $this->addEntity($state);
        $actual = $this->repository->getNotEditableCount();
        self::assertSame(1, $actual);
    }

    public function testGetQueryBuilderByEditable(): void
    {
        $actual = $this->repository->getQueryBuilderByEditable();
        self::assertInstanceOf(QueryBuilder::class, $actual);
    }

    public function testGetTableQueryBuilder(): void
    {
        $actual = $this->repository->getTableQueryBuilder();
        self::assertInstanceOf(QueryBuilder::class, $actual);
    }
}
