<?php
/*
 * This file is part of the Calculation package.
 *
 * (c) bibi.nu. <bibi@bibi.nu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace App\Controller;

use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\ForeignKeyConstraint;
use Doctrine\DBAL\Schema\Table;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Controller to display the database schema.
 *
 * @author Laurent Muller
 *
 * @Route("/schema")
 * @IsGranted("ROLE_SUPER_ADMIN")
 */
class SchemaController extends AbstractController
{
    private EntityManagerInterface $manager;

    /**
     * Constructor.
     */
    public function __construct(EntityManagerInterface $manager)
    {
        $this->manager = $manager;
    }

    /**
     * @Route("", name="schema")
     */
    public function index(): Response
    {
        $tables = $this->getTables();

        return $this->renderForm('schema/index.html.twig', [
            'tables' => $tables,
        ]);
    }

    /**
     * @Route("/{name}", name="schema_table")
     */
    public function table(Request $request, string $name): Response
    {
        $columns = $this->getColumns($name);
        $primaryKey = $this->getPrimaryKey($name);

        return $this->renderForm('schema/table.html.twig', [
            'name' => $name,
            'columns' => $columns,
            'primaryKey' => $primaryKey,
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
     */
    private function getColumns(string $name, bool $sort = false): array
    {
        $manager = $this->getSchemaManager();
        $columns = $manager->listTableColumns($name);
        $foreignKeys = $manager->listTableForeignKeys($name);

        if ($sort) {
            \usort($columns, function (Column $a, Column $b): int {
                return \strnatcmp($a->getName(), $b->getName());
            });
        }

        return \array_map(function (Column $column) use ($foreignKeys): array {
            $name = $column->getName();
            $foreignTableName = $this->findForeignTableName($name, $foreignKeys);

            return [
                'name' => $name,
                'type' => $column->getType()->getName(),
                'length' => (int) $column->getLength(),
                'null' => !$column->getNotnull(),
                'foreignTableName' => $foreignTableName,
            ];
        }, $columns);
    }

    private function getPrimaryKey(string $name): ?string
    {
        $manager = $this->getSchemaManager();
        $table = $manager->listTableDetails($name);
        if ($table->hasPrimaryKey()) {
            $columns = $table->getPrimaryKeyColumns();
            if (!empty($columns)) {
                $key = \array_key_first($columns);

                return $columns[$key]->getName();
            }
        }

        return null;
    }

    /**
     * @return AbstractSchemaManager<\Doctrine\DBAL\Platforms\AbstractPlatform>
     */
    private function getSchemaManager(): AbstractSchemaManager
    {
        return $this->manager->getConnection()
            ->createSchemaManager();
    }

    /**
     * Gets the tables.
     */
    private function getTables(bool $sort = true): array
    {
        $manager = $this->getSchemaManager();
        $tables = $manager->listTables();

        if ($sort) {
            \usort($tables, function (Table $a, Table $b): int {
                return \strnatcmp($a->getName(), $b->getName());
            });
        }

        return \array_map(function (Table $table): array {
            return [
                'name' => $table->getName(),
                 'columns' => \count($table->getColumns()),
             ];
        }, $tables);
    }
}
