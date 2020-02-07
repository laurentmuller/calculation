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
use App\DataTables\Tables\EntityDataTable;
use App\Entity\Log;
use App\Repository\LogRepository;
use App\Service\ApplicationService;
use App\Utils\LogUtils;
use App\Utils\Utils;
use DataTables\DataTablesInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * Log data table handler.
 *
 * @author Laurent Muller
 *
 * @see App\Entity\Log
 */
class LogDataTable extends EntityDataTable
{
    /**
     * The datatable identifier.
     */
    public const ID = Log::class;

    /**
     * The created at column name.
     */
    private const COLUMN_DATE = 'createdAt';

    /**
     * The formatted date to search.
     */
    private const DATE_FORMAT = '%d.%m.%Y %H:%i:%s';

    /**
     * Constructor.
     *
     * @param ApplicationService  $application the application to get parameters
     * @param SessionInterface    $session     the session to save/retrieve user parameters
     * @param DataTablesInterface $datatables  the datatables to handle request
     * @param LogRepository       $repository  the repository to get entities
     */
    public function __construct(ApplicationService $application, SessionInterface $session, DataTablesInterface $datatables, LogRepository $repository)
    {
        parent::__construct($application, $session, $datatables, $repository);
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
    protected function getDefaultOrder(): array
    {
        return [self::COLUMN_DATE => DataColumn::SORT_DESC];
    }
}
