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

namespace App\Service;

use App\Traits\ArrayTrait;
use App\Utils\StringUtils;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\ForeignKeyConstraint;
use Doctrine\DBAL\Schema\Index;
use Doctrine\DBAL\Schema\Index\IndexedColumn;
use Doctrine\DBAL\Schema\Index\IndexType;
use Doctrine\DBAL\Schema\Name;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\BooleanType;
use Doctrine\DBAL\Types\FloatType;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * Service to get database schema information.
 *
 * @phpstan-type SchemaColumnType=array{
 *     name: string,
 *     primary: bool,
 *     unique: bool,
 *     type: string,
 *     length: int,
 *     required: bool,
 *     foreign_table: string|null,
 *     default: string}
 * @phpstan-type SchemaIndexType=array{
 *     name: string,
 *     primary: bool,
 *     unique: bool,
 *     columns: string[]}
 * @phpstan-type SchemaAssociationType=array{
 *     name: string,
 *     inverse: bool,
 *     table: string}
 * @phpstan-type SchemaTableType=array{
 *     name: string,
 *     columns: SchemaColumnType[],
 *     indexes: SchemaIndexType[],
 *     associations: SchemaAssociationType[],
 *     records: int,
 *     size: int,
 *     sql_rows: string}
 */
class SchemaService
{
    use ArrayTrait;

    // Query to get records and sizes (MySQL platform)
    private const SQL_ALL = <<<SQL_QUERY
            SELECT
                TABLE_NAME AS 'name',
                TABLE_ROWS  AS 'records',
                (data_length + index_length) / 1024 AS 'size'
            FROM
                information_schema.tables
            WHERE
                table_schema = '%database%';
        SQL_QUERY;

    private ?Connection $connection = null;

    /**
     * @phpstan-var AbstractSchemaManager<\Doctrine\DBAL\Platforms\AbstractPlatform>
     */
    private ?AbstractSchemaManager $schemaManager = null;

    public function __construct(
        private readonly EntityManagerInterface $manager,
        #[Target('calculation.schema')]
        private readonly CacheInterface $cache
    ) {
    }

    /**
     * Get information for the given table.
     *
     * @phpstan-return SchemaTableType|array<array-key, mixed>
     */
    public function getTable(string $name): array
    {
        return $this->getTables(false)[\strtolower($name)] ?? [];
    }

    /**
     * Gets tables information.
     *
     * @param bool $updateRecords true to update the number of records and size
     *
     * @phpstan-return array<string, SchemaTableType>
     */
    public function getTables(bool $updateRecords = true): array
    {
        $tables = $this->cache->get('tables', fn (): array => $this->loadTables());
        if (!$updateRecords) {
            return $tables;
        }

        if ($this->isMySQLPlatform()) {
            return $this->countAll($tables);
        }

        foreach ($tables as &$table) {
            $table['records'] = $this->countRecords($table);
        }

        return $tables;
    }

    /**
     * Returns if the given table exists.
     */
    public function tableExists(string $name): bool
    {
        return [] !== $this->getTable($name);
    }

    /**
     * @phpstan-param array<string, SchemaTableType> $tables
     *
     * @phpstan-return array<string, SchemaTableType>
     */
    private function countAll(array $tables): array
    {
        $result = null;

        try {
            $connection = $this->getConnection();
            $database = $connection->getDatabase();
            if (!\is_string($database)) {
                return $tables;
            }

            $sql = \str_replace('%database%', $database, self::SQL_ALL);
            $result = $connection->executeQuery($sql);
            $rows = $result->fetchAllAssociative();

            /** @phpstan-var array{name: string, records: int, size: int} $row */
            foreach ($rows as $row) {
                $name = $this->mapTableName($row['name']);
                if (\array_key_exists($name, $tables)) {
                    $tables[$name]['records'] = $row['records'];
                    $tables[$name]['size'] = $row['size'];
                }
            }
        } catch (\Doctrine\DBAL\Exception) {
            // ignore
        } finally {
            $result?->free();
        }

        return $tables;
    }

    /**
     * @phpstan-param SchemaTableType $table
     */
    private function countRecords(array $table): int
    {
        $result = null;

        try {
            $sql = $table['sql_rows'];
            $result = $this->getConnection()
                ->executeQuery($sql);

            return (int) $result->fetchOne();
        } catch (\Doctrine\DBAL\Exception) {
            return 0;
        } finally {
            $result?->free();
        }
    }

