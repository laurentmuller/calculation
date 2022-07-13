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

namespace App\Controller;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\ForeignKeyConstraint;
use Doctrine\DBAL\Schema\Index;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\BooleanType;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Controller to display the database schema.
 */
#[AsController]
#[IsGranted('ROLE_SUPER_ADMIN')]
#[Route(path: '/schema')]
class SchemaController extends AbstractController
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
     * @var array<string, ClassMetadataInfo>
     */
    private readonly array $metaDatas;

    /**
     * Constructor.
     *
     * @throws \Doctrine\DBAL\Exception
     */
    public function __construct(EntityManagerInterface $manager)
    {
        $this->connection = $manager->getConnection();
        $this->manager = $this->connection->createSchemaManager();
        $this->metaDatas = $this->filterMetaDatas($manager);
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    #[Route(path: '', name: 'schema')]
    public function index(): Response
    {
        return $this->renderForm('schema/index.html.twig', [
            'tables' => $this->getTables(),
        ]);
    }

    /**
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException if the table can not be found
     * @throws \Doctrine\DBAL\Exception
     */
    #[Route(path: '/{name}', name: 'schema_table')]
    public function table(string $name): Response
    {
        if (!$this->tableExist($name)) {
            throw $this->createNotFoundException($this->trans('schema.table.error', ['%name%' => $name]));
        }

        return $this->renderForm('schema/table.html.twig', [
            'name' => $name,
            'columns' => $this->getColumns($name),
            'associations' => $this->getAssociations($name),
        ]);
    }

    private function countAssociationNames(string $name): int
    {
        $metaData = $this->getTableMetaData($name);

        return $metaData instanceof ClassMetadataInfo ? \count($metaData->getAssociationNames()) : 0;
    }

    /**
     * @return array<string, ClassMetadataInfo>
     */
    private function filterMetaDatas(EntityManagerInterface $manager): array
    {
        $result = [];
        $metaDatas = $manager->getMetadataFactory()->getAllMetadata();
        foreach ($metaDatas as $data) {
            if (!$data->isMappedSuperclass && !$data->isEmbeddedClass) {
                $result[\strtolower($data->table['name'])] = $data;
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
                return $foreignKey->getForeignTableName();
            }
        }

        return null;
    }

    /**
     * @return array<array{name: string, table: string, inverseSide: bool}>
     */
    private function getAssociations(string $name): array
    {
        $metaData = $this->getTableMetaData($name);
        if (!$metaData instanceof ClassMetadataInfo) {
            return [];
        }
        $associationNames = $metaData->getAssociationNames();
        if (empty($associationNames)) {
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
                    'table' => $targetData->table['name'],
                    'inverseSide' => $inverseSide,
                    ];
            }
        }

        return $result;
    }

    /**
     * Gets the columns for the given table name.
     *
     * @return array<array{
     *      name: string,
     *      primaryKey: bool,
     *      unique: bool,
     *      type: string,
     *      length: int,
     *      nullable: bool,
     *      foreignTableName: null|string}>
     *
     * @throws \Doctrine\DBAL\Exception
     */
    private function getColumns(string $name): array
    {
        $table = $this->getManager()->listTableDetails($name);
        $indexes = $table->getIndexes();
        $foreignKeys = $table->getForeignKeys();
        $primaryKeys = $this->getPrimaryKeys($table);

        return \array_map(function (Column $column) use ($primaryKeys, $indexes, $foreignKeys): array {
            $name = $column->getName();
            $isPrimaryKey = \in_array($name, $primaryKeys, true);
            $unique = $this->isIndexUnique($name, $indexes);
            $foreignTableName = $this->findForeignTableName($name, $foreignKeys);

            return [
                'name' => $name,
                'primaryKey' => $isPrimaryKey,
                'unique' => $unique,
                'type' => $column->getType()->getName(),
                'length' => (int) $column->getLength(),
                'nullable' => !$column->getNotnull(),
                'foreignTableName' => $foreignTableName,
                'default' => $this->getDefaultValue($column),
            ];
        }, $table->getColumns());
    }

    private function getDefaultValue(Column $column): string
    {
        $default = $column->getDefault();
        if (null !== $default && $column->getType() instanceof BooleanType) {
            /** @psalm-var bool $value */
            $value = \filter_var($default, \FILTER_VALIDATE_BOOLEAN);

            return \ucfirst((string) \json_encode($value));
        }

        return $default ?? '';
    }

    /**
     * @return AbstractSchemaManager<\Doctrine\DBAL\Platforms\AbstractPlatform>
     */
    private function getManager(): AbstractSchemaManager
    {
        return $this->manager;
    }

    /**
     * @return string[]
     *
     * @throws \Doctrine\DBAL\Exception
     */
    private function getPrimaryKeys(Table $table): array
    {
        if ($table->hasPrimaryKey()) {
            return \array_map(static fn (Column $c): string => $c->getName(), $table->getPrimaryKeyColumns());
        }

        return [];
    }

    /**
     * @param Column[] $columns
     */
    private function getRecords(Table $table, array $columns): int
    {
        $name = $table->getName();
        if (!isset($this->counters[$name])) {
            try {
                $column = \array_key_first($columns);
                $result = $this->connection->executeQuery("SELECT COUNT($column) AS TOTAL FROM $name");
                /** @psalm-var int $count */
                $count = $result->fetchOne();
                $result->free();
            } catch (\Doctrine\DBAL\Exception) {
                $count = 0;
            }
            $this->counters[$name] = $count;
        }

        return $this->counters[$name];
    }

    private function getTableMetaData(string $name): ?ClassMetadataInfo
    {
        return $this->metaDatas[\strtolower($name)] ?? null;
    }

    /**
     * Gets the tables.
     *
     * @return array<array{
     *      name: string,
     *      columns: int,
     *      associations: int}>
     *
     * @throws \Doctrine\DBAL\Exception
     */
    private function getTables(): array
    {
        $tables = $this->getManager()->listTables();
        \usort($tables, static fn (Table $a, Table $b): int => \strnatcmp($a->getName(), $b->getName()));

        return \array_map(function (Table $table): array {
            $name = $table->getName();
            $columns = $table->getColumns();

            return [
                'name' => $name,
                'columns' => \count($columns),
                'records' => $this->getRecords($table, $columns),
                'associations' => $this->countAssociationNames($name),
             ];
        }, $tables);
    }

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
     * @throws \Doctrine\DBAL\Exception
     */
    private function tableExist(string $name): bool
    {
        return $this->getManager()->tablesExist([$name]);
    }
}
