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

namespace App\Doctrine;

/**
 * Extended the SQLite3 database with transaction support.
 *
 * @author Laurent Muller
 */
abstract class AbstractDatabase extends \SQLite3
{
    /**
     * The file name.
     *
     * @var string
     */
    protected $filename;

    /**
     * The opened statements.
     *
     * @var \SQLite3Stmt[]
     */
    protected $statements = [];

    /**
     * The transaction state.
     *
     * @var bool
     */
    protected $transaction = false;

    /**
     * Instantiates and opens the database.
     *
     * @param string $filename       Path to the SQLite database, or <code>:memory:</code> to use in-memory database.
     *                               If filename is an empty string, then a private, temporary on-disk database will be created.
     *                               This private database will be automatically deleted as soon as the database connection is closed.
     * @param bool   $readonly       true open the database for reading only. Note that if the file name
     *                               does not exist, the database is opened with the
     *                               <code>SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE</code> flags.
     * @param string $encryption_key An optional encryption key used when encrypting and decrypting an SQLite database. If the
     *                               SQLite encryption module is not installed, this parameter will have no effect.
     */
    public function __construct(string $filename, bool $readonly = false, string $encryption_key = '')
    {
        $this->filename = $filename;
        $create = !\file_exists($filename) || 0 === \filesize($filename);

        if ($create) {
            $flags = SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE;
        } elseif ($readonly) {
            $flags = SQLITE3_OPEN_READONLY;
        } else {
            $flags = SQLITE3_OPEN_READWRITE;
        }

        parent::__construct($filename, $flags, $encryption_key);

        // schema
        if ($create) {
            $this->createSchema();
        }
    }

    /**
     * Returns a string representing this object.
     */
    public function __toString(): string
    {
        return $this->filename;
    }

    /**
     * Begin a transaction.
     *
     * @return bool true if success, false on failure
     */
    public function beginTransaction(): bool
    {
        if (!$this->isTransaction() && $this->exec('BEGIN TRANSACTION;')) {
            $this->transaction = true;

            return true;
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function close()
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
     * @return bool true if success, false on failure
     */
    public function commitTransaction()
    {
        if ($this->isTransaction() && $this->exec('COMMIT TRANSACTION;')) {
            $this->transaction = false;

            return true;
        }

        return false;
    }

    /**
     * Compact the database.
     *
     * <b>NB:</b> Make sure that there is no transaction open when the command is executed. For more information see: <a href="https://www.sqlitetutorial.net/sqlite-vacuum/" target="_blank" rel="noopener noreferrer">SQLite VACUUM</a>
     *
     * @return bool true if success
     */
    public function compact(): bool
    {
        return (bool) $this->exec('VACUUM;');
    }

    /**
     * Gets the file name.
     *
     * @return string the file name
     */
    public function getFilename()
    {
        return $this->filename;
    }

    /**
     * Returns if an transaction is active.
     *
     * @return bool true if an transaction is active
     */
    public function isTransaction(): bool
    {
        return $this->transaction;
    }

    /**
     * Rollback the current transaction (if any).
     *
     * @return bool true if success, false on failure
     */
    public function rollbackTransaction()
    {
        if ($this->isTransaction() && $this->exec('ROLLBACK TRANSACTION;')) {
            $this->transaction = false;

            return true;
        }

        return false;
    }

    /**
     * Creates an index.
     *
     * @param string $table  the table name
     * @param string $column the column name
     *
     * @return bool true if the creation succeeded, false on failure
     */
    protected function createIndex(string $table, string $column): bool
    {
        $name = "idx_{$table}_{$column}";
        $query = "CREATE INDEX IF NOT EXISTS {$name} ON {$table}({$column})";

        return (bool) $this->exec($query);
    }

    /**
     * Creates the database schema. This function is called when the database is opened with the <code>SQLITE3_OPEN_CREATE</code> flag.
     */
    abstract protected function createSchema(): void;

    /**
     * Gets a statement for the given query.
     *
     * @param string $query the SQL query to prepare
     *
     * @return \SQLite3Stmt the statement
     */
    protected function getStatement(string $query): \SQLite3Stmt
    {
        if (!isset($this->statements[$query])) {
            return $this->statements[$query] = $this->prepare($query);
        }

        return $this->statements[$query];
    }
}
