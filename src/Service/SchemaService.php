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
use App\Traits\CacheAwareTrait;
use App\Utils\StringUtils;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\ForeignKeyConstraint;
use Doctrine\DBAL\Schema\Index;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\BooleanType;
use Doctrine\DBAL\Types\FloatType;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Symfony\Contracts\Service\ServiceMethodsSubscriberTrait;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

/**
 * Service to get database schema information.
 *
 * @psalm-type SchemaColumnType=array{
 *     name: string,
 *     primary: bool,
 *     unique: bool,
 *     type: string,
 *     length: int,
 *     required: bool,
 *     foreign_table: string|null,
 *     default: string}
 * @psalm-type SchemaIndexType=array{
 *     name: string,
 *     primary: bool,
 *     unique: bool,
 *     columns: string[]}
 * @psalm-type SchemaAssociationType=array{
 *     name: string,
 *     inverse: bool,
 *     table: string}
 * @psalm-type SchemaTableType=array{
 *     name: string,
 *     columns: SchemaColumnType[],
 *     indexes: SchemaIndexType[],
 *     associations: SchemaAssociationType[],
 *     records: int,
 *     size: float,
 *     sql_rows: string}
 */
class SchemaService implements ServiceSubscriberInterface
{
    use ArrayTrait;
    use CacheAwareTrait;
    use ServiceMethodsSubscriberTrait;

    // Query to update records and sizes (MySQL platform)
    private const SQL_ALL = <<<SQL_QUERY
            SELECT
                TABLE_NAME AS 'name',
                TABLE_ROWS as 'records',
                (data_length + index_length) AS 'size'
            FROM
                information_schema.tables
            WHERE
                table_schema = '%database%';
        SQL_QUERY;

    private ?Connection $connection = null;

    /**
     * @psalm-var AbstractSchemaManager<\Doctrine\DBAL\Platforms\AbstractPlatform>
     */
    private ?AbstractSchemaManager $schemaManager = null;

    public function __construct(private readonly EntityManagerInterface $manager)
    {
    }

    public function getCacheTimeout(): int
    {
        return 86_400; // 1 day
    }

    /**
     * Get table information.
     *
     * @param string $name the table's name to get information for
     *
     * @return SchemaTableType
     */
    public function getTable(string $name): array
    {
        /** @psalm-var SchemaTableType */
        return $this->getCacheValue("schema_service.metadata.table.$name", fn (): array => $this->loadTable($name));
    }

    /**
     * Gets tables information.
     *
     * @return array<string, SchemaTableType>
     */
    public function getTables(): array
    {
        /** @psalm-var array<string, SchemaTableType> $tables */
        $tables = $this->getCacheValue('schema_service.tables', fn (): array => $this->loadTables());

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
     *
     * @throws \Doctrine\DBAL\Exception
     */
    public function tableExist(string $name): bool
    {
        return $this->getSchemaManager()->tablesExist([$name]);
    }

    /**
     * @psalm-param array<string, SchemaTableType> $tables
     *
     * @psalm-return array<string, SchemaTableType>
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
            $result = $this->getConnection()
                ->executeQuery($sql);
            $rows = $result->fetchAllAssociative();
            /** @psalm-var array{name: string, records: int, size: int} $row */
            foreach ($rows as $row) {
                $name = $this->mapTableName($row['name']);
                if (!\array_key_exists($name, $tables)) {
                    continue;
                }
                $tables[$name]['records'] = $row['records'];
                $tables[$name]['size'] = (float) $row['size'] / 1024.0;
            }
        } catch (\Doctrine\DBAL\Exception) {
        } finally {
            $result?->free();
        }

