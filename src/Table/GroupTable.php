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

namespace App\Table;

use App\Entity\Group;
use App\Repository\GroupRepository;
use App\Util\FileUtils;
use Twig\Environment;

/**
 * The groups table.
 *
 * @author Laurent Muller
 * @template-extends AbstractEntityTable<Group>
 */
class GroupTable extends AbstractEntityTable
{
    /**
     * Constructor.
     */
    public function __construct(GroupRepository $repository, private readonly Environment $twig)
    {
        parent::__construct($repository);
    }

    /**
     * Formatter for the category's column.
     */
    public function formatCategories(\Countable $categories, Group $group): string
    {
        return $this->twig->render('macros/_cell_table_link.html.twig', [
            'route' => 'category_table',
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
        return FileUtils::buildPath(__DIR__, 'Definition', 'group.json');
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultOrder(): array
    {
        return ['code' => self::SORT_ASC];
    }
}
