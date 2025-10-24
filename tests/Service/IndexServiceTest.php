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
use App\Model\CalculationsMonth;
use App\Model\CalculationsState;
use App\Repository\CalculationRepository;
use App\Repository\CalculationStateRepository;
use App\Service\IndexService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\UnitOfWork;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

class IndexServiceTest extends TestCase
{
    public function testClear(): void
    {
        $this->createService()->clear();
        self::expectNotToPerformAssertions();
    }

    public function testGetCalculationByMonths(): void
    {
        $result = new CalculationsMonth([]);
        $repository = $this->createMock(CalculationRepository::class);
        $repository->expects(self::once())
            ->method('getByMonth')
            ->willReturn($result);

        $manager = $this->createMock(EntityManagerInterface::class);
        $manager->expects(self::once())
            ->method('getRepository')
            ->willReturn($repository);

        $service = $this->createService($manager);
        $actual = $service->getCalculationByMonths();
        self::assertCount(0, $actual);
    }

    public function testGetCalculationByStates(): void
    {
        $result = new CalculationsState([]);
        $repository = $this->createMock(CalculationStateRepository::class);
        $repository->expects(self::once())
            ->method('getCalculations')
            ->willReturn($result);

        $manager = $this->createMock(EntityManagerInterface::class);
        $manager->expects(self::once())
            ->method('getRepository')
            ->willReturn($repository);

        $service = $this->createService($manager);
        $actual = $service->getCalculationByStates();
        self::assertCount(0, $actual);
    }

    public function testGetCatalog(): void
    {
        $keys = [
            'task',
            'group',
            'product',
            'category',
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

    private function createService(?EntityManagerInterface $manager = null): IndexService
    {
        $manager ??= $this->createMock(EntityManagerInterface::class);

        return new IndexService($manager, new ArrayAdapter());
    }
}