    /**
     * @phpstan-return SchemaTableType
     */
    private function createSchemaTable(Table $table): array
    {
        return [
            'name' => $this->mapTableName($table),
            'columns' => $this->getColumns($table),
            'indexes' => $this->getIndexes($table),
            'associations' => $this->getAssociations($table),
            'records' => 0,
            'size' => 0,
            'sql_rows' => $this->getSqlCounter($table),
        ];
    }

    /**
     * @param ForeignKeyConstraint[] $foreignKeys
     */
    private function findForeignTableName(string $name, array $foreignKeys): ?string
    {
        foreach ($foreignKeys as $foreignKey) {
            $columns = $this->mapNames($foreignKey->getReferencingColumnNames());
            if (\in_array($name, $columns, true)) {
                return $this->mapName($foreignKey->getReferencedTableName());
            }
        }

        return null;
    }

    /**
     * @phpstan-return array<SchemaAssociationType>
     */
    private function getAssociations(Table $table): array
    {
        $data = $this->getMetaData($table);
        if (!$data instanceof ClassMetadata) {
            return [];
        }
        $names = $data->getAssociationNames();
        if ([] === $names) {
            return [];
        }
        $result = [];
        foreach ($names as $name) {
            $target = $data->getAssociationTargetClass($name);
            $targetData = $this->getTargetMetaData($target);
            if ($targetData instanceof ClassMetadata) {
                $inverse = $data->isAssociationInverseSide($name);
                $result[] = [
                    'name' => \ucfirst($name),
                    'inverse' => $inverse,
                    'table' => $this->mapTableName($targetData),
                ];
            }
        }

        return $result;
    }

    /**
     * @phpstan-return array<SchemaColumnType>
     *
     * @psalm-suppress InternalMethod
     */
    private function getColumns(Table $table): array
    {
        $indexes = $table->getIndexes();
        $foreignKeys = $table->getForeignKeys();
        $primaryKeys = $this->getPrimaryKeys($table);

        return \array_map(function (Column $column) use ($primaryKeys, $indexes, $foreignKeys): array {
            $name = \strtolower($column->getName());
            $primary = \in_array($name, $primaryKeys, true);
            $unique = $this->isIndexUnique($name, $indexes);
            $foreignTable = $this->findForeignTableName($name, $foreignKeys);

            return [
                'name' => $name,
                'primary' => $primary,
                'unique' => $unique,
                'type' => $this->getColumnType($column),
                'length' => $column->getLength() ?? 0,
                'required' => $column->getNotnull(),
                'foreign_table' => $foreignTable,
                'default' => $this->getDefaultValue($column),
            ];
        }, $table->getColumns());
    }

    private function getColumnType(Column $column): string
    {
        try {
            return Type::getTypeRegistry()->lookupName($column->getType());
        } catch (\Doctrine\DBAL\Exception) {
            return 'unknown';
        }
    }

    private function getConnection(): Connection
    {
        return $this->connection ??= $this->manager->getConnection();
    }

    private function getDefaultValue(Column $column): string
    {
        /** @phpstan-var string|null $default */
        $default = $column->getDefault();
        if (!\is_string($default)) {
            return '';
        }

        $type = $column->getType();
        if ($type instanceof FloatType && '0' === $default) {
            return '0.00';
        }
        if ($type instanceof BooleanType) {
            $default = StringUtils::encodeJson(\filter_var($default, \FILTER_VALIDATE_BOOLEAN));
        }

        return StringUtils::capitalize(\trim($default, "'"));
    }

    /**
     * @phpstan-return array<SchemaIndexType>
     *
     * @psalm-suppress InternalMethod
     */
    private function getIndexes(Table $table): array
    {
        $indexes = $table->getIndexes();
        $primaryColumns = $this->mapNames($table->getPrimaryKeyConstraint()?->getColumnNames() ?? []);
        $results = \array_map(function (Index $index) use ($primaryColumns): array {
            $columns = $this->mapIndexColumns($index);
            $primary = $columns === $primaryColumns;
            $unique = IndexType::UNIQUE === $index->getType();

            return [
                'name' => $index->getName(),
                'primary' => $primary,
                'unique' => $unique,
                'columns' => $columns,
            ];
        }, $indexes);

        \usort($results, $this->sortIndexes(...));

        return $results;
    }

    /**
     * @phpstan-return ClassMetadata<object>|null
     */
    private function getMetaData(Table|string $name): ?ClassMetadata
    {
        $name = $this->mapTableName($name);

        return $this->getMetaDatas()[$name] ?? null;
    }

