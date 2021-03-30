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
use App\DataTable\Model\DataColumn;
use App\DataTable\Model\DataColumnFactory;
use App\Entity\Log;
use App\Service\LogService;
use App\Util\Utils;
use DataTables\DataTableQuery;
use DataTables\DataTableResults;
use DataTables\DataTablesInterface;
use DataTables\Order;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

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
     * @var ?string[]
     */
    private $channels;

    /**
     * The levels.
     *
     * @var ?string[]
     */
    private $levels;

    /**
     * The log service.
     */
    private LogService $service;

    /**
     * Constructor.
     *
     * @param SessionInterface    $session    the session to save/retrieve user parameters
     * @param DataTablesInterface $datatables the datatables to handle request
     */
    public function __construct(SessionInterface $session, DataTablesInterface $datatables, LogService $service)
    {
        parent::__construct($session, $datatables);
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
     * @return string[]
     */
    public function getChannels(): array
    {
        return $this->channels ?? [];
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
     * @return string[]
     */
    public function getLevels(): array
    {
        return $this->levels ?? [];
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
        if (!$entries = $this->service->getEntries()) {
            return $results;
        }

        $logs = $entries[LogService::KEY_LOGS];
        $results->recordsTotal = \count($logs);

        // filter
        $skipChannel = false;
        if ($value = $query->columns[2]->search->value) {
            $logs = LogService::filterChannel($logs, $value);
            $skipChannel = true;
        }
        $skipLevel = false;
        if ($value = $query->columns[3]->search->value) {
            $logs = LogService::filterLevel($logs, $value);
            $skipLevel = true;
        }
        if ($value = $query->search->value) {
            $logs = LogService::filter($logs, $value, $skipChannel, $skipLevel);
        }
        $results->recordsFiltered = \count($logs);

        // sort
        if (!empty($query->order)) {
            /** @var Order $order */
            $order = $query->order[0];
            $field = $this->getColumn($order->column)->getName();
            $this->sort($logs, $field, $order->dir);
        }

        // restrict and convert
        $logs = \array_slice($logs, $query->start, $query->length);
        $results->data = \array_map([$this, 'getCellValues'], $logs);

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
     * <b>NB:</b> Sorts only when not the default order (date ascending).
     *
     * @param Log[]  $logs      the logs to sort
     * @param string $field     the sorted field
     * @param string $direction the sorted direction ('asc' or 'desc')
     */
    private function sort(array &$logs, string $field, string $direction): void
    {
        if (self::COLUMN_DATE !== $field || DataColumn::SORT_ASC !== $direction) {
            $ascending = DataColumn::SORT_ASC === $direction;
            Utils::sortField($logs, $field, $ascending);
        }
    }
}
