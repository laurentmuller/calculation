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

namespace App\Database;

use App\Entity\Log;

/**
 * SQLite database for logs.
 */
class LogDatabase extends AbstractDatabase
{
    /**
     * SQL statement to create the log table.
     *
     * @var string
     */
    private const SQL_CREATE = <<<'sql'
        CREATE TABLE "sy_Log" (
            id         INTEGER NOT NULL,
            created_at INTEGER NOT NULL,
            channel    TEXT NOT NULL,
            level      TEXT NOT NULL,
            message    TEXT NOT NULL,
            context    TEXT,
            extra      TEXT,
            PRIMARY KEY(id)
        ) WITHOUT ROWID
        sql;

    /**
     * SQL statement to add a log into the table.
     *
     * @var string
     */
    private const SQL_INSERT = <<<'sql'
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
        $stmt = $this->getStatement(self::SQL_INSERT);

        // parameters
        $this->bindParam($stmt, ':id', $log->getId(), \SQLITE3_INTEGER);
        $this->bindParam($stmt, ':created_at', $this->dateToInt($log->getCreatedAt()), \SQLITE3_INTEGER);
        $this->bindParam($stmt, ':channel', $log->getChannel(), \SQLITE3_TEXT);
        $this->bindParam($stmt, ':level', $log->getLevel(), \SQLITE3_TEXT);
        $this->bindParam($stmt, ':message', $log->getMessage(), \SQLITE3_TEXT);
        $this->bindParam($stmt, ':context', $this->arrayToString($log->getContext()), \SQLITE3_TEXT);
        $this->bindParam($stmt, ':extra', $this->arrayToString($log->getExtra()), \SQLITE3_TEXT);

        // execute
        return false !== $stmt->execute();
    }

    /**
     * {@inheritdoc}
     */
    protected function createSchema(): void
    {
        $this->exec(self::SQL_CREATE);
    }

    /**
     * Converts an array to a string.
     *
     * @param array|null $array $array the array to convert
     *
     * @return string|null the converted array, if not empty; null otherwise
     */
    private function arrayToString(?array $array): ?string
    {
        if (!empty($array)) {
            return (string) \json_encode($array);
        }

        return null;
    }

    /**
     * Converts a date to an integer.
     */
    private function dateToInt(?\DateTimeInterface $date): int
    {
        $date ??= new \DateTime();

        return $date->getTimestamp();
    }
}
