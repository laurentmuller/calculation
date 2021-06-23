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
use App\Entity\Category;
use App\Repository\CategoryRepository;
use DataTables\DataTablesInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Environment;

/**
 * Category data table handler.
 *
 * @author Laurent Muller
 *
 * @template-extends AbstractEntityDataTable<Category>
 */
class CategoryDataTable extends AbstractEntityDataTable
{
    /**
     * The datatable identifier.
     */
    public const ID = Category::class;

    /**
     * Constructor.
     */
    public function __construct(RequestStack $requestStack, DataTablesInterface $datatables, CategoryRepository $repository, Environment $environment)
    {
        parent::__construct($requestStack, $datatables, $repository, $environment);
    }

    /**
     * Creates the cell link to products.
     */
    public function formatProducts(\Countable $products, Category $item): string
    {
        $context = [
            'id' => $item->getId(),
            'code' => $item->getCode(),
            'count' => \count($products),
        ];

        return $this->renderTemplate('category/category_cell_product.html.twig', $context);
    }

    /**
     * Creates the cell link to tasks.
     */
    public function formatTasks(\Countable $tasks, Category $item): string
    {
        $context = [
            'id' => $item->getId(),
            'code' => $item->getCode(),
            'count' => \count($tasks),
        ];

        return $this->renderTemplate('category/category_cell_task.html.twig', $context);
    }

    /**
     * {@inheritdoc}
     */
    protected function createColumns(): array
    {
        $path = __DIR__ . '/Definition/category.json';

        return DataColumnFactory::fromJson($this, $path);
    }

    /**
     * {@inheritdoc}
     */
    protected function createSearchExpression(string $field, string $parameter)
    {
        switch ($field) {
            case 'g.id':
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
            case 'g.id':
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
        return ['code' => self::SORT_ASC];
    }
}
