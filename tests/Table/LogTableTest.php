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

use App\Entity\Log;
use App\Model\LogChannel;
use App\Model\LogFile;
use App\Model\LogLevel;
use App\Service\LogService;
use App\Table\AbstractTable;
use App\Table\DataQuery;
use App\Table\LogTable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Psr\Log\LogLevel as PsrLevel;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

#[CoversClass(AbstractTable::class)]
#[CoversClass(LogTable::class)]
class LogTableTest extends TestCase
{
    /**
     * @throws Exception
     */
    public function testEmptyMessage(): void
    {
        $this->processEmptyMessage(0, 'log.list.empty');
        $this->processEmptyMessage(1, null);
    }

    /**
     * @throws Exception
     */
    public function testGetEntityClassName(): void
    {
        $service = $this->createMock(LogService::class);
        $twig = $this->createMock(Environment::class);
        $table = new LogTable($service, $twig);
        $actual = $table->getEntityClassName();
        self::assertSame(Log::class, $actual);
    }

    /**
     * @throws Exception
     */
    public function testWithData(): void
    {
        $query = $this->createDataQuery();
        $table = $this->createTableWithData();
        $results = $table->processDataQuery($query);
        self::assertSame(Response::HTTP_OK, $results->status);
        self::assertCount(2, $results->rows);
    }

    /**
     * @throws Exception
     */
    public function testWithEmptyQuery(): void
    {
        $service = $this->createMock(LogService::class);
        $twig = $this->createMock(Environment::class);
        $table = new LogTable($service, $twig);

        $query = $this->createDataQuery(0);
        $results = $table->processDataQuery($query);
        self::assertSame(Response::HTTP_PRECONDITION_FAILED, $results->status);
        self::assertCount(0, $results->rows);
    }

    /**
     * @throws Exception
     */
    public function testWithoutData(): void
    {
        $query = $this->createDataQuery();
        $table = $this->createTableWithoutData();
        $results = $table->processDataQuery($query);
        self::assertSame(Response::HTTP_OK, $results->status);
        self::assertCount(0, $results->rows);
    }

    /**
     * @throws Exception
     */
    public function testWitSearchChannel(): void
    {
        $query = $this->createDataQuery();
        $query->channel = 'doctrine';

        $table = $this->createTableWithData();
        $results = $table->processDataQuery($query);
        self::assertSame(Response::HTTP_OK, $results->status);
        self::assertCount(1, $results->rows);
    }

    /**
     * @throws Exception
     */
    public function testWitSearchLevel(): void
    {
        $query = $this->createDataQuery();
        $query->level = PsrLevel::ALERT;

        $table = $this->createTableWithData();
        $results = $table->processDataQuery($query);
        self::assertSame(Response::HTTP_OK, $results->status);
        self::assertCount(1, $results->rows);
    }

    /**
     * @throws Exception
     */
    public function testWitSortChannel(): void
    {
        $query = $this->createDataQuery();
        $query->sort = 'channel';

        $table = $this->createTableWithData();
        $results = $table->processDataQuery($query);
        self::assertSame(Response::HTTP_OK, $results->status);
        self::assertCount(2, $results->rows);
    }

    /**
     * @throws Exception
     */
    public function testWitSortLevel(): void
    {
        $query = $this->createDataQuery();
        $query->sort = 'level';

        $table = $this->createTableWithData();
        $results = $table->processDataQuery($query);
        self::assertSame(Response::HTTP_OK, $results->status);
        self::assertCount(2, $results->rows);
    }

    private function createChannels(): array
    {
        $channel1 = new LogChannel('application');
        $channel1->increment();

        $channel2 = new LogChannel('doctrine');
        $channel1->increment();

        return [$channel1, $channel2];
    }

    private function createDataQuery(int $limit = 15): DataQuery
    {
        $query = new DataQuery();
        $query->limit = $limit;

        return $query;
    }

    private function createLevels(): array
    {
        $level1 = new LogLevel(PsrLevel::INFO);
        $level1->increment();

        $level2 = new LogLevel(PsrLevel::ALERT);
        $level2->increment();

        return [$level1, $level2];
    }

    private function createLogs(): array
    {
        $log1 = Log::instance(1)
            ->setMessage('Message1');
        $log2 = Log::instance(2)
            ->setMessage('Message2')
            ->setLevel(PsrLevel::ALERT)
            ->setChannel('doctrine');

        return [$log1, $log2];
    }

    /**
     * @throws Exception
     */
    private function createTableWithData(): LogTable
    {
        $file = $this->createMock(LogFile::class);
        $file->method('getFile')
            ->willReturn(__FILE__);

        $file->method('count')
            ->willReturn(2);

        $file->method('getLogs')
            ->willReturn($this->createLogs());

        $file->method('getChannels')
            ->willReturn($this->createChannels());

        $file->method('getLevels')
            ->willReturn($this->createLevels());

        $service = $this->createMock(LogService::class);
        $service->method('getLogFile')
            ->willReturn($file);

        $twig = $this->createMock(Environment::class);

        return new LogTable($service, $twig);
    }

    /**
     * @throws Exception
     */
    private function createTableWithoutData(): LogTable
    {
        $file = $this->createMock(LogFile::class);
        $file->method('getFile')
            ->willReturn(__FILE__);

        $file->method('count')
            ->willReturn(0);

        $file->method('getLogs')
            ->willReturn([]);

        $file->method('getChannels')
            ->willReturn([]);

        $file->method('getLevels')
            ->willReturn([]);

        $service = $this->createMock(LogService::class);
        $service->method('getLogFile')
            ->willReturn($file);

        $twig = $this->createMock(Environment::class);

        return new LogTable($service, $twig);
    }

    /**
     * @throws Exception
     */
    private function processEmptyMessage(int $count, mixed $expected): void
    {
        $file = $this->createMock(LogFile::class);
        $file->method('count')
            ->willReturn($count);

        $service = $this->createMock(LogService::class);
        $service->method('getLogFile')
            ->willReturn($file);

        $twig = $this->createMock(Environment::class);

        $table = new LogTable($service, $twig);
        $actual = $table->getEmptyMessage();
        self::assertSame($expected, $actual);
    }
}
