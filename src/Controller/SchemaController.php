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

use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\ForeignKeyConstraint;
use Doctrine\DBAL\Schema\Index;
use Doctrine\DBAL\Schema\Table;
use Doctrine\ORM\EntityManagerInterface;
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
    /**
     * @var AbstractSchemaManager<\Doctrine\DBAL\Platforms\AbstractPlatform>
     */
    private readonly AbstractSchemaManager $manager;

    /**
     * Constructor.
     */
    public function __construct(EntityManagerInterface $manager)
    {
        $this->manager = $manager->getConnection()->createSchemaManager();
    }

    #[Route(path: '', name: 'schema')]
    public function index(): Response
    {
        return $this->renderForm('schema/index.html.twig', [
            'tables' => $this->getTables(),
        ]);
    }

    /**
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException if the table can not be found
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
        ]);
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
            ];
        }, $table->getColumns());
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
     */
    private function getPrimaryKeys(Table $table): array
    {
        if ($table->hasPrimaryKey()) {
            return \array_map(static fn (Column $c): string => $c->getName(), $table->getPrimaryKeyColumns());
        }

        return [];
    }

    /**
     * Gets the tables.
     *
     * @return array<array{
     *      name: string,
     *      count: int}>
     */
    private function getTables(): array
    {
        $tables = $this->getManager()->listTables();

        \usort($tables, fn (Table $a, Table $b): int => \strnatcmp($a->getName(), $b->getName()));

        return \array_map(static function (Table $table): array {
            return [
                'name' => $table->getName(),
                'count' => \count($table->getColumns()),
             ];
        }, $tables);
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

    private function tableExist(string $name): bool
    {
        return $this->getManager()->tablesExist([$name]);
    }
}