        return $tables;
    }

    /**
     * @psalm-param SchemaTableType $table
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
     * @pslam-return SchemaTableType
     */
    private function createSchemaTable(Table $table): array
    {
        return [
            'name' => $this->mapTableName($table),
            'columns' => $this->getColumns($table),
            'indexes' => $this->getIndexes($table),
            'associations' => $this->getAssociations($table),
            'sql_rows' => $this->getSqlCounter($table),
            'records' => 0,
            'size' => 0,
        ];
    }

    /**
     * @param ForeignKeyConstraint[] $foreignKeys
     */
    private function findForeignTableName(string $name, array $foreignKeys): ?string
    {
        foreach ($foreignKeys as $foreignKey) {
            $columns = $foreignKey->getLocalColumns();
            if (\in_array($name, $columns, true)) {
                return $this->mapTableName($foreignKey->getForeignTableName());
            }
        }

        return null;
    }

    /**
     * @pslam-return array<SchemaAssociationType>
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
                    'name' => $name,
                    'inverse' => $inverse,
                    'table' => $this->mapTableName($targetData),
                ];
            }
        }

        return $result;
    }

    /**
     * @pslam-return array<SchemaColumnType>
     */
    private function getColumns(Table $table): array
    {
        $indexes = $table->getIndexes();
        $foreignKeys = $table->getForeignKeys();
        $primaryKeys = $this->getPrimaryKeys($table);

        return \array_map(function (Column $column) use ($primaryKeys, $indexes, $foreignKeys): array {
            $name = $column->getName();
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
        if (!$this->connection instanceof Connection) {
            $this->connection = $this->manager->getConnection();
        }

        return $this->connection;
    }

    private function getDefaultValue(Column $column): string
    {
        /** @psalm-var string|null $default */
        $default = $column->getDefault();
        if (!\is_string($default)) {
            return '';
        }

        $type = $column->getType();
        if ($type instanceof BooleanType) {
            return StringUtils::encodeJson(\filter_var($default, \FILTER_VALIDATE_BOOLEAN));
        }
        if ('0' === $default && $type instanceof FloatType) {
            return '0.00';
        }

        return $default;
    }

    /**
     * @pslam-return array<SchemaIndexType>
     */
    private function getIndexes(Table $table): array
    {
        $indexes = $table->getIndexes();

        $results = \array_map(fn (Index $index): array => [
            'name' => \strtolower($index->getName()),
            'primary' => $index->isPrimary(),
            'unique' => $index->isUnique(),
            'columns' => $index->getColumns(),
        ], $indexes);

        return $this->sortIndexes($results);
    }

    /**
     * @psalm-return ClassMetadata<object>|null
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
        /** @psalm-var array<string, ClassMetadata<object>> */
        return $this->getCacheValue(
            'schema_service.metadata',
            fn (): array => $this->loadMetaDatas($this->manager)
        );
    }

    /**
     * @return string[]
     */
    private function getPrimaryKeys(Table $table): array
    {
        return $table->getPrimaryKey()?->getColumns() ?? [];
    }

    /**
     * @psalm-return AbstractSchemaManager<\Doctrine\DBAL\Platforms\AbstractPlatform>
     *
     * @throws \Doctrine\DBAL\Exception
     */
    private function getSchemaManager(): AbstractSchemaManager
    {
        if (!$this->schemaManager instanceof AbstractSchemaManager) {
            $this->schemaManager = $this->getConnection()->createSchemaManager();
        }

        return $this->schemaManager;
    }

    private function getSqlCounter(Table $table): string
    {
        $name = $table->getName();
        $column = (string) \array_key_first($table->getColumns());

        return "SELECT COUNT($column) AS TOTAL FROM $name";
    }

    /**
     * @psalm-return ClassMetadata<object>|null
     */
    private function getTargetMetaData(string $name): ?ClassMetadata
    {
        return $this->findFirst($this->getMetaDatas(), fn (ClassMetadata $data): bool => $data->getName() === $name);
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    private function introspectTable(string $name): Table
    {
        return $this->getSchemaManager()->introspectTable($name);
    }

    /**
     * @param Index[] $indexes
     */
    private function isIndexUnique(string $name, array $indexes): bool
    {
        foreach ($indexes as $index) {
            if (\in_array($name, $index->getColumns(), true)) {
                return $index->isUnique();
            }
        }

        return false;
    }

    private function isMySQLPlatform(): bool
    {
        try {
            $connection = $this->getConnection();
            $platform = $connection->getDatabasePlatform();

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
        $result = [];
        $datas = $manager->getMetadataFactory()->getAllMetadata();
        foreach ($datas as $data) {
            if ($data->isMappedSuperclass || $data->isEmbeddedClass) {
                continue;
            }
            $result[$data->table['name']] = $data;
        }

        return $result;
    }

    /**
     * @pslam-return SchemaTableType
     *
     * @throws \Doctrine\DBAL\Exception
     */
    private function loadTable(string $name): array
    {
        $table = $this->introspectTable($name);

        return $this->createSchemaTable($table);
    }

    /**
     * @pslam-return array<string, SchemaTableType>
     *
     * @throws \Doctrine\DBAL\Exception
     */
    private function loadTables(): array
    {
        $result = [];
        $tables = $this->getSchemaManager()->listTables();
        foreach ($tables as $table) {
            $name = $this->mapTableName($table);
            $result[$name] = $this->createSchemaTable($table);
        }
        \ksort($result);

        return $result;
    }

    /**
     * @psalm-param Table|ClassMetadata<object>|string $name
     */
    private function mapTableName(Table|ClassMetadata|string $name): string
    {
        if ($name instanceof Table) {
            $name = $name->getName();
        } elseif ($name instanceof ClassMetadata) {
            $name = $name->table['name'];
        }

        return $this->findFirst(
            \array_keys($this->getMetaDatas()),
            fn (string $value): bool => StringUtils::equalIgnoreCase($value, $name)
        ) ?? $name;
    }

    /**
     * @psalm-param SchemaIndexType[] $indexes
     *
     * @psalm-return SchemaIndexType[]
     */
    private function sortIndexes(array &$indexes): array
    {
        /**
         * @psalm-param SchemaIndexType $a
         * @psalm-param SchemaIndexType $b
         */
        $callback = static function (array $a, array $b): int {
            if ($a['primary']) {
                return -1;
            }
            if ($b['primary']) {
                return 1;
            }

            return \strnatcmp((string) $a['name'], (string) $b['name']);
        };

        \usort($indexes, $callback);

        return $indexes;
    }
}
