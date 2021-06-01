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
use App\Entity\Group;
use App\Repository\GroupRepository;
use DataTables\DataTablesInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Environment;

/**
 * Parent category (group) data table handler.
 *
 * @author Laurent Muller
 *
 * @template-extends AbstractEntityDataTable<Group>
 */
class GroupDataTable extends AbstractEntityDataTable
{
    /**
     * The datatable identifier.
     */
    public const ID = Group::class;

    /**
     * Constructor.
     */
    public function __construct(RequestStack $requestStack, DataTablesInterface $datatables, GroupRepository $repository, Environment $environment)
    {
        parent::__construct($requestStack, $datatables, $repository, $environment);
    }

    /**
     * Creates the cell link to categories.
     */
    public function formatCategories(\Countable $categories, Group $item): string
    {
        $context = [
            'id' => $item->getId(),
            'code' => $item->getCode(),
            'count' => \count($categories),
        ];

        return $this->renderTemplate('group/group_cell_category.html.twig', $context);
    }

    /**
     * {@inheritdoc}
     */
    protected function createColumns(): array
    {
        $path = __DIR__ . '/Definition/group.json';

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
