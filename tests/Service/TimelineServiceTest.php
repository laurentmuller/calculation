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

namespace App\Tests\Service;

use App\Entity\Calculation;
use App\Repository\CalculationRepository;
use App\Service\TimelineService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(TimelineService::class)]
class TimelineServiceTest extends TestCase
{
    /**
     * @throws Exception|\Exception
     */
    public function testCurrent(): void
    {
        $repository = $this->createMockRepository();
        $service = new TimelineService($repository);
        $actual = $service->current();
        self::assertArrayHasKey('count', $actual);
        self::assertSame(0, $actual['count']);
    }

    /**
     * @throws Exception|\Exception
     */
    public function testCurrentWithDates(): void
    {
        $calculation = new Calculation();
        $repository = $this->createMockRepository($calculation);
        $service = new TimelineService($repository);
        $actual = $service->current();
        self::assertArrayHasKey('count', $actual);
        self::assertSame(1, $actual['count']);
    }

    /**
     * @throws Exception|\Exception
     */
    public function testFirst(): void
    {
        $repository = $this->createMockRepository();
        $service = new TimelineService($repository);
        $actual = $service->first();
        self::assertArrayHasKey('count', $actual);
        self::assertSame(0, $actual['count']);
    }

    /**
     * @throws Exception|\Exception
     */
    public function testLast(): void
    {
        $repository = $this->createMockRepository();
        $service = new TimelineService($repository);
        $actual = $service->last();
        self::assertArrayHasKey('count', $actual);
        self::assertSame(0, $actual['count']);
    }

    /**
     * @throws Exception
     */
    private function createMockRepository(?Calculation $calculation = null): MockObject&CalculationRepository
    {
        $date = new \DateTime('today');
        $repository = $this->createMock(CalculationRepository::class);

        if ($calculation instanceof Calculation) {
            $repository->method('getByInterval')
                ->willReturn([$calculation]);
            $date = $calculation->getDate();
        }

        $repository->method('getMinMaxDates')
            ->willReturn([$date, $date]);

        return $repository;
    }
}
