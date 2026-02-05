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

use App\Utils\FileUtils;
use App\Utils\StringUtils;

/**
 * Extended the SQLite3 database with transaction support and caching SQL statements.
 */
abstract class AbstractDatabase extends \SQLite3 implements \Stringable
{
    /**
     * The in-memory database file name.
     */
    public const string IN_MEMORY = ':memory:';

    /**
     * The opened statements.
     *
     * @var array<string, \SQLite3Stmt>
     */
    private array $statements = [];

    /**
     * The transaction state.
     */
    private bool $transaction = false;

    /**
     * Instantiates and opens the database.
     *
     * @param string $filename      Path to the SQLite database, or <code>:memory:</code> to use the in-memory database.
     *                              If the filename is an empty string, then a private, temporary on-disk database will
     *                              be created. This private database will be automatically deleted as soon as the
     *                              database connection is closed.
     * @param bool   $readonly      <code>true</code> open the database for reading only. Notes that if the file name
     *                              does not exist, the database is opened with the
     *                              <code>SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE</code> flags.
     * @param string $encryptionKey an optional encryption key used when encrypting and decrypting an SQLite database
     */
    public function __construct(protected string $filename, bool $readonly = false, string $encryptionKey = '')
    {
        // check creation state
        $create = self::IN_MEMORY === $filename || FileUtils::empty($filename);

        if ($create) {
            $flags = \SQLITE3_OPEN_READWRITE | \SQLITE3_OPEN_CREATE;
        } elseif ($readonly) {
            $flags = \SQLITE3_OPEN_READONLY;
        } else {
            $flags = \SQLITE3_OPEN_READWRITE;
        }

        parent::__construct($filename, $flags, $encryptionKey);

        // create schema
        if ($create) {
            $this->createSchema();
        }
    }

    /**
     * Returns a string representing this object.
     */
    #[\Override]
    public function __toString(): string
    {
        return $this->getFilename();
    }

    /**
     * Begin a new transaction.
     *
     * <b>Note:</b> if one transaction is already in effect, no new one is started.
     *
     * @return bool true on success; false on failure
     *
     * @see AbstractDatabase::commitTransaction()
     * @see AbstractDatabase::rollbackTransaction()
     */
    public function beginTransaction(): bool
    {
        if (!$this->transaction && $this->exec('BEGIN TRANSACTION;')) {
            return $this->transaction = true;
        }

        return false;
    }

    /**
     * Closes the database connection.
     *
     * All opened statements are also closed.
     * If a transaction is active, then it is canceled (rollback).
     */
    #[\Override]
    public function close(): bool
    {
        // close statements
        foreach ($this->statements as $statement) {
            $statement->close();
        }
        $this->statements = [];

        // cancel transaction
        if ($this->isTransaction()) {
            $this->rollbackTransaction();
        }

        return parent::close();
    }

    /**
     * Commit the current transaction (if any).
     *
     * @return bool true on success; false on failure
     *
     * @see AbstractDatabase::beginTransaction()
     * @see AbstractDatabase::rollbackTransaction()
     */
    public function commitTransaction(): bool
    {
        if ($this->transaction && $this->exec('COMMIT TRANSACTION;')) {
            $this->transaction = false;

            return true;
        }

        return false;
    }

    /**
     * Compact the database.
     *
     * <b>NB:</b> Make sure there is no transaction open when the command is executed. For more information
     * see: <a href="https://www.sqlitetutorial.net/sqlite-vacuum/" target="_blank">SQLite VACUUM</a>
     *
     * @return bool true if success
     */
    public function compact(): bool
    {
        return $this->exec('VACUUM;');
    }

    /**
     * Gets the file name.
     *
     * @return string the file name
     */
    public function getFilename(): string
    {
        return $this->filename;
    }

    /**
     * Gets the number of records for the given table name.
     *
     * @return non-negative-int
     */
    public function getRecordsCount(string $table): int
    {
        if (!StringUtils::pregMatch('/^\w+$/', $table)) {
            throw new \InvalidArgumentException(\sprintf('Invalid table name: "%s".', $table));
        }

        /** @phpstan-var non-negative-int */
        return $this->querySingle(\sprintf('SELECT COUNT(1) FROM %s;', $table)) ?? 0;
    }

