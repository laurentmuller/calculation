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

namespace App\Tests\Table;

use App\Service\SearchService;
use App\Table\DataQuery;
use App\Table\SearchTable;
use App\Tests\TranslatorMockTrait;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class SearchTableTest extends TestCase
{
    use TranslatorMockTrait;

    /**
     * @throws Exception
     */
    public function testEmptyMessage(): void
    {
        $service = $this->createMock(SearchService::class);
        $table = $this->createTable($service);
        self::assertNull($table->getEmptyMessage());
    }

    /**
     * @throws Exception
     */
    public function testEntityClassName(): void
    {
        $service = $this->createMock(SearchService::class);
        $table = $this->createTable($service);
        self::assertNull($table->getEntityClassName());
    }

    /**
     * @throws Exception
     */
    public function testWithAllItems(): void
    {
        $query = new DataQuery();
        $query->search = 'fake';
        $query->sort = 'entityName';
        $query->limit = 100;

        $service = $this->createMockService();
        $table = $this->createTable($service);
        $results = $table->processDataQuery($query);
        self::assertSame(Response::HTTP_OK, $results->status);
        self::assertCount(8, $results->rows);
    }

    /**
     * @throws Exception
     */
    public function testWithCallback(): void
    {
        $query = new DataQuery();
        $query->search = 'fake';
        $query->callback = true;
        $query->limit = 1;

        $service = $this->createMockService();
        $table = $this->createTable($service);
        $results = $table->processDataQuery($query);
        self::assertSame(Response::HTTP_OK, $results->status);
        self::assertCount(1, $results->rows);
    }

    /**
     * @throws Exception
     */
    public function testWithEmptyQuery(): void
    {
        $service = $this->createMock(SearchService::class);
        $table = $this->createTable($service);
        $results = $table->processDataQuery(new DataQuery());
        self::assertSame(Response::HTTP_OK, $results->status);
        self::assertCount(0, $results->rows);
    }

    /**
     * @throws Exception
     */
    public function testWithSearchNoResult(): void
    {
        $query = new DataQuery();
        $query->search = 'fake';

        $service = $this->createMock(SearchService::class);
        $table = $this->createTable($service);
        $results = $table->processDataQuery($query);
        self::assertSame(Response::HTTP_OK, $results->status);
        self::assertCount(0, $results->rows);
    }

    /**
     * @throws Exception
     */
    public function testWithSearchResults(): void
    {
        $query = new DataQuery();
        $query->search = 'fake';
        $query->limit = 1;

        $service = $this->createMockService();
        $table = $this->createTable($service);
        $results = $table->processDataQuery($query);
        self::assertSame(Response::HTTP_OK, $results->status);
        self::assertCount(1, $results->rows);
    }

    /**
     * @throws Exception
     */
    public function testWithSort(): void
    {
        $query = new DataQuery();
        $query->search = 'fake';
        $query->sort = 'entityName';
        $query->limit = 1;

        $service = $this->createMockService();
        $table = $this->createTable($service);
        $results = $table->processDataQuery($query);
        self::assertSame(Response::HTTP_OK, $results->status);
        self::assertCount(1, $results->rows);
    }

    /**
     * @throws Exception
     */
    private function createMockService(): MockObject&SearchService
    {
        $data = $this->createSearchResults();
        $entities = ['key' => 'value'];

        $service = $this->createMock(SearchService::class);
        $service->method('search')
            ->willReturn($data);

        $service->method('formatContent')
            ->willReturnArgument(1);

        $service->method('getEntities')
            ->willReturn($entities);

        return $service;
    }

    private function createSearchResults(): array
    {
        return [
            [
                'id' => 1,
                'type' => 'calculation',
                'field' => 'field',
                'content' => 'content1',
                'entityName' => 'calculation',
                'fieldName' => 'fieldName',
            ],
            [
                'id' => 1,
                'type' => 'calculation',
                'field' => 'field',
                'content' => 'content1',
                'entityName' => 'calculation',
                'fieldName' => 'fieldName',
            ],
            [
                'id' => 2,
                'type' => 'calculationstate',
                'field' => 'field',
                'content' => 'content2',
                'entityName' => 'calculationstate',
                'fieldName' => 'fieldName',
            ],
            [
                'id' => 2,
                'type' => 'category',
                'field' => 'field',
                'content' => 'content2',
                'entityName' => 'entityName',
                'fieldName' => 'fieldName',
            ], [
                'id' => 3,
                'type' => 'customer',
                'field' => 'createdBy',
                'content' => 'content2',
                'entityName' => 'entityName',
                'fieldName' => 'fieldName',
            ],
            [
                'id' => 4,
                'type' => 'task',
                'field' => 'updatedBy',
                'content' => 'content2',
                'entityName' => 'entityName',
                'fieldName' => 'fieldName',
            ],
            [
                'id' => 4,
                'type' => 'group',
                'field' => 'updatedBy',
                'content' => 'content2',
                'entityName' => 'entityName',
                'fieldName' => 'fieldName',
            ],
            [
                'id' => 4,
                'type' => 'product',
                'field' => 'updatedBy',
                'content' => 'content2',
                'entityName' => 'entityName',
                'fieldName' => 'fieldName',
            ],
        ];
    }

    /**
     * @throws Exception
     */
    private function createTable(SearchService $service): SearchTable
    {
        $checker = $this->createMock(AuthorizationCheckerInterface::class);
        $translator = $this->createMockTranslator();
        $table = new SearchTable($service);

        return $table->setTranslator($translator)
            ->setChecker($checker);
    }
}
