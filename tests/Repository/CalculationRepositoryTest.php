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

use App\Entity\CalculationState;
use App\Entity\User;
use App\Interfaces\SortModeInterface;
use App\Repository\CalculationRepository;
use App\Tests\DatabaseTrait;
use App\Tests\DateAssertTrait;
use App\Tests\Entity\IdTrait;
use App\Tests\EntityTrait\CalculationTrait;
use App\Tests\EntityTrait\ProductTrait;
use App\Tests\KernelServiceTestCase;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Clock\DatePoint;

class CalculationRepositoryTest extends KernelServiceTestCase
{
    use CalculationTrait;
    use DatabaseTrait;
    use DateAssertTrait;
    use IdTrait;
    use ProductTrait;

    private CalculationRepository $repository;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = $this->getService(CalculationRepository::class);
    }

    #[\Override]
    protected function tearDown(): void
    {
        $this->deleteCalculation();
        parent::tearDown();
    }

    public function testAddBelowFilter(): void
    {
        $builder = $this->repository->createDefaultQueryBuilder();
        CalculationRepository::addBelowFilter($builder, 1.1);
        self::expectNotToPerformAssertions();
    }

    public function testCountDistinctMonths(): void
    {
        $actual = $this->repository->countDistinctMonths();
        self::assertSame(0, $actual);
    }

    public function testCountItemsBelow(): void
    {
        $actual = $this->repository->countItemsBelow(1.0);
        self::assertSame(0, $actual);
    }

    public function testCountItemsDuplicate(): void
    {
        $actual = $this->repository->countItemsDuplicate();
        self::assertSame(0, $actual);
    }

    public function testCountItemsEmpty(): void
    {
        $actual = $this->repository->countItemsEmpty();
        self::assertSame(0, $actual);
    }

    /**
     * @throws \ReflectionException
     */
    public function testCountStateReferences(): void
    {
        $state = new CalculationState();
        self::setId($state);

        $actual = $this->repository->countStateReferences($state);
        self::assertSame(0, $actual);
    }

    public function testCreateDefaultQueryBuilder(): void
    {
        $actual = $this->repository->createDefaultQueryBuilder();
        self::assertInstanceOf(QueryBuilder::class, $actual);
    }

    public function testGetByInterval(): void
    {
        $from = new DatePoint();
        $to = new DatePoint();
        $actual = $this->repository->getByInterval($from, $to);
        self::assertEmpty($actual);
    }

    /**
     * @throws \Exception
     */
    public function testGetByMonth(): void
    {
        $actual = $this->repository->getByMonth();
        self::assertEmpty($actual);

        $actual = $this->repository->getByMonth(12);
        self::assertEmpty($actual);

        $this->getCalculation();
        $actual = $this->repository->getByMonth(12);
        self::assertCount(1, $actual);
    }

    public function testGetCalendarYears(): void
    {
        $actual = $this->repository->getCalendarYears();
        self::assertEmpty($actual);
    }

    public function testGetCalendarYearsMonths(): void
    {
        $actual = $this->repository->getCalendarYearsMonths();
        self::assertEmpty($actual);
    }

    public function testGetCalendarYearsWeeks(): void
    {
        $actual = $this->repository->getCalendarYearsWeeks();
        self::assertEmpty($actual);
    }

    public function testGetForMonth(): void
    {
        $actual = $this->repository->getForMonth(2024, 1);
        self::assertEmpty($actual);
    }

    public function testGetForWeek(): void
    {
        $actual = $this->repository->getForWeek(2024, 1);
        self::assertEmpty($actual);
    }

    public function testGetForYear(): void
    {
        $actual = $this->repository->getForYear(2024);
        self::assertEmpty($actual);
    }

    public function testGetItemsBelow(): void
    {
        $actual = $this->repository->getItemsBelow(1.1);
        self::assertEmpty($actual);
    }

    public function testGetItemsDuplicate(): void
    {
        $actual = $this->repository->getItemsDuplicate();
        self::assertEmpty($actual);

        $calculation = $this->getCalculation();
        $product = $this->getProduct();
        $calculation->addProduct($product);
        $calculation->addProduct($product);
        $this->addEntity($calculation);

        $actual = $this->repository->getItemsDuplicate('stateCode', SortModeInterface::SORT_ASC);
        self::assertCount(1, $actual);
    }

    public function testGetItemsEmpty(): void
    {
        $actual = $this->repository->getItemsEmpty();
        self::assertEmpty($actual);

        $calculation = $this->getCalculation();
        $product = $this->getProduct();
        $calculation->addProduct($product, 0.0);
        $this->addEntity($calculation);

        $actual = $this->repository->getItemsEmpty();
        self::assertCount(1, $actual);
    }

    /**
     * @throws \ReflectionException
     */
    public function testGetLastCalculations(): void
    {
        $actual = $this->repository->getLastCalculations(6);
        self::assertEmpty($actual);

        $user = new User();
        $user->setUsername('fake');
        self::setId($user);
        $actual = $this->repository->getLastCalculations(6, $user);
        self::assertEmpty($actual);

        $this->getCalculation();
        $actual = $this->repository->getLastCalculations(6);
        self::assertCount(1, $actual);
    }

    /**
     * @throws \Exception
     */
    public function testGetMinMaxDates(): void
    {
        $actual = $this->repository->getMinMaxDates();
        self::assertCount(2, $actual);
        self::assertNull($actual[0]);
        self::assertNull($actual[1]);

        $date = new DatePoint('2024-01-01');
        $calculation = $this->getCalculation();
        $calculation->setDate($date);
        $this->addEntity($calculation);

        $expected = $calculation->getDate();

        $actual = $this->repository->getMinMaxDates();
        self::assertCount(2, $actual);
        self::assertTimestampEquals($expected, $actual[0]);
        self::assertTimestampEquals($expected, $actual[1]);
    }

    public function testGetPivot(): void
    {
        $actual = $this->repository->getPivot();
        self::assertEmpty($actual);
    }

    public function testGetSearchFields(): void
    {
        $actual = $this->repository->getSearchFields('date');
        self::assertSame("DATE_FORMAT(e.date, '%d.%m.%Y')", $actual);

        $actual = $this->repository->getSearchFields('overallMargin');
        self::assertSame('IFELSE(e.itemsTotal != 0, ROUND((100 * e.overallTotal / e.itemsTotal) - 0.5, 0) / 1, 0)', $actual);

        $actual = $this->repository->getSearchFields('state.id');
        self::assertSame('s.id', $actual);
        $actual = $this->repository->getSearchFields('stateCode');
        self::assertSame('s.code', $actual);
        $actual = $this->repository->getSearchFields('state.code');
        self::assertSame('s.code', $actual);

        $actual = $this->repository->getSearchFields('stateColor');
        self::assertSame('s.color', $actual);
        $actual = $this->repository->getSearchFields('state.color');
        self::assertSame('s.color', $actual);

        $actual = $this->repository->getSearchFields('stateEditable');
        self::assertSame('s.editable', $actual);
        $actual = $this->repository->getSearchFields('state.editable');
        self::assertSame('s.editable', $actual);
    }

    public function testGetSortFields(): void
    {
        $actual = $this->repository->getSortField('overallMargin');
        self::assertSame('IFELSE(e.itemsTotal != 0, ROUND((100 * e.overallTotal / e.itemsTotal) - 0.5, 0) / 1, 0)', $actual);

        $actual = $this->repository->getSortField('stateId');
        self::assertSame('s.code', $actual);
        $actual = $this->repository->getSortField('state_id');
        self::assertSame('s.code', $actual);
        $actual = $this->repository->getSortField('state.id');
        self::assertSame('s.code', $actual);
        $actual = $this->repository->getSortField('code');
        self::assertSame('s.code', $actual);
        $actual = $this->repository->getSortField('stateCode');
        self::assertSame('s.code', $actual);
        $actual = $this->repository->getSortField('state_code');
        self::assertSame('s.code', $actual);
        $actual = $this->repository->getSortField('state.code');
        self::assertSame('s.code', $actual);

        $actual = $this->repository->getSortField('color');
        self::assertSame('s.color', $actual);
        $actual = $this->repository->getSortField('stateColor');
        self::assertSame('s.color', $actual);
        $actual = $this->repository->getSortField('state_color');
        self::assertSame('s.color', $actual);
        $actual = $this->repository->getSortField('state.color');
        self::assertSame('s.color', $actual);

        $actual = $this->repository->getSortField('editable');
        self::assertSame('s.editable', $actual);
        $actual = $this->repository->getSortField('stateEditable');
        self::assertSame('s.editable', $actual);
        $actual = $this->repository->getSortField('state_editable');
        self::assertSame('s.editable', $actual);
        $actual = $this->repository->getSortField('state.editable');
        self::assertSame('s.editable', $actual);
    }
}