    /**
     * Returns if a transaction is active.
     *
     * @return bool true if a transaction is active
     */
    public function isTransaction(): bool
    {
        return $this->transaction;
    }

    /**
     * Set a pragma statement.
     *
     * @param string     $name  the pragma name
     * @param mixed|null $value the optional pragma value
     *
     * @return bool true if succeeded; false on failure
     */
    public function pragma(string $name, mixed $value = null): bool
    {
        if (null !== $value) {
            return $this->exec(\sprintf('PRAGMA %s = %s', $name, $value));
        }

        return $this->exec('PRAGMA ' . $name);
    }

    /**
     * Roll back the current transaction (if any).
     *
     * @return bool true on success; false on failure
     *
     * @see AbstractDatabase::beginTransaction()
     * @see AbstractDatabase::commitTransaction()
     */
    public function rollbackTransaction(): bool
    {
        if ($this->transaction && $this->exec('ROLLBACK TRANSACTION;')) {
            $this->transaction = false;

            return true;
        }

        return false;
    }

    /**
     * Creates an index for the given table and columns.
     *
     * @param string $table      the table name
     * @param string ...$columns the column names
     *
     * @return bool true if creation succeeded; false on failure
     */
    protected function createIndex(string $table, string ...$columns): bool
    {
        $name = \sprintf('idx_%s_%s', $table, \implode('_', $columns));
        $indexed_columns = \implode(',', $columns);
        $query = \sprintf('CREATE INDEX IF NOT EXISTS %s ON %s(%s)', $name, $table, $indexed_columns);

        return $this->exec($query);
    }

    /**
     * Creates the database schema.
     *
     * This function is called when the database is opened with the <code>SQLITE3_OPEN_CREATE</code> flag.
     */
    abstract protected function createSchema(): void;

    /**
     * Execute the given statement and fetch with SQLITE3_ASSOC flag the result to an associative array.
     *
     * @param \SQLite3Stmt $stmt the statement to execute
     *
     * @return array<array<string, mixed>>
     */
    protected function executeAndFetch(\SQLite3Stmt $stmt): array
    {
        $result = $stmt->execute();
        if (!$result instanceof \SQLite3Result) {
            return [];
        }

        $rows = [];
        while (false !== ($row = $result->fetchArray(\SQLITE3_ASSOC))) {
            $rows[] = $row;
        }
        $result->finalize();

        return $rows;
    }

    /**
     * Gets a statement for the given query.
     *
     * <p>
     * NB: The statement is created only once and is cached for future use.
     * </p>
     *
     * @param string $query the SQL query to prepare
     *
     * @return ?\SQLite3Stmt the statement object on success; null on failure
     */
    protected function getStatement(string $query): ?\SQLite3Stmt
    {
        if (isset($this->statements[$query])) {
            return $this->statements[$query];
        }

        $statement = $this->prepare($query);
        if (false === $statement) {
            return null;
        }

        return $this->statements[$query] = $statement;
    }

    /**
     * Build a like value parameter.
     *
     * @param string $value the value parameter
     *
     * @return string the like value parameter
     */
    protected function likeValue(string $value): string
    {
        return '%' . \trim($value, " \n\r\t\v\0%") . '%';
    }

    /**
     * Search data.
     * <p>
     * <b>NB</b>: The SQL query must contain 2 parameters:
     * <ul>
     * <li>"<code>:value</code>" - The search parameter.</li>
     * <li>"<code>:limit</code>" - The limit parameter.</li>
     * </ul>
     * </p>.
     *
     * @param string $query the SQL query to prepare
     * @param string $value the value to search for
     * @param int    $limit the maximum number of rows to return
     *
     * @return array<array<string, mixed>>
     */
    protected function search(string $query, string $value, int $limit): array
    {
        /** @var \SQLite3Stmt $stmt */
        $stmt = $this->getStatement($query);
        $stmt->bindValue(':value', $this->likeValue($value));
        $stmt->bindValue(':limit', $limit, \SQLITE3_INTEGER);

        return $this->executeAndFetch($stmt);
    }
}
