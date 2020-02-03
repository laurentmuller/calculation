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
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * @author Laurent Muller
 */
class LogFileDataTable extends AbstractDataTable
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
     * The log file name.
     *
     * @var string
     */
    private $fileName;

    /**
     * Constructor.
     *
     * @param ApplicationService  $application the application to get parameters
     * @param SessionInterface    $session     the session to save/retrieve user parameters
     * @param DataTablesInterface $datatables  the datatables to handle request
     */
    public function __construct(ApplicationService $application, SessionInterface $session, DataTablesInterface $datatables)
    {
        parent::__construct($application, $session, $datatables);
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
     * Gets the file name.
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
     * Sets the file name.
     *
     * @param string $fileName the file name to parse
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
                ->setClassName('date')
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
        $results->recordsFiltered = 0;
        $results->recordsTotal = 0;
        $results->data = [];

        // file?
        if ($this->fileName && $entries = LogUtils::readAll($this->fileName)) {
            $logs = $entries['logs'];
            $results->recordsTotal = \count($logs);

            // filter
            $logs = $this->filter($logs, $query->search->value);

            // sort
            if (!empty($query->order)) {
                /** @var Order $order */
                $order = $query->order[0];
                $field = $this->getColumns()[$order->column]->getName();
                $logs = $this->sort($logs, $field, $order->dir);
            }

            // restrict
            $logs = \array_slice($logs, $query->start, $query->length);
            $results->recordsFiltered = \count($logs);
            $results->data = $logs;
        }

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
    private function filter(array $logs, string $value): array
    {
        if (0 !== \strlen($value)) {
            $filter = function (Log $log) use ($value) {
                $level = $this->getLevel($log->getLevel());
                if (Utils::contains($level, $value)) {
                    return true;
                }

                $channel = $this->getChannel($log->getChannel());
                if (Utils::contains($channel, $value)) {
                    return true;
                }

                $date = $this->getDate($log->getCreatedAt());
                if (Utils::contains($date, $value)) {
                    return true;
                }

                $message = LogUtils::getMessage($log->getMessage());
                if (Utils::contains($message, $value)) {
                    return true;
                }

                return false;
            };

            return \array_filter($logs, $filter);
        }

        return $logs;
    }

    /**
     * Sorts logs.
     *
     * @param Log[]  $logs      the logs to sort
     * @param string $field     the sorted field
     * @param string $direction the sorted direction ('asc' or 'desc')
     *
     * @return Log[] the sorted logs
     */
    private function sort(array $logs, string $field, string $direction): array
    {
        // sort only if no default order
        if (self::COLUMN_DATE !== $field || DataColumn::SORT_DESC !== $direction) {
            $ascending = DataColumn::SORT_ASC === $direction;
            Utils::sortField($logs, $field, $ascending);
        }

        return $logs;
    }
}
