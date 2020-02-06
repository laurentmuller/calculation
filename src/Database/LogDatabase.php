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

namespace App\Database;

use App\Entity\Log;

/**
 * SQLite database for logs.
 *
 * @author Laurent Muller
 *
 * @see App\Entity\Log
 */
class LogDatabase extends AbstractDatabase
{
    /**
     * SQL statement to create the log table.
     *
     * @var string
     */
    private static $SQL_CREATE = <<<'sql'
CREATE TABLE "sy_Log" (
	id	       INTEGER NOT NULL,
    created_at INTEGER NOT NULL,
	channel	   TEXT NOT NULL,
	level	   TEXT NOT NULL,
	message	   TEXT NOT NULL,
    context	   TEXT,
    extra	   TEXT,
	PRIMARY KEY(id)
) WITHOUT ROWID
sql;

    /**
     * SQL statement to add a log into the table.
     *
     * @var string
     */
    private static $SQL_INSERT = <<<'sql'
INSERT INTO sy_Log(id, created_at, channel, level, message, context, extra)
    VALUES(:id, :created_at, :channel, :level, :message, :context, :extra)
sql;

    /**
     * Insert a log to the database.
     *
     * @param Log $log the log to insert
     *
     * @return bool true if success
     */
    public function insertLog(Log $log): bool
    {
        /** @var \SQLite3Stmt $stmt */
        $stmt = $this->getStatement(self::$SQL_INSERT);

        // parameters
        $stmt->bindParam(':id', $log->getId(), SQLITE3_INTEGER);
        $stmt->bindParam(':created_at', $this->dateToInt($log->getCreatedAt()), SQLITE3_INTEGER);
        $stmt->bindParam(':channel', $log->getChannel(), SQLITE3_TEXT);
        $stmt->bindParam(':level', $log->getLevel(), SQLITE3_TEXT);
        $stmt->bindParam(':message', $log->getMessage(), SQLITE3_TEXT);
        $stmt->bindParam(':context', $this->arrayToString($log->getContext()), SQLITE3_TEXT);
        $stmt->bindParam(':extra', $this->arrayToString($log->getExtra()), SQLITE3_TEXT);

        // execute
        return false !== $stmt->execute();
    }

    /**
     * {@inheritdoc}
     */
    protected function createSchema(): void
    {
        $this->exec(self::$SQL_CREATE);

        // indexes
        // $this->createIndex('sy_Log', 'channel');
        // $this->createIndex('sy_Log', 'level');
        // $this->createIndex('sy_Log', 'message');
    }

    /**
     * Converts an array to a string.
     *
     * @param array $array the array to convert
     *
     * @return string|null the converted array, if not empty; null otherwise
     */
    private function arrayToString(?array $array): ?string
    {
        return empty($array) ? null : \json_encode($array);
    }

    /**
     * Converts a date to an integer.
     *
     * @param \DateTime $date the date to convert
     *
     * @return int the converted date
     */
    private function dateToInt(?\DateTime $date): int
    {
        $date ??= new \DateTime();

        return $date->getTimestamp();
    }
}
