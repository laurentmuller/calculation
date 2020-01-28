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

namespace App\Service;

use App\Entity\Log;
use App\Utils\LogUtils;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Logger;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Handler to save log entries to the database.
 *
 * @author Laurent Muller
 */
class DatabaseLogService extends AbstractProcessingHandler
{
    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var string
     */
    private $tableName;

    /**
     * @var string
     */
    private $userName = 'System';

    /**
     * Constructor.
     *
     * @param EntityManagerInterface $manager    the manager to get log table name
     * @param Connection             $connection the database connection
     * @param TokenStorageInterface  $storage    the token storage the get the user name
     * @param int                    $level      The minimum logging level at which this handler will be triggered
     * @param bool                   $bubble     Whether the messages that are handled can bubble up the stack or not
     */
    public function __construct(EntityManagerInterface $manager, Connection $connection, TokenStorageInterface $storage, int $level = Logger::ERROR, bool $bubble = true)
    {
        $this->tableName = $manager->getClassMetadata(Log::class)->getTableName();
        $this->connection = $connection;

        if ($token = $storage->getToken()) {
            $user = $token->getUser();
            if ($user instanceof UserInterface) {
                $this->userName = $user->getUsername();
            } elseif (\is_string($user)) {
                $this->userName = (string) $user;
            }
        }

        parent::__construct($level, $bubble);
    }

    /**
     * {@inheritdoc}
     */
    public function isHandling(array $record): bool
    {
        if (false === \strpos($record['message'], $this->tableName)) {
            return parent::isHandling($record);
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    protected function write(array $record): void
    {
        try {
            // create
            $data = $this->createData($record);

            // save
            $this->insertData($data);
        } catch (\Exception $e) {
            // ignore
            //if ($e) {}
        } finally {
        }
    }

    /**
     * Convert a record to a log entries.
     *
     * @param array $record the record to convert
     *
     * @return array an associative array containing column-value pairs
     */
    private function createData(array $record): array
    {
        $extra = null;
        if (\is_array($record['extra'])) {
            $extra = \serialize($record['extra']);
        }
        $context = null;
        if (\is_array($record['context'])) {
            $context = \serialize($record['context']);
        }

        return [
            'level' => $record['level'],
            'message' => $record['message'],
            'user_name' => $this->userName,
            'channel' => LogUtils::getChannel($record['channel']),
            'level_name' => LogUtils::getLevel($record['level_name']),
            'created_at' => $record['datetime']->format('Y-m-d H:i:s'),
            'extra' => $extra,
            'context' => $context,
        ];
    }

    /**
     * Creates a log entry for the given record.
     *
     * @param array $record the record to get values from
     *
     * @return Log the log entry
     */
    private function createLog(array $record): Log
    {
        $log = new Log();

        // copy
        $log->setUserName($this->userName)
            ->setLevel($record['level'])
            ->setMessage($record['message'])
            ->setCreatedAt($record['datetime'])
            ->setChannel(LogUtils::getChannel($record['channel']))
            ->setLevel(LogUtils::getLevel($record['level_name']))
            ->setExtra(\is_array($record['extra']) ? $record['extra'] : [])
            ->setContext(\is_array($record['context']) ? $record['context'] : []);

        return $log;
    }

    /**
     * Insert the given data to the log table.
     *
     * @param array $data an associative array containing column-value pairs
     *
     * @return int the number of affected rows
     */
    private function insertData(array $data): int
    {
        return $this->connection->insert($this->tableName, $data);
    }
}
