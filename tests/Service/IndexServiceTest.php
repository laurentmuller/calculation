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

use App\Entity\Group;
use App\Model\MonthChartData;
use App\Model\StateChartData;
use App\Repository\CalculationRepository;
use App\Repository\CalculationStateRepository;
use App\Service\IndexService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\UnitOfWork;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

final class IndexServiceTest extends TestCase
{
    public function testClear(): void
    {
        $cache = $this->createMock(ArrayAdapter::class);
        $cache->method('withSubNamespace')
            ->willReturn($cache);
        $cache->expects(self::once())
            ->method('withSubNamespace');
        $cache->expects(self::once())
            ->method('clear');
        $service = $this->createService(cache: $cache);
        $service->clear();
    }

    public function testGetCatalog(): void
    {
        $keys = [
            'user',
            'task',
            'group',
            'product',
            'category',
            'calculation',
            'globalMargin',
            'calculationState',
        ];

        $repository = $this->createMock(EntityRepository::class);
        $repository->method('count')
            ->willReturn(0);

        $manager = $this->createMock(EntityManagerInterface::class);
        $manager->method('getRepository')
            ->willReturn($repository);

        $service = $this->createService($manager);
        $actual = $service->getCatalog();
        self::assertCount(\count($keys), $actual);
        foreach ($keys as $key) {
            self::assertArrayHasKey($key, $actual);
            self::assertSame(0, $actual[$key]);
        }
    }

    public function testGetLastCalculations(): void
    {
        $repository = $this->createMock(CalculationRepository::class);
        $repository->expects(self::once())
            ->method('getLastCalculations')
            ->willReturn([]);

        $manager = $this->createMock(EntityManagerInterface::class);
        $manager->expects(self::once())
            ->method('getRepository')
            ->willReturn($repository);

        $service = $this->createService($manager);
        $actual = $service->getLastCalculations(6);
        self::assertCount(0, $actual);
    }

    public function testGetMonthChartData(): void
    {
        $monthChartData = new MonthChartData([]);
        $repository = $this->createMock(CalculationRepository::class);
        $repository->expects(self::once())
            ->method('getMonthChartData')
            ->willReturn($monthChartData);

        $manager = $this->createMock(EntityManagerInterface::class);
        $manager->expects(self::once())
            ->method('getRepository')
            ->willReturn($repository);

        $service = $this->createService($manager);
        $actual = $service->getMonthChartData();
        self::assertCount(0, $actual);
    }

    public function testGetStateChartData(): void
    {
        $stateChartData = new StateChartData([]);
        $repository = $this->createMock(CalculationStateRepository::class);
        $repository->expects(self::once())
            ->method('getStateChartData')
            ->willReturn($stateChartData);

        $manager = $this->createMock(EntityManagerInterface::class);
        $manager->expects(self::once())
            ->method('getRepository')
            ->willReturn($repository);

        $service = $this->createService($manager);
        $actual = $service->getStateChartData();
        self::assertCount(0, $actual);
    }

    public function testOnFlush(): void
    {
        $unitOfWork = $this->createMock(UnitOfWork::class);
        $unitOfWork->expects(self::once())
            ->method('getScheduledEntityInsertions')
            ->willReturn([new Group()]);

        $manager = $this->createMock(EntityManagerInterface::class);
        $manager->expects(self::once())
            ->method('getUnitOfWork')
            ->willReturn($unitOfWork);

        $args = $this->createMock(OnFlushEventArgs::class);
        $args->expects(self::once())
            ->method('getObjectManager')
            ->willReturn($manager);

        $service = $this->createService($manager);
        $service->onFlush($args);
    }

    private function createService(
        ?EntityManagerInterface $manager = null,
        ?ArrayAdapter $cache = null
    ): IndexService {
        $manager ??= $this->createMock(EntityManagerInterface::class);
        $cache ??= new ArrayAdapter();

        return new IndexService($manager, $cache);
    }
}
