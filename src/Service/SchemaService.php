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
use App\Util\StringUtils;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\ForeignKeyConstraint;
use Doctrine\DBAL\Schema\Index;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\BooleanType;
use Doctrine\DBAL\Types\FloatType;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
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
 *     foreign_name: string|null,
 *     default: string}
 * @psalm-type SchemaIndexType=array{
 *     name: string,
 *     primary: bool,
 *     unique: bool,
 *     columns: array<string>}
 * @psalm-type SchemaAssociationType=array{
 *     name: string,
 *     inverse: bool,
 *     table: string}
 * @psalm-type SchemaTableType=array{
 *          name: string,
 *          columns: array<SchemaColumnType>,
 *          records: int,
 *          indexes: array<SchemaIndexType>,
 *          associations: array<SchemaAssociationType>}
 * @psalm-type SchemaSoftTableType=array{
 *          name: string,
 *          columns: int,
 *          records: int,
 *          indexes: int,
 *          associations: int}
 */
class SchemaService implements ServiceSubscriberInterface
{
    use CacheAwareTrait;
    use ServiceSubscriberTrait;

    /**
     * The cache TTL (1 day).
     */
    private const LIFE_TIME = 86_400;

    private ?Connection $connection = null;

    /**
     * @psalm-var AbstractSchemaManager<\Doctrine\DBAL\Platforms\AbstractPlatform>
     */
    private ?AbstractSchemaManager $schemaManager = null;

    public function __construct(private readonly EntityManagerInterface $manager)
    {
    }

    /**
     * Get table information.
     *
     * @pslam-return SchemaTableType
     *
     * @throws Exception
     */
    public function getTable(string $name): array
    {
        $key = 'schema_service.metadata.table.' . $name;

        /** @psalm-var SchemaTableType|null $results */
        $results = $this->getCacheValue($key);
        if (\is_array($results)) {
            return $results;
        }

        $table = $this->introspectTable($name);
        $results = [
            'name' => $name,
            'columns' => $this->getColumns($table),
            'indexes' => $this->getIndexes($table),
            'associations' => $this->getAssociations($table),
        ];
        $this->setCacheValue($key, $results, self::LIFE_TIME);

        return $results;
    }

    /**
     * Gets tables information.
     *
     * @pslam-return SchemaSoftTableType[]
     *
     * @throws Exception
     */
    public function getTables(): array
    {
        /** @psalm-var SchemaSoftTableType[] $results */
        $results = $this->getCacheValue('schema_service.tables', fn () => $this->loadTables());

        // update records
        foreach ($results as &$result) {
            $result['records'] = $this->countRecords($result['name']);
        }

        return $results;
    }

    private function countAssociationNames(Table $table): int
    {
        $name = $this->mapTableName($table->getName());
        $metaData = $this->getTableMetaData($name);

        return $metaData instanceof ClassMetadataInfo ? \count($metaData->getAssociationNames()) : 0;
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
     * @throws Exception
     */
    private function countRecords(string|Table $nameOrTable): int
    {
        $result = null;
        $table = \is_string($nameOrTable) ? $this->introspectTable($nameOrTable) : $nameOrTable;
        $column = \array_key_first($table->getColumns());
        $name = $table->getName();

        try {
            $result = $this->getConnection()->executeQuery("SELECT COUNT($column) AS TOTAL FROM $name");

            return (int) $result->fetchOne();
        } catch (Exception) {
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
        $name = $this->mapTableName($table->getName());
        $metaData = $this->getTableMetaData($name);
        if (!$metaData instanceof ClassMetadataInfo) {
            return [];
        }
        $associationNames = $metaData->getAssociationNames();
        if ([] === $associationNames) {
            return [];
        }
        $result = [];
        foreach ($associationNames as $associationName) {
            $targetClass = $metaData->getAssociationTargetClass($associationName);
            $inverse = $metaData->isAssociationInverseSide($associationName);
            $targetData = $this->getTargetMetaData($targetClass);
            if ($targetData instanceof ClassMetadataInfo) {
                $result[] = [
                    'name' => $associationName,
                    'inverse' => $inverse,
                    'table' => $this->mapTableName($targetData->table['name']),
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
            $foreignTableName = $this->findForeignTableName($name, $foreignKeys);

            return [
                'name' => $name,
                'primary' => $primary,
                'unique' => $unique,
                'type' => $this->getColumnType($column),
                'length' => $column->getLength() ?? 0,
                'required' => $column->getNotnull(),
                'foreign_name' => $foreignTableName,
                'default' => $this->getDefaultValue($column),
            ];
        }, $table->getColumns());
    }

    private function getColumnType(Column $column): string
    {
        try {
            return Type::getTypeRegistry()->lookupName($column->getType());
        } catch (Exception) {
            return 'unknown';
        }
    }

    private function getConnection(): Connection
    {
        if (null === $this->connection) {
            $this->connection = $this->manager->getConnection();
        }

        return $this->connection;
    }

    private function getDefaultValue(Column $column): string
    {
        $type = $column->getType();
        $default = $column->getDefault() ?? '';
        if ('' !== $default && $type instanceof BooleanType) {
            return (string) \json_encode(\filter_var($default, \FILTER_VALIDATE_BOOLEAN));
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

        return \array_map(function (Index $index): array {
            return [
                'name' => \strtolower($index->getName()),
                'primary' => $index->isPrimary(),
                'unique' => $index->isUnique(),
                'columns' => $index->getColumns(),
            ];
        }, $indexes);
    }

    /**
     * @return array<string, ClassMetadataInfo<object>>
     */
    private function getMetaDatas(): array
    {
        /** @psalm-var array<string, ClassMetadataInfo<object>> $metaDatas */
        $metaDatas = $this->getCacheValue(
            'schema_service.metadata',
            fn () => $this->loadMetaDatas($this->manager),
            self::LIFE_TIME
        );

        return $metaDatas;
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
     * @throws Exception
     */
    private function getSchemaManager(): AbstractSchemaManager
    {
        if (null === $this->schemaManager) {
            $this->schemaManager = $this->getConnection()->createSchemaManager();
        }

        return $this->schemaManager;
    }

    /**
     * @psalm-return ClassMetadataInfo<object>|null
     */
    private function getTableMetaData(string $name): ?ClassMetadataInfo
    {
        return $this->getMetaDatas()[$name] ?? null;
    }

    /**
     * @psalm-return ClassMetadataInfo<object>|null
     */
    private function getTargetMetaData(string $name): ?ClassMetadataInfo
    {
        foreach ($this->getMetaDatas() as $metaData) {
            if ($metaData->getName() === $name) {
                return $metaData;
            }
        }

        return null;
    }

    /**
     * @throws Exception
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
     * @return array<string, ClassMetadataInfo<object>>
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
     * @pslam-return SchemaSoftTableType[]
     *
     * @throws Exception
     */
    private function loadTables(): array
    {
        $tables = $this->getSchemaManager()->listTables();
        \usort($tables, static fn (Table $a, Table $b): int => \strnatcmp($a->getName(), $b->getName()));

        return \array_map(function (Table $table): array {
            return [
                'name' => $this->mapTableName($table->getName()),
                'columns' => $this->countColumns($table),
                'indexes' => $this->countIndexes($table),
                'associations' => $this->countAssociationNames($table),
            ];
        }, $tables);
    }

    private function mapTableName(string $name): string
    {
        foreach (\array_keys($this->getMetaDatas()) as $key) {
            if (StringUtils::equalIgnoreCase($key, $name)) {
                return $key;
            }
        }

        return $name;
    }
}
