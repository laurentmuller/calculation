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
use App\Utils\FormatUtils;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NativeQuery;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

final class SearchServiceTest extends TestCase
{
    public function testCount(): void
    {
        $service = $this->createSearchService();
        $actual = $service->count('');
        self::assertSame(0, $actual);

        $actual = $service->count('fake');
        self::assertSame(0, $actual);

        $service = $this->createSearchService(entity: true);
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
        $service = $this->createSearchService(debug: true);
        $actual = $service->getEntities();
        self::assertArrayHasKey('customer', $actual);
        self::assertSame('customer.name', $actual['customer']);
    }

    public function testSearchNotDebug(): void
    {
        $service = $this->createSearchService();
        $actual = $service->search('');
        self::assertEmpty($actual);

        $actual = $service->search('fake');
        self::assertEmpty($actual);

        $service = $this->createSearchService(entity: true);
        $actual = $service->search('customer');
        self::assertCount(1, $actual);

        $actual = $service->search('customer', 'calculation');
        self::assertCount(1, $actual);

        $actual = $service->search('customer', 'calculation', -1);
        self::assertCount(1, $actual);

        $service = $this->createSearchService();
        $actual = $service->search('customer', 'fake_entity');
        self::assertEmpty($actual);
    }

    public function testSearchNotGranted(): void
    {
        $service = $this->createSearchService(debug: true, granted: false);
        $actual = $service->search('fake');
        self::assertEmpty($actual);
    }

    public function testSearchWithDebug(): void
    {
        $service = $this->createSearchService(debug: true, entity: true);
        $actual = $service->search('customer');
        self::assertCount(1, $actual);
    }

    private function createEntityManager(bool $entity = false): EntityManagerInterface
    {
        $results = [];
        if ($entity) {
            $results[] = [
                'id' => 1,
                'type' => 'product',
                'field' => 'name',
                'content' => 'Content',
                'entityName' => 'Product',
                'fieldName' => 'product.name',
            ];
        }
        $nativeQuery = $this->createMock(NativeQuery::class);
        $nativeQuery->method('getArrayResult')
            ->willReturn($results);

        $manager = $this->createMock(EntityManagerInterface::class);
        $manager->method('createNativeQuery')
            ->willReturn($nativeQuery);

        $queryBuilder = self::createStub(QueryBuilder::class);
        $manager->method('createQueryBuilder')
            ->willReturn($queryBuilder);

        return $manager;
    }

    private function createSearchService(
        bool $debug = false,
        bool $granted = true,
        bool $entity = false
    ): SearchService {
        $checker = $this->createMock(AuthorizationCheckerInterface::class);
        $checker->method('isGranted')
            ->willReturn($granted);
        $cache = new ArrayAdapter();
        $manager = $this->createEntityManager($entity);
        $service = new SearchService($manager, $debug, $cache);
        $service->setChecker($checker);

        return $service;
    }
}
