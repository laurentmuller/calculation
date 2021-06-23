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

namespace App\DataTable;

use App\DataTable\Model\AbstractEntityDataTable;
use App\DataTable\Model\DataColumnFactory;
use App\Entity\Task;
use App\Repository\TaskRepository;
use DataTables\DataTablesInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Environment;

/**
 * Task data table handler.
 *
 * @author Laurent Muller
 *
 * @template-extends AbstractEntityDataTable<Task>
 */
class TaskDataTable extends AbstractEntityDataTable
{
    /**
     * The datatable identifier.
     */
    public const ID = Task::class;

    /**
     * Constructor.
     */
    public function __construct(RequestStack $requestStack, DataTablesInterface $datatables, TaskRepository $repository, Environment $environment)
    {
        parent::__construct($requestStack, $datatables, $repository, $environment);
    }

    /**
     * {@inheritdoc}
     */
    protected function createColumns(): array
    {
        $path = __DIR__ . '/Definition/task.json';

        return DataColumnFactory::fromJson($this, $path);
    }

    /**
     * {@inheritdoc}
     */
    protected function createSearchExpression(string $field, string $parameter)
    {
        switch ($field) {
            case 'c.id':
                return "{$field} = :{$parameter}";
            default:
                return parent::createSearchExpression($field, $parameter);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function createSearchParameterValue(string $field, string $value)
    {
        switch ($field) {
            case 'c.id':
                return (int) $value;
            default:
                return parent::createSearchParameterValue($field, $value);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultOrder(): array
    {
        return ['name' => self::SORT_ASC];
    }
}
