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

use App\Traits\CacheAwareTrait;
use App\Utils\StringUtils;
use Doctrine\DBAL\Connection;
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
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Symfony\Contracts\Service\ServiceSubscriberTrait;

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
 *     associations: SchemaAssociationType[]}
 * @psalm-type SchemaSoftTableType=array{
 *     name: string,
 *     columns: int,
 *     records: int,
 *     indexes: int,
 *     associations: int,
 *     sql: string}
 */
class SchemaService implements ServiceSubscriberInterface
{
    use CacheAwareTrait;
    use ServiceSubscriberTrait;

    // the cache timeout (1 day)
    private const CACHE_TIMEOUT = 86_400;

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
        return self::CACHE_TIMEOUT;
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
        /** @psalm-var SchemaTableType $result */
        $result = $this->getCacheValue("schema_service.metadata.table.$name", fn (): array => $this->loadTable($name));

        return $result;
    }

    /**
     * Gets tables information.
     *
     * @return SchemaSoftTableType[]
     */
    public function getTables(): array
    {
        /** @psalm-var SchemaSoftTableType[] $results */
        $results = $this->getCacheValue('schema_service.tables', fn (): array => $this->loadTables());

        // update records
        foreach ($results as &$result) {
            $result['records'] = $this->countRecords($result);
        }

        return $results;
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

    private function countAssociations(Table $table): int
    {
        $data = $this->getMetaData($table);
        if ($data instanceof ClassMetadata) {
            return \count($data->getAssociationNames());
        }

        return 0;
    }

    private function countColumns(Table $table): int
    {
        return \count($table->getColumns());
    }

    private function countIndexes(Table $table): int
    {
        return \count($table->getIndexes());
    }

    /**
     * @psalm-param SchemaSoftTableType $table
     */
    private function countRecords(array $table): int
    {
        $result = null;

        try {
            $sql = $table['sql'];
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
        /** @psalm-var array<string, ClassMetadata<object>> $result */
        $result = $this->getCacheValue(
            'schema_service.metadata',
            fn (): array => $this->loadMetaDatas($this->manager),
            self::CACHE_TIMEOUT
        );

        return $result;
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
        foreach ($this->getMetaDatas() as $data) {
            if ($data->getName() === $name) {
                return $data;
            }
        }

        return null;
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

    /**
     * @return array<string, ClassMetadata<object>>
     */
    private function loadMetaDatas(EntityManagerInterface $manager): array
    {
        $result = [];
        $datas = $manager->getMetadataFactory()->getAllMetadata();
        foreach ($datas as $data) {
            if (!$data->isMappedSuperclass && !$data->isEmbeddedClass) {
                $result[$data->table['name']] = $data;
            }
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

        return [
            'name' => $name,
            'columns' => $this->getColumns($table),
            'indexes' => $this->getIndexes($table),
            'associations' => $this->getAssociations($table),
        ];
    }

    /**
     * @pslam-return SchemaSoftTableType[]
     *
     * @throws \Doctrine\DBAL\Exception
     */
    private function loadTables(): array
    {
        $tables = $this->getSchemaManager()->listTables();
        \usort($tables, static fn (Table $a, Table $b): int => \strnatcmp($a->getName(), $b->getName()));

        return \array_map(fn (Table $table): array => [
            'name' => $this->mapTableName($table),
            'columns' => $this->countColumns($table),
            'indexes' => $this->countIndexes($table),
            'associations' => $this->countAssociations($table),
            'sql' => $this->getSqlCounter($table),
        ], $tables);
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
        foreach (\array_keys($this->getMetaDatas()) as $key) {
            if (StringUtils::equalIgnoreCase($key, $name)) {
                return $key;
            }
        }

        return $name;
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
