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

/**
 * Service to get database schema information.
 *
 * @psalm-type SchemaColumnType=array{
 *     name: string,
 *     primary: bool,
 *     unique: bool,
 *     type: string,
 *     length: int,
 *     nullable: bool,
 *     foreign_name: string|null,
 *     default: string}
 * @psalm-type SchemaIndexType=array{
 *     name: string,
 *     primary: bool,
 *     unique: bool,
 *     columns: array<string>}
 * @psalm-type SchemaAssociationType=array{
 *     name: string,
 *     inverseSide: bool,
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
class SchemaService
{
    private readonly Connection $connection;

    /**
     * @var array<string, int>
     */
    private array $counters = [];

    /**
     * @var AbstractSchemaManager<\Doctrine\DBAL\Platforms\AbstractPlatform>
     */
    private readonly AbstractSchemaManager $manager;

    /**
     * @var array<string, ClassMetadataInfo<object>>
     */
    private readonly array $metaDatas;

    /**
     * @throws Exception
     */
    public function __construct(EntityManagerInterface $manager)
    {
        $this->connection = $manager->getConnection();
        $this->manager = $this->connection->createSchemaManager();
        $this->metaDatas = $this->filterMetaDatas($manager);
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
        $table = $this->introspectTable($name);

        return [
            'name' => $name,
            'columns' => $this->getColumns($table),
            'indexes' => $this->getIndexes($table),
            'associations' => $this->getAssociations($name),
        ];
    }

    /**
     * @return string[]
     *
     * @throws Exception
     */
    public function getTableNames(): array
    {
        $tables = $this->manager->listTables();

        return \array_map(fn (Table $table) => $this->mapTableName($table->getName()), $tables);
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
        $tables = $this->manager->listTables();
        \usort($tables, static fn (Table $a, Table $b): int => \strnatcmp($a->getName(), $b->getName()));

        return \array_map(function (Table $table): array {
            $columns = $table->getColumns();
            $name = $this->mapTableName($table->getName());

            return [
                'name' => $name,
                'columns' => \count($columns),
                'indexes' => \count($this->getIndexes($table)),
                'records' => $this->countRecords($table, $columns),
                'associations' => $this->countAssociationNames($name),
            ];
        }, $tables);
    }

    private function countAssociationNames(string $name): int
    {
        $metaData = $this->getTableMetaData($name);

        return $metaData instanceof ClassMetadataInfo ? \count($metaData->getAssociationNames()) : 0;
    }

    /**
     * @param Column[] $columns
     */
    private function countRecords(Table $table, array $columns): int
    {
        $result = null;
        $name = $table->getName();
        if (!isset($this->counters[$name])) {
            try {
                $column = \array_key_first($columns);
                $result = $this->connection->executeQuery("SELECT COUNT($column) AS TOTAL FROM $name");
                $count = (int) $result->fetchOne();
            } catch (Exception) {
                $count = 0;
            } finally {
                $result?->free();
            }
            $this->counters[$name] = $count;
        }

        return $this->counters[$name];
    }

    /**
     * @return array<string, ClassMetadataInfo<object>>
     */
    private function filterMetaDatas(EntityManagerInterface $manager): array
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
    private function getAssociations(string $name): array
    {
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
            $inverseSide = $metaData->isAssociationInverseSide($associationName);
            $targetData = $this->getTargetMetaData($targetClass);
            if ($targetData instanceof ClassMetadataInfo) {
                $result[] = [
                    'name' => $associationName,
                    'inverseSide' => $inverseSide,
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
                'nullable' => !$column->getNotnull(),
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
            return 'Unknown';
        }
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
     * @return string[]
     */
    private function getPrimaryKeys(Table $table): array
    {
        return $table->getPrimaryKey()?->getColumns() ?? [];
    }

    /**
     * @psalm-return ClassMetadataInfo<object>|null
     */
    private function getTableMetaData(string $name): ?ClassMetadataInfo
    {
        return $this->metaDatas[$name] ?? null;
    }

    /**
     * @psalm-return ClassMetadataInfo<object>|null
     */
    private function getTargetMetaData(string $name): ?ClassMetadataInfo
    {
        foreach ($this->metaDatas as $metaData) {
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
        return $this->manager->introspectTable($name);
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

    private function mapTableName(string $name): string
    {
        foreach (\array_keys($this->metaDatas) as $key) {
            if (StringUtils::equalIgnoreCase($key, $name)) {
                return $key;
            }
        }

        return $name;
    }
}
