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

namespace App\BootstrapTable;

use App\Entity\Group;
use App\Repository\GroupRepository;
use Twig\Environment;

/**
 * The goups table.
 *
 * @author Laurent Muller
 * @template-extends AbstractEntityTable<Group>
 */
class GroupTable extends AbstractEntityTable
{
    /**
     * The template renderer.
     */
    private Environment $twig;

    /**
     * Constructor.
     */
    public function __construct(GroupRepository $repository, Environment $twig)
    {
        parent::__construct($repository);
        $this->twig = $twig;
    }

    /**
     * Formatter for the categories column.
     */
    public function formatCategories(\Countable $categories, Group $group): string
    {
        return $this->twig->render('table/_cell_table_link.html.twig', [
            'route' => 'table_category',
            'count' => $categories->count(),
            'title' => 'group.list.category_title',
            'parameters' => [
                CategoryTable::PARAM_GROUP => $group->getId(),
            ],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    protected function getColumnDefinitions(): string
    {
        return __DIR__ . '/Definition/group.json';
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultOrder(): array
    {
        return ['code' => Column::SORT_ASC];
    }

    /**
     * {@inheritDoc}
     */
    protected function isCustomViewAllowed(): bool
    {
        return true;
    }
}
