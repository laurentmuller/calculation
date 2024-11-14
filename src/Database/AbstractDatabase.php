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

/**
 * Extended the SQLite3 database with transaction support and caching SQL statements.
 */
abstract class AbstractDatabase extends \SQLite3 implements \Stringable
{
    /**
     * The in-memory database file name.
     */
    final public const IN_MEMORY = ':memory:';

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
     * @param string $filename       Path to the SQLite database, or <code>:memory:</code> to use
     *                               the in-memory database.
     *                               If the filename is an empty string, then a private, temporary on-disk database
     *                               will be created.
     *                               This private database will be automatically deleted as soon as the database
     *                               connection is closed.
     * @param bool   $readonly       <code>true</code> open the database for reading only. Notes that if the file name
     *                               does not exist, the database is opened with the
     *                               <code>SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE</code> flags.
     * @param string $encryption_key an optional encryption key used when encrypting and decrypting an SQLite database
     */
    public function __construct(protected string $filename, bool $readonly = false, string $encryption_key = '')
    {
        // check creation state
        $create = '' === $filename || self::IN_MEMORY === $filename
            || !FileUtils::exists($filename) || FileUtils::empty($filename);

        if ($create) {
            $flags = \SQLITE3_OPEN_READWRITE | \SQLITE3_OPEN_CREATE;
        } elseif ($readonly) {
            $flags = \SQLITE3_OPEN_READONLY;
        } else {
            $flags = \SQLITE3_OPEN_READWRITE;
        }

        parent::__construct($filename, $flags, $encryption_key);

        // create schema
        if ($create) {
            $this->createSchema();
        }
    }

    /**
     * Returns a string representing this object.
     */
    public function __toString(): string
    {
        return $this->getFilename();
    }

    /**
     * Begin a transaction.
     *
     * @return bool true on success; false on failure
     *
     * @see AbstractDatabase::commitTransaction()
     * @see AbstractDatabase::rollbackTransaction()
     */
    public function beginTransaction(): bool
    {
        if (!$this->transaction && $this->exec('BEGIN TRANSACTION;')) {
            $this->transaction = true;

            return true;
        }

        return false;
    }

    /**
     * Closes the database connection.
     *
     * All opened statements are also closed.
     * If a transaction is active, then it is canceled (rollback).
     */
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
     * Gets the number of records for the given table.
     *
     * @return int<0, max>
     */
    public function getRecordsCount(string $table): int
    {
        $query = "SELECT COUNT(1) FROM $table";
        $result = $this->querySingle($query);

        /** @psalm-var int<0, max> */
        return \is_int($result) ? $result : 0;
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
            return $this->exec("PRAGMA $name = $value");
        }

        return $this->exec("PRAGMA $name");
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
        $query = "CREATE INDEX IF NOT EXISTS $name ON $table($indexed_columns)";

        return $this->exec($query);
    }

    /**
     * Creates the database schema.
     *
     * This function is called when the database is opened with the <code>SQLITE3_OPEN_CREATE</code> flag.
     */
    abstract protected function createSchema(): void;

    /**
     * Execute the given statement and fetch the result to an associative array.
     *
     * @param \SQLite3Stmt $stmt the statement to execute
     * @param int          $mode controls how the next row will be returned to the caller. This value
     *                           must be one of either SQLITE3_ASSOC (default), SQLITE3_NUM, or SQLITE3_BOTH.
     *
     * @psalm-template T of array<string, mixed>
     *
     * @psalm-param int<1,3> $mode
     *
     * @psalm-return list<T>
     *
     * @phpstan-ignore method.templateTypeNotInParameter
     */
    protected function executeAndFetch(\SQLite3Stmt $stmt, int $mode = \SQLITE3_ASSOC): array
    {
        $result = $stmt->execute();
        if (!$result instanceof \SQLite3Result) {
            return [];
        }

        /** @psalm-var list<T> $rows */
        $rows = [];
        while ($row = $result->fetchArray($mode)) {
            $rows[] = $row;
        }
        $result->finalize();

        /** @psalm-var list<T> */
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
        return '%' . \trim($value) . '%';
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
     * @param int    $mode  controls how the next row will be returned to the caller. This value
     *                      must be one of either SQLITE3_ASSOC (default), SQLITE3_NUM, or SQLITE3_BOTH.
     *
     * @psalm-template T of array<string, mixed>
     *
     * @psalm-param int<1,3> $mode $mode
     *
     * @psalm-return array<int, T>
     *
     * @phpstan-ignore method.templateTypeNotInParameter
     */
    protected function search(string $query, string $value, int $limit, int $mode = \SQLITE3_ASSOC): array
    {
        $value = $this->likeValue($value);

        /** @var \SQLite3Stmt $stmt */
        $stmt = $this->getStatement($query);
        $stmt->bindValue(':value', $value);
        $stmt->bindValue(':limit', $limit, \SQLITE3_INTEGER);

        /** @psalm-var array<int, T> */
        return $this->executeAndFetch($stmt, $mode);
    }
}
