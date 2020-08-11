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

namespace App\DataTable;

use App\DataTable\Model\AbstractDataTable;
use App\DataTable\Model\DataColumn;
use App\Entity\Log;
use App\Service\ApplicationService;
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
     * @var string[]
     */
    private $channels;

    /**
     * The levels.
     *
     * @var string[]
     */
    private $levels;

    /**
     * The log service.
     */
    private LogService $service;

    /**
     * Constructor.
     *
     * @param ApplicationService  $application the application to get parameters
     * @param SessionInterface    $session     the session to save/retrieve user parameters
     * @param DataTablesInterface $datatables  the datatables to handle request
     */
    public function __construct(ApplicationService $application, SessionInterface $session, DataTablesInterface $datatables, LogService $service)
    {
        parent::__construct($application, $session, $datatables);
        $this->service = $service;
    }

    /**
     * Gets the formatted channel.
     *
     * @param string $value the source
     *
     * @return string the channel
     */
    public function getChannel(string $value): string
    {
        return LogService::getChannel($value, true);
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
     * Gets the file name to parse.
     */
    public function getFileName(): ?string
    {
        return $this->service->getFileName();
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
        return LogService::getLevel($value, true);
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
        return [
            DataColumn::hidden('id'),
            DataColumn::date('createdAt')
                ->setTitle('log.fields.createdAt')
                ->setClassName('pl-3 date')
                ->setCallback('renderLog')
                ->setDescending()
                ->setDefault(true)
                ->setFormatter([$this, 'getDate']),
            DataColumn::instance('channel')
                ->setTitle('log.fields.channel')
                ->setClassName('channel')
                ->setFormatter([$this, 'getChannel']),
            DataColumn::instance('level')
                ->setTitle('log.fields.level')
                ->setClassName('level')
                ->setFormatter([$this, 'getLevel']),
            DataColumn::instance('message')
                ->setTitle('log.fields.message')
                ->setClassName('cell'),
        ];
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
            $logs = $this->filterChannel($logs, $value);
            $skipChannel = true;
        }

        $skipLevel = false;
        if ($value = $query->columns[3]->search->value) {
            $logs = $this->filterLevel($logs, $value);
            $skipLevel = true;
        }

        if ($value = $query->search->value) {
            $logs = $this->filter($logs, $value, $skipChannel, $skipLevel);
        }
        $results->recordsFiltered = \count($logs);

        // sort
        if (!empty($query->order)) {
            /** @var Order $order */
            $order = $query->order[0];
            $field = $this->getColumn($order->column)->getName();
            $this->sort($logs, $field, $order->dir);
        }

        // restrict
        $logs = \array_slice($logs, $query->start, $query->length);
        $results->data = \array_map([$this, 'getCellValues'], $logs);

        // copy
        $this->channels = $entries[LogService::KEY_CHANNELS];
        $this->levels = $entries[LogService::KEY_LEVELS];

        return $results;
    }

    /**
     * Filters the log.
     *
     * @param Log[]  $logs        the logs to search in
     * @param string $value       the value to search for
     * @param bool   $skipChannel true to skip search in channel
     * @param bool   $skipLevel   true to skip search in level
     *
     * @return Log[] the filtered logs
     */
    private function filter(array $logs, ?string $value, bool $skipChannel, bool $skipLevel): array
    {
        if (Utils::isString($value)) {
            $filter = function (Log $log) use ($value, $skipChannel, $skipLevel) {
                if (!$skipChannel) {
                    $channel = $this->getChannel($log->getChannel());
                    if (Utils::contains($channel, $value, true)) {
                        return true;
                    }
                }

                if (!$skipLevel) {
                    $level = $this->getLevel($log->getLevel());
                    if (Utils::contains($level, $value, true)) {
                        return true;
                    }
                }

                $date = $this->getDate($log->getCreatedAt());
                if (Utils::contains($date, $value, true)) {
                    return true;
                }

                if (Utils::contains($log->getMessage(), $value, true)) {
                    return true;
                }

                return false;
            };

            return \array_filter($logs, $filter);
        }

        return $logs;
    }

    /**
     * Filters the log for the given channel.
     *
     * @param Log[]  $logs  the logs to search in
     * @param string $value the channel value to search for
     *
     * @return Log[] the filtered logs
     */
    private function filterChannel(array $logs, ?string $value): array
    {
        if (Utils::isString($value)) {
            return \array_filter($logs, function (Log $log) use ($value) {
                return $value === $log->getChannel();
            });
        }

        return $logs;
    }

    /**
     * Filters the log for the given level.
     *
     * @param Log[]  $logs  the logs to search in
     * @param string $value the level value to search for
     *
     * @return Log[] the filtered logs
     */
    private function filterLevel(array $logs, ?string $value): array
    {
        if (Utils::isString($value)) {
            return \array_filter($logs, function (Log $log) use ($value) {
                return $value === $log->getLevel();
            });
        }

        return $logs;
    }

    /**
     * Sorts log.
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
