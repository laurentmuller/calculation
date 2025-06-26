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
use App\Service\IndexService;
use App\Tests\DatabaseTrait;
use App\Tests\KernelServiceTestCase;

class IndexServiceTest extends KernelServiceTestCase
{
    use DatabaseTrait;

    private IndexService $service;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->service = self::getService(IndexService::class);
    }

    public function testClear(): void
    {
        $this->service->clear();
        self::assertTrue($this->service->isEnabled());
    }

    public function testGetCalculationByMonths(): void
    {
        $actual = $this->service->getCalculationByMonths();
        self::assertCount(0, $actual);
    }

    public function testGetCalculationByStates(): void
    {
        $actual = $this->service->getCalculationByStates();
        self::assertCount(1, $actual);
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
        $actual = $this->service->getCatalog();

        $expected = \count($keys);
        self::assertCount($expected, $actual);

        foreach ($keys as $key) {
            self::assertArrayHasKey($key, $actual);
            self::assertSame(0, $actual[$key]);
        }
    }

    public function testGetLastCalculations(): void
    {
        $actual = $this->service->getLastCalculations(6, null);
        self::assertCount(0, $actual);
    }

    public function testOnFlushDefault(): void
    {
        $group = $this->createGroup();
        $this->deleteEntity($group);
        self::assertTrue($this->service->isEnabled());
    }

    public function testOnFlushDisabled(): void
    {
        $this->service->setEnabled(false);
        self::assertFalse($this->service->isEnabled());
        $group = $this->createGroup();
        $this->deleteEntity($group);
        $this->service->setEnabled(true);
        self::assertTrue($this->service->isEnabled());
    }

    public function testOnFlushNoChange(): void
    {
        $group = $this->createGroup();
        $group->setCode('fake');
        $this->addEntity($group);
        $this->deleteEntity($group);
        self::assertTrue($this->service->isEnabled());
    }

    private function createGroup(): Group
    {
        $group = new Group();
        $group->setCode('fake');

        return $this->addEntity($group);
    }
}
