<?php
/*
 * This file is part of the Calculation package.
 *
 * Copyright (c) 2019 bibi.nu. All rights reserved.
 *
 * This computer code is protected by copyright law and international
 * treaties. Unauthorised reproduction or distribution of this code, or
 * any portion of it, may result in severe civil and criminal penalties,
 * and will be prosecuted to the maximum extent possible under the law.
 */

declare(strict_types=1);

namespace App\DataTables;

use App\DataTables\Columns\DataColumn;
use App\DataTables\Tables\AbstractDataTable;
use App\Entity\Log;
use App\Service\ApplicationService;
use App\Utils\LogUtils;
use App\Utils\Utils;
use DataTables\DataTableQuery;
use DataTables\DataTableResults;
use DataTables\DataTablesInterface;
use DataTables\Order;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * Log data table for a file name.
 *
 * @author Laurent Muller
 */
class LogFileDataTable extends AbstractDataTable
{
    /**
     * The datatable identifier.
     */
    public const ID = 'LogFile';

    /**
     * The cache validity in seconds (2 minutes).
     */
    private const CACHE_TIMEOUT = 60 * 2;

    /**
     * The created at column name.
     */
    private const COLUMN_DATE = 'createdAt';

    /**
     * The cache adapter.
     */
    private AdapterInterface $adapter;

    /**
     * The channels.
     *
     * @var string[]
     */
    private $channels;

    /**
     * The log file name.
     *
     * @var string
     */
    private $fileName;

    /**
     * The levels.
     *
     * @var string[]
     */
    private $levels;

    /**
     * Constructor.
     *
     * @param ApplicationService  $application the application to get parameters
     * @param SessionInterface    $session     the session to save/retrieve user parameters
     * @param DataTablesInterface $datatables  the datatables to handle request
     */
    public function __construct(ApplicationService $application, SessionInterface $session, DataTablesInterface $datatables, AdapterInterface $adapter)
    {
        $this->adapter = $adapter;
        parent::__construct($application, $session, $datatables);
    }

    /**
     * Clear the cached values.
     */
    public function clearCachedValues(): self
    {
        $this->adapter->deleteItems([
            LogUtils::KEY_LOGS,
            LogUtils::KEY_LEVELS,
            LogUtils::KEY_CHANNELS,
        ]);

        return $this;
    }

