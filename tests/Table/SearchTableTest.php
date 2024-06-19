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
use App\Table\AbstractTable;
use App\Table\DataQuery;
use App\Table\SearchTable;
use App\Tests\TranslatorMockTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

#[CoversClass(AbstractTable::class)]
#[CoversClass(SearchTable::class)]
class SearchTableTest extends TestCase
{
    use TranslatorMockTrait;

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
        $result1 = [
            'id' => 1,
            'type' => 'type',
            'field' => 'field',
            'content' => 'content1',
            'entityName' => 'entityName',
            'fieldName' => 'fieldName',
        ];
        $result2 = [
            'id' => 2,
            'type' => 'type',
            'field' => 'field',
            'content' => 'content2',
            'entityName' => 'entityName',
            'fieldName' => 'fieldName',
        ];
        $result3 = [
            'id' => 2,
            'type' => 'type',
            'field' => 'field',
            'content' => 'content2',
            'entityName' => 'entityName',
            'fieldName' => 'fieldName',
        ];

        return [$result1, $result2, $result3];
    }

    /**
     * @throws Exception
     */
    private function createTable(SearchService $service): SearchTable
    {
        $translator = $this->createMockTranslator();
        $checker = $this->createMock(AuthorizationCheckerInterface::class);

        $table = new SearchTable($service);
        $table->setChecker($checker);
        $table->setTranslator($translator);

        return $table;
    }
}
