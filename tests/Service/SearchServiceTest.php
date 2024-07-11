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
use Doctrine\ORM\Exception\ORMException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\Exception;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

#[CoversClass(SearchService::class)]
class SearchServiceTest extends KernelServiceTestCase
{
    use CalculationTrait;
    use DatabaseTrait;

    /**
     * @throws ORMException
     */
    protected function tearDown(): void
    {
        $this->deleteCalculation();
        parent::tearDown();
    }

    /**
     * @throws ORMException|Exception|InvalidArgumentException
     */
    public function testCount(): void
    {
        $service = $this->getSearchService();
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

    /**
     * @throws Exception
     */
    public function testFormatContent(): void
    {
        \Locale::setDefault(FormatUtils::DEFAULT_LOCALE);

        $service = $this->getSearchService();
        $actual = $service->formatContent('Calculation.id', 1);
        self::assertSame('000001', $actual);

        $actual = $service->formatContent('Calculation.overallTotal', 1);
        self::assertSame('1.00', $actual);

        $actual = $service->formatContent('Product.price', 10);
        self::assertSame('10.00', $actual);

        $actual = $service->formatContent('fake', 'value');
        self::assertSame('value', $actual);
    }

    /**
     * @throws Exception
     */
    public function testGetEntities(): void
    {
        $service = $this->getSearchService();
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

    /**
     * @throws Exception
     */
    public function testGetEntitiesDebug(): void
    {
        $service = $this->getSearchService(true);
        $actual = $service->getEntities();
        self::assertArrayHasKey('customer', $actual);
        self::assertSame('customer.name', $actual['customer']);
    }

    /**
     * @throws ORMException|Exception|InvalidArgumentException
     */
    public function testSearch(): void
    {
        $service = $this->getSearchService();
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

    /**
     * @throws ORMException|Exception|InvalidArgumentException
     */
    public function testSearchDebug(): void
    {
        $service = $this->getSearchService(true);
        $this->getCalculation();
        $actual = $service->search('customer');
        self::assertCount(1, $actual);
    }

    /**
     * @throws Exception
     */
    private function getSearchService(bool $debug = false): SearchService
    {
        $checker = $this->createMock(AuthorizationCheckerInterface::class);
        $checker->method('isGranted')
            ->willReturn(true);
        $cache = new ArrayAdapter();
        $manager = $this->getService(EntityManagerInterface::class);

        $service = new SearchService($manager, $debug, $cache);
        $service->setChecker($checker);

        return $service;
    }
}