    /**
     * Gets the log channel.
     *
     * @param string $value the source
     *
     * @return string the channel
     */
    public function getChannel(string $value): string
    {
        return Utils::capitalize(LogUtils::getChannel($value));
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
     * Gets the formatted log date.
     *
     * @param \DateTime $value the source
     *
     * @return string the formatted date
     */
    public function getDate(\DateTime $value): string
    {
        return $this->localeDateTime($value, null, \IntlDateFormatter::MEDIUM);
    }

    /**
     * Gets the cached entries.
     *
     * @return array|bool the entries, if cached; false otherwise
     */
    public function getEntries()
    {
        return $this->getCachedValues();
    }

    /**
     * Gets the file name to parse.
     */
    public function getFileName(): ?string
    {
        return $this->fileName;
    }

    /**
     * Gets the log level.
     *
     * @param string $value the source
     *
     * @return string the level
     */
    public function getLevel(string $value): string
    {
        return Utils::capitalize($value);
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
     * Gets the log for the given identifier.
     *
     * @param int $id the log identifier to find
     *
     * @return Log|null the log, if founs; null otherwise
     */
    public function getLog(int $id): ?Log
    {
        if ($entries = $this->getEntries()) {
            if ($logs = $entries[LogUtils::KEY_LOGS]) {
                if (\array_key_exists($id, $logs)) {
                    return $logs[$id];
                }
            }
        }

        return null;
    }

    /**
     * Sets the file name to parse.
     */
    public function setFileName(string $fileName): self
    {
        $this->fileName = $fileName;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function createColumns(): array
    {
        return [
            DataColumn::hidden('id'),
            DataColumn::date('createdAt')
                ->setTitle('logs.fields.date')
                ->setClassName('pl-3 date')
                ->setCallback('renderLog')
                ->setDescending()
                ->setDefault(true)
                ->setFormatter([$this, 'getDate']),
            DataColumn::instance('channel')
                ->setTitle('logs.fields.channel')
                ->setClassName('channel')
                ->setFormatter([$this, 'getChannel']),
            DataColumn::instance('level')
                ->setTitle('logs.fields.level')
                ->setClassName('level')
                ->setFormatter([$this, 'getLevel']),
            DataColumn::instance('message')
                ->setTitle('logs.fields.message')
                ->setClassName('cell')
                ->setFormatter([LogUtils::class, 'getMessage']),
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function createDataTableResults(DataTableQuery $query): DataTableResults
    {
        // default
        $results = new DataTableResults();

        //clear
        $this->levels = [];
        $this->channels = [];

        if ($entries = $this->getCachedValues()) {
            // get values from cache
            $logs = $entries[LogUtils::KEY_LOGS];
        } elseif ($this->fileName && $entries = LogUtils::readAll($this->fileName)) {
            // get values from file
            $this->setCachedValues($entries);
            $logs = $entries[LogUtils::KEY_LOGS];
        } else {
            // empty
            return $results;
        }

        $results->recordsTotal = \count($logs);

        // filter
        if ($value = $query->search->value) {
            $logs = $this->filter($logs, $value);
            $results->recordsFiltered = \count($logs);
        } else {
            $results->recordsFiltered = $results->recordsTotal;
        }

        // sort
        if (!empty($query->order)) {
            /** @var Order $order */
            $order = $query->order[0];
            $field = $this->getColumns()[$order->column]->getName();
            $this->sort($logs, $field, $order->dir);
        }

        // restrict
        $logs = \array_slice($logs, $query->start, $query->length);
        $results->data = \array_map([$this, 'getCellValues'], $logs);

        // copy
        $this->levels = $entries[LogUtils::KEY_LEVELS];
        $this->channels = $entries[LogUtils::KEY_CHANNELS];

        return $results;
    }

    /**
     * Filters the logs.
     *
     * @param Log[]  $logs  the logs to search in
     * @param string $value the value to search for
     *
     * @return Log[] the filtered logs
     */
    private function filter(array $logs, ?string $value): array
    {
        if (Utils::isString($value)) {
            $filter = function (Log $log) use ($value) {
                $level = $this->getLevel($log->getLevel());
                if (Utils::contains($level, $value, true)) {
                    return true;
                }

                $channel = $this->getChannel($log->getChannel());
                if (Utils::contains($channel, $value, true)) {
                    return true;
                }

                $date = $this->getDate($log->getCreatedAt());
                if (Utils::contains($date, $value, true)) {
                    return true;
                }

                $message = LogUtils::getMessage($log->getMessage());
                if (Utils::contains($message, $value, true)) {
                    return true;
                }

                return false;
            };

            return \array_filter($logs, $filter);
        }

        return $logs;
    }

    /**
     * Gets the cached values.
     *
     * @return array|bool the values, if cached; false otherwise
     */
    private function getCachedValues()
    {
        $items = $this->adapter->getItems([
            LogUtils::KEY_LOGS,
            LogUtils::KEY_LEVELS,
            LogUtils::KEY_CHANNELS,
        ]);

        foreach ($items as $item) {
            if ($item->isHit()) {
                $entries[$item->getKey()] = $item->get();
            } else {
                return false;
            }
        }

        return $entries;
    }

    private function setCachedValue(string $key, array $entries): self
    {
        $value = $entries[$key];
        $item = $this->adapter->getItem($key)
            ->expiresAfter(self::CACHE_TIMEOUT)
            ->set($value);
        $this->adapter->save($item);

        return $this;
    }

    private function setCachedValues(array $entries): void
    {
        $this->setCachedValue(LogUtils::KEY_LOGS, $entries)
            ->setCachedValue(LogUtils::KEY_LEVELS, $entries)
            ->setCachedValue(LogUtils::KEY_CHANNELS, $entries);
    }

    /**
     * Sorts logs.
     *
     * @param Log[]  $logs      the logs to sort
     * @param string $field     the sorted field
     * @param string $direction the sorted direction ('asc' or 'desc')
     */
    private function sort(array &$logs, string $field, string $direction): void
    {
        // sort only if no default order
        if (self::COLUMN_DATE !== $field || DataColumn::SORT_DESC !== $direction) {
            $ascending = DataColumn::SORT_ASC === $direction;
            Utils::sortField($logs, $field, $ascending);
        }
    }
}
