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
use Symfony\Component\Cache\Adapter\TagAwareAdapter;

final class IndexServiceTest extends TestCase
{
    public function testClear(): void
    {
        $cache = self::createMock(TagAwareAdapter::class);
        $cache->expects(self::once())
            ->method('invalidateTags');
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

        $repository = self::createStub(EntityRepository::class);
        $repository->method('count')
            ->willReturn(0);

        $manager = self::createStub(EntityManagerInterface::class);
        $manager->method('getRepository')
            ->willReturn($repository);

        $service = $this->createService($manager);
        $actual = $service->getCatalog();
        self::assertCount(\count($keys), $actual);
        foreach ($keys as $key) {
            self::assertSame(0, $service->getEntitiesCount($key));
            self::assertArrayHasKey($key, $actual);
            self::assertSame(0, $actual[$key]);
        }
    }

    public function testGetLastCalculations(): void
    {
        $repository = self::createMock(CalculationRepository::class);
        $repository->expects(self::once())
            ->method('getLastCalculations')
            ->willReturn([]);

        $manager = self::createMock(EntityManagerInterface::class);
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
        $repository = self::createMock(CalculationRepository::class);
        $repository->expects(self::once())
            ->method('getMonthChartData')
            ->willReturn($monthChartData);

        $manager = self::createMock(EntityManagerInterface::class);
        $manager->expects(self::once())
            ->method('getRepository')
            ->willReturn($repository);

        $service = self::createService($manager);
        $actual = $service->getMonthChartData();
        self::assertCount(0, $actual);
    }

    public function testGetStateChartData(): void
    {
        $stateChartData = new StateChartData([]);
        $repository = self::createMock(CalculationStateRepository::class);
        $repository->expects(self::once())
            ->method('getStateChartData')
            ->willReturn($stateChartData);

        $manager = self::createMock(EntityManagerInterface::class);
        $manager->expects(self::once())
            ->method('getRepository')
            ->willReturn($repository);

        $service = $this->createService($manager);
        $actual = $service->getStateChartData();
        self::assertCount(0, $actual);
    }

    public function testOnFlush(): void
    {
        $unitOfWork = self::createMock(UnitOfWork::class);
        $unitOfWork->expects(self::once())
            ->method('getScheduledEntityInsertions')
            ->willReturn([new Group()]);

        $manager = self::createMock(EntityManagerInterface::class);
        $manager->expects(self::once())
            ->method('getUnitOfWork')
            ->willReturn($unitOfWork);

        $args = self::createMock(OnFlushEventArgs::class);
        $args->expects(self::once())
            ->method('getObjectManager')
            ->willReturn($manager);

        $service = $this->createService($manager);
        $service->onFlush($args);
    }

    private function createService(
        ?EntityManagerInterface $manager = null,
        ?TagAwareAdapter $cache = null
    ): IndexService {
        $manager ??= self::createStub(EntityManagerInterface::class);
        $cache ??= new TagAwareAdapter(new ArrayAdapter());

        return new IndexService($manager, $cache);
    }
}
