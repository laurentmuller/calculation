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

use App\Service\SearchService;
use App\Tests\DatabaseTrait;
use App\Tests\EntityTrait\CalculationTrait;
use App\Tests\KernelServiceTestCase;
use App\Utils\FormatUtils;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class SearchServiceTest extends KernelServiceTestCase
{
    use CalculationTrait;
    use DatabaseTrait;

    protected function tearDown(): void
    {
        $this->deleteCalculation();
        parent::tearDown();
    }

    public function testCount(): void
    {
        $service = $this->createSearchService();
        $actual = $service->count('');
        self::assertSame(0, $actual);

        $actual = $service->count('fake');
        self::assertSame(0, $actual);

        $this->getCalculation();
        $actual = $service->count('customer');
        self::assertSame(1, $actual);

        $actual = $service->count('customer', 'calculation');
        self::assertSame(1, $actual);

        $actual = $service->count('customer', 'fake_entity');
        self::assertSame(0, $actual);
    }

    public function testFormatContent(): void
    {
        \Locale::setDefault(FormatUtils::DEFAULT_LOCALE);

        $service = $this->createSearchService();
        $actual = $service->formatContent('Calculation.id', 1);
        self::assertSame('000001', $actual);

        $actual = $service->formatContent('Calculation.overallTotal', 1);
        self::assertSame('1.00', $actual);

        $actual = $service->formatContent('Product.price', 10);
        self::assertSame('10.00', $actual);

        $actual = $service->formatContent('fake', 'value');
        self::assertSame('value', $actual);
    }

    public function testGetEntitiesNotDebug(): void
    {
        $service = $this->createSearchService();
        $actual = $service->getEntities();

        self::assertArrayHasKey('calculation', $actual);
        self::assertArrayHasKey('product', $actual);
        self::assertArrayHasKey('task', $actual);
        self::assertArrayHasKey('category', $actual);
        self::assertArrayHasKey('group', $actual);
        self::assertArrayHasKey('calculationstate', $actual);

        self::assertSame('calculation.name', $actual['calculation']);
        self::assertSame('product.name', $actual['product']);
        self::assertSame('task.name', $actual['task']);
        self::assertSame('category.name', $actual['category']);
        self::assertSame('group.name', $actual['group']);
        self::assertSame('calculationstate.name', $actual['calculationstate']);
    }

    public function testGetEntitiesWithDebug(): void
    {
        $service = $this->createSearchService(true);
        $actual = $service->getEntities();
        self::assertArrayHasKey('customer', $actual);
        self::assertSame('customer.name', $actual['customer']);
    }

    public function testSearchNotDebug(): void
    {
        $service = $this->createSearchService();
        $actual = $service->search('');
        self::assertCount(0, $actual);

        $actual = $service->search('fake');
        self::assertCount(0, $actual);

        $this->getCalculation();
        $actual = $service->search('customer');
        self::assertCount(1, $actual);

        $actual = $service->search('customer', 'calculation');
        self::assertCount(1, $actual);

        $actual = $service->search('customer', 'calculation', -1);
        self::assertCount(1, $actual);

        $actual = $service->search('customer', 'fake_entity');
        self::assertCount(0, $actual);
    }

    public function testSearchNotGranted(): void
    {
        $service = $this->createSearchService(true, false);
        $actual = $service->search('fake');
        self::assertCount(0, $actual);
    }

    public function testSearchWithDebug(): void
    {
        $this->getCalculation();
        $service = $this->createSearchService(true);
        $actual = $service->search('customer');
        self::assertCount(1, $actual);
    }

    private function createSearchService(bool $debug = false, bool $granted = true): SearchService
    {
        $checker = $this->createMock(AuthorizationCheckerInterface::class);
        $checker->method('isGranted')
            ->willReturn($granted);
        $cache = new ArrayAdapter();
        $manager = $this->getService(EntityManagerInterface::class);
        $service = new SearchService($manager, $debug, $cache);
        $service->setChecker($checker);

        return $service;
    }
}