    /**
     * @return array<string, ClassMetadata<object>>
     */
    private function getMetaDatas(): array
    {
        return $this->cache->get('metadata', fn (): array => $this->loadMetaDatas($this->manager));
    }

    /**
     * @return string[]
     */
    private function getPrimaryKeys(Table $table): array
    {
        return $this->mapNames($table->getPrimaryKeyConstraint()?->getColumnNames() ?? []);
    }

    /**
     * @phpstan-return AbstractSchemaManager<\Doctrine\DBAL\Platforms\AbstractPlatform>
     *
     * @throws \Doctrine\DBAL\Exception
     */
    private function getSchemaManager(): AbstractSchemaManager
    {
        return $this->schemaManager ??= $this->getConnection()->createSchemaManager();
    }

    /**
     * @psalm-suppress InternalMethod
     */
    private function getSqlCounter(Table $table): string
    {
        $name = $table->getName();
        $column = (string) \array_key_first($table->getColumns());

        return "SELECT COUNT($column) AS TOTAL FROM $name";
    }

    /**
     * @phpstan-return ClassMetadata<object>|null
     */
    private function getTargetMetaData(string $name): ?ClassMetadata
    {
        return $this->findFirst($this->getMetaDatas(), fn (ClassMetadata $data): bool => $data->getName() === $name);
    }

    /**
     * @param Index[] $indexes
     */
    private function isIndexUnique(string $name, array $indexes): bool
    {
        foreach ($indexes as $index) {
            if (\in_array($name, $this->mapIndexColumns($index), true)) {
                return IndexType::UNIQUE === $index->getType();
            }
        }

        return false;
    }

    private function isMySQLPlatform(): bool
    {
        try {
            $platform = $this->getConnection()
                ->getDatabasePlatform();

            return $platform instanceof AbstractMySQLPlatform;
        } catch (\Doctrine\DBAL\Exception) {
            return false;
        }
    }

    /**
     * @return array<string, ClassMetadata<object>>
     */
    private function loadMetaDatas(EntityManagerInterface $manager): array
    {
        $datas = \array_filter(
            $manager->getMetadataFactory()->getAllMetadata(),
            static fn (ClassMetadata $data): bool => !$data->isMappedSuperclass && !$data->isEmbeddedClass
        );

        return $this->mapToKeyValue(
            $datas,
            fn (ClassMetadata $data): array => [\strtolower($data->table['name']) => $data]
        );
    }

    /**
     * @phpstan-return array<string, SchemaTableType>
     *
     * @throws \Doctrine\DBAL\Exception
     */
    private function loadTables(): array
    {
        $tables = $this->mapToKeyValue(
            $this->getSchemaManager()->listTables(),
            fn (Table $table): array => [$this->mapTableName($table) => $this->createSchemaTable($table)]
        );
        \ksort($tables);

        return $tables;
    }

    /**
     * @return array<string>
     */
    private function mapIndexColumns(Index $index): array
    {
        return \array_map(
            fn (IndexedColumn $column): string => $this->mapName($column->getColumnName()),
            $index->getIndexedColumns()
        );
    }

    private function mapName(Name $name): string
    {
        return \strtolower($name->toString());
    }

    /**
     * @param array<Name> $names
     *
     * @return string[]
     */
    private function mapNames(array $names): array
    {
        return \array_map(fn (Name $name): string => $this->mapName($name), $names);
    }

    /**
     * @phpstan-param Table|ClassMetadata<object>|string $name
     *
     * @psalm-suppress InternalMethod
     */
    private function mapTableName(Table|Name|ClassMetadata|string $name): string
    {
        if ($name instanceof Table) {
            $name = $name->getName();
        } elseif ($name instanceof Name) {
            $name = $name->toString();
        } elseif ($name instanceof ClassMetadata) {
            $name = $name->table['name'];
        }

        return $this->findFirst(
            \array_keys($this->getMetaDatas()),
            fn (string $value): bool => StringUtils::equalIgnoreCase($value, $name)
        ) ?? \strtolower($name);
    }

    /**
     * @phpstan-param SchemaIndexType $a
     * @phpstan-param SchemaIndexType $b
     */
    private function sortIndexes(array $a, array $b): int
    {
        // sort first by primary index
        $result = $b['primary'] <=> $a['primary'];
        if (0 !== $result) {
            return $result;
        }

        return $a['name'] <=> $b['name'];
    }
}
