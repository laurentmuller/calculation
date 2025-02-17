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
use App\Utils\StringUtils;

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
        CREATE TABLE IF NOT EXISTS sy_Log (
            id         INTEGER PRIMARY KEY,
            created_at INTEGER NOT NULL,
            channel    TEXT NOT NULL,
            level      TEXT NOT NULL,
            message    TEXT NOT NULL,
            context    TEXT,
            extra      TEXT
        )
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
     *
     * @psalm-api
     */
    public function insertLog(Log $log): bool
    {
        /** @var \SQLite3Stmt $stmt */
        $stmt = $this->getStatement(self::SQL_INSERT);

        // parameters
        $stmt->bindValue(':id', $log->getId(), \SQLITE3_INTEGER);
        $stmt->bindValue(':created_at', $this->dateToInt($log->getCreatedAt()), \SQLITE3_INTEGER);
        $stmt->bindValue(':channel', $log->getChannel());
        $stmt->bindValue(':level', $log->getLevel());
        $stmt->bindValue(':message', $log->getMessage());
        $stmt->bindValue(':context', $this->arrayToString($log->getContext()));
        $stmt->bindValue(':extra', $this->arrayToString($log->getExtra()));

        // execute
        return false !== $stmt->execute();
    }

    #[\Override]
    protected function createSchema(): void
    {
        $this->exec(self::SQL_CREATE);
    }

    /**
     * Converts an array to a string.
     *
     * @param ?array $array $array the array to convert
     *
     * @return string|null the converted array, if not empty; null otherwise
     */
    private function arrayToString(?array $array): ?string
    {
        return null === $array || [] === $array ? null : StringUtils::encodeJson($array);
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
