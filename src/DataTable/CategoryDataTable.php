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
use App\DataTable\Model\DataColumn;
use App\DataTable\Model\DataColumnFactory;
use App\Entity\Category;
use App\Repository\CategoryRepository;
use DataTables\DataTablesInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
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
     *
     * @param SessionInterface    $session     the session to save/retrieve user parameters
     * @param DataTablesInterface $datatables  the datatables to handle request
     * @param CategoryRepository  $repository  the repository to get entities
     * @param Environment         $environment the Twig environment to render cells
     */
    public function __construct(SessionInterface $session, DataTablesInterface $datatables, CategoryRepository $repository, Environment $environment)
    {
        parent::__construct($session, $datatables, $repository, $environment);
    }

    /**
     * Creates the link to prodcuts.
     */
    public function formatProducts(\Countable $products, Category $item): string
    {
        $context = [
            'id' => $item->getId(),
            'code' => $item->getCode(),
            'count' => \count($products),
        ];

        return $this->renderTemplate('category/category_product_cell.html.twig', $context);
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
    protected function getDefaultOrder(): array
    {
        return ['code' => DataColumn::SORT_ASC];
    }
}
