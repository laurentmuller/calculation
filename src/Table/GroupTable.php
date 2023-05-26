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
use App\Repository\AbstractRepository;
use App\Repository\GroupRepository;
use App\Traits\AuthorizationCheckerAwareTrait;
use App\Utils\FileUtils;
use Doctrine\ORM\QueryBuilder;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Symfony\Contracts\Service\ServiceSubscriberTrait;
use Twig\Environment;

/**
 * The groups table.
 *
 * @method GroupRepository getRepository()
 *
 * @template-extends AbstractEntityTable<Group>
 */
class GroupTable extends AbstractEntityTable implements ServiceSubscriberInterface
{
    use AuthorizationCheckerAwareTrait;
    use ServiceSubscriberTrait;

    /**
     * Constructor.
     */
    public function __construct(GroupRepository $repository, private readonly Environment $twig)
    {
        parent::__construct($repository);
    }

    /**
     * Formatter for the category column.
     *
     * @throws \Twig\Error\Error
     *
     * @psalm-param Group|array{id: int} $group
     */
    public function formatCategories(\Countable|int $categories, Group|array $group): string
    {
        $id = \is_array($group) ? $group['id'] : $group->getId();
        $count = $categories instanceof \Countable ? $categories->count() : $categories;
        $context = [
            'count' => $count,
            'title' => 'group.list.category_title',
            'route' => $this->isGrantedList(Category::class) ? 'category_table' : false,
            'parameters' => [
                CategoryTable::PARAM_GROUP => $id,
            ],
        ];

        return $this->twig->render('macros/_cell_table_link.html.twig', $context);
    }

    protected function createDefaultQueryBuilder(string $alias = AbstractRepository::DEFAULT_ALIAS): QueryBuilder
    {
        return $this->getRepository()->getTableQueryBuilder($alias);
    }

    protected function getColumnDefinitions(): string
    {
        return FileUtils::buildPath(__DIR__, 'Definition', 'group.json');
    }

    protected function getDefaultOrder(): array
    {
        return ['code' => self::SORT_ASC];
    }
}
