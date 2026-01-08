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
use App\Entity\User;
use App\Interfaces\SortModeInterface;
use App\Repository\CalculationRepository;
use App\Tests\DateAssertTrait;
use App\Tests\Entity\IdTrait;
use App\Tests\EntityTrait\CalculationTrait;
use App\Tests\EntityTrait\ProductTrait;
use Symfony\Component\Clock\DatePoint;

/**
 * @extends AbstractRepositoryTestCase<Calculation, CalculationRepository>
 */
final class CalculationRepositoryTest extends AbstractRepositoryTestCase
{
    use CalculationTrait;
    use DateAssertTrait;
    use IdTrait;
    use ProductTrait;

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

    public function testCountStateReferences(): void
    {
        $state = new CalculationState();
        self::setId($state);

        $actual = $this->repository->countStateReferences($state);
        self::assertSame(0, $actual);
    }

    public function testCreateDefaultQueryBuilder(): void
    {
        $this->repository->createDefaultQueryBuilder();
        self::expectNotToPerformAssertions();
    }

    public function testFindOneByIdFound(): void
    {
        $id = $this->getCalculation()->getId();
        $actual = $this->repository->findOneById((int) $id);
        self::assertNotNull($actual);
    }

    public function testFindOneByIdNotFound(): void
    {
        $actual = $this->repository->findOneById(0);
        self::assertNull($actual);
    }

    public function testGetByInterval(): void
    {
        $from = new DatePoint();
        $to = new DatePoint();
        $actual = $this->repository->getByInterval($from, $to);
        self::assertEmpty($actual);
    }

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
        $this->assertSameSearchField(
            'overallMargin',
            'IFELSE(e.itemsTotal != 0, ROUND((100 * e.overallTotal / e.itemsTotal) - 0.5, 0) / 1, 0)'
        );
        $this->assertSameSearchField('date', "DATE_FORMAT(e.date, '%d.%m.%Y')");

        $this->assertSameSearchField('state.id', 's.id');
        $this->assertSameSearchField('stateCode', 's.code');
        $this->assertSameSearchField('state.code', 's.code');

        $this->assertSameSearchField('stateColor', 's.color');
        $this->assertSameSearchField('state.color', 's.color');

        $this->assertSameSearchField('stateEditable', 's.editable');
        $this->assertSameSearchField('state.editable', 's.editable');
    }

    public function testGetSortFields(): void
    {
        $this->assertSameSortField(
            'overallMargin',
            'IFELSE(e.itemsTotal != 0, ROUND((100 * e.overallTotal / e.itemsTotal) - 0.5, 0) / 1, 0)'
        );
        $this->assertSameSortField('stateId', 's.code');
        $this->assertSameSortField('state_id', 's.code');
        $this->assertSameSortField('state.id', 's.code');

        $this->assertSameSortField('code', 's.code');
        $this->assertSameSortField('stateCode', 's.code');
        $this->assertSameSortField('state_code', 's.code');
        $this->assertSameSortField('state.code', 's.code');

        $this->assertSameSortField('color', 's.color');
        $this->assertSameSortField('stateColor', 's.color');
        $this->assertSameSortField('state_color', 's.color');
        $this->assertSameSortField('state.color', 's.color');

        $this->assertSameSortField('editable', 's.editable');
        $this->assertSameSortField('stateEditable', 's.editable');
        $this->assertSameSortField('state_editable', 's.editable');
        $this->assertSameSortField('state.editable', 's.editable');
    }

    #[\Override]
    protected function getRepositoryClass(): string
    {
        return CalculationRepository::class;
    }
}
