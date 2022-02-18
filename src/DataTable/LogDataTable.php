<?php
/*
 * This file is part of the Calculation package.
 *
 * (c) bibi.nu. <bibi@bibi.nu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace App\DataTable;

use App\DataTable\Model\AbstractDataTable;
use App\DataTable\Model\DataColumnFactory;
use App\Entity\Log;
use App\Service\LogService;
use App\Util\Utils;
use DataTables\DataTableQuery;
use DataTables\DataTableResults;
use DataTables\DataTablesInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Log data table for a file name.
 *
 * @author Laurent Muller
 */
class LogDataTable extends AbstractDataTable
{
    /**
     * The datatable identifier.
     */
    public const ID = 'LogFile';

    /**
     * The created at column name.
     */
    private const COLUMN_DATE = 'createdAt';

    /**
     * The channels.
     *
     * @var array<string, int>
     */
    private array $channels = [];

    /**
     * The levels.
     *
     * @var array<string, int>
     */
    private array $levels = [];

    /**
     * The log service.
     */
    private LogService $service;

    /**
     * Constructor.
     */
    public function __construct(RequestStack $requestStack, DataTablesInterface $datatables, LogService $service)
    {
        parent::__construct($requestStack, $datatables);
        $this->service = $service;
    }

    /**
     * Formats the channel.
     */
    public function formatChannel(string $value): string
    {
        return LogService::getChannel($value, true);
    }

    /**
     * Formats the date.
     */
    public function formatCreatedAt(\DateTimeInterface $value): string
    {
        return LogService::getCreatedAt($value);
    }

    /**
     * Formats the log level.
     *
     * @param string $value the source
     *
     * @return string the level
     */
    public function formatLevel(string $value): string
    {
        return LogService::getLevel($value, true);
    }

    /**
     * Gets the channels.
     *
     * @return array<string, int>
     */
    public function getChannels(): array
    {
        return $this->channels;
    }

    /**
     * Gets the file name to parse.
     */
    public function getFileName(): ?string
    {
        return $this->service->getFileName();
    }

    /**
     * Gets the levels.
     *
     * @return array<string, int>
     */
    public function getLevels(): array
    {
        return $this->levels;
    }

    /**
     * Gets the log service.
     */
    public function getService(): LogService
    {
        return $this->service;
    }

    /**
     * {@inheritdoc}
     */
    protected function createColumns(): array
    {
        $path = __DIR__ . '/Definition/log.json';

        return DataColumnFactory::fromJson($this, $path);
    }

    /**
     * {@inheritdoc}
     */
    protected function createDataTableResults(DataTableQuery $query): DataTableResults
    {
        //clear
        $this->channels = [];
        $this->levels = [];

        // default
        $results = new DataTableResults();

        // get entries
        $entries = $this->service->getEntries();
        if (!\is_array($entries)) {
            return $results;
        }

        /** @var Log[] $logs */
        $logs = $entries[LogService::KEY_LOGS];
        $results->recordsTotal = \count($logs);

        // filter
        $skipChannel = false;
        $channel = $query->columns[2]->search->value;
        if (Utils::isString($channel)) {
            $logs = LogService::filterChannel($logs, $channel);
            $skipChannel = true;
        }
        $skipLevel = false;
        $level = $query->columns[3]->search->value;
        if (Utils::isString($level)) {
            $logs = LogService::filterLevel($logs, $level);
            $skipLevel = true;
        }
        $value = $query->search->value;
        if (Utils::isString($value)) {
            $logs = LogService::filter($logs, $value, $skipChannel, $skipLevel);
        }
        $results->recordsFiltered = \count($logs);

        // sort
        if ([] !== $query->order) {
            $order = $query->order[0];
            $column = $this->getColumn($order->column);
            if (null !== $column) {
                $field = $column->getName();
                $this->sort($logs, $field, $order->dir);
            }
        }

        // restrict and convert
        $logs = \array_slice($logs, $query->start, $query->length);
        $results->data = \array_map(function ($data): array {
            return $this->getCellValues($data);
        }, $logs);

        // copy
        $this->channels = $entries[LogService::KEY_CHANNELS];
        $this->levels = $entries[LogService::KEY_LEVELS];

        return $results;
    }

    /**
     * {@inheritdoc}
     */
    protected function createSessionPrefix(): string
    {
        return Utils::getShortName(Log::class);
    }

    /**
     * Sort logs.
     *
     * <b>NB:</b> Sorts only when not set to the default order (date ascending).
     *
     * @param Log[]  $logs      the logs to sort
     * @param string $field     the sorted field
     * @param string $direction the sorted direction ('asc' or 'desc')
     *
     * @psalm-suppress ReferenceConstraintViolation
     */
    private function sort(array &$logs, string $field, string $direction): void
    {
        // need sort?
        if (self::COLUMN_DATE === $field && self::SORT_ASC === $direction) {
            return;
        }

        $ascending = self::SORT_ASC === $direction;
        if (self::COLUMN_DATE === $field) {
            Utils::sortField($logs, $field, $ascending);
        } else {
            $fields = [
                $field => $ascending,
                self::COLUMN_DATE => false,
            ];
            Utils::sortFields($logs, $fields);
        }
    }
}
