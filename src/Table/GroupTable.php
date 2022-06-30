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

namespace App\Table;

use App\Entity\Category;
use App\Entity\Group;
use App\Repository\GroupRepository;
use App\Traits\CheckerTrait;
use App\Util\FileUtils;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Twig\Environment;

/**
 * The groups table.
 *
 * @template-extends AbstractEntityTable<Group>
 */
class GroupTable extends AbstractEntityTable
{
    use CheckerTrait;

    /**
     * Constructor.
     */
    public function __construct(
        GroupRepository $repository,
        AuthorizationCheckerInterface $checker,
        private readonly Environment $twig
    ) {
        parent::__construct($repository);
        $this->checker = $checker;
    }

    /**
     * Formatter for the category column.
     *
     * @throws \Twig\Error\Error
     */
    public function formatCategories(\Countable $categories, Group $group): string
    {
        $context = [
            'count' => $categories->count(),
            'title' => 'group.list.category_title',
            'route' => $this->isGrantedList(Category::class) ? 'category_table' : false,
            'parameters' => [
                CategoryTable::PARAM_GROUP => $group->getId(),
            ],
        ];

        return $this->twig->render('macros/_cell_table_link.html.twig', $context);
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
