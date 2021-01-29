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
use App\Repository\CategoryRepository;
use App\Repository\GroupRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpFoundation\Request;

/**
 * The categories table.
 *
 * @author Laurent Muller
 */
class CategoryTable extends AbstractEntityTable
{
    /**
     * The group parameter name.
     */
    private const PARAM_GROUP = 'groupId';

    /**
     * The selected group identifier.
     */
    private int $groupId = 0;

    /**
     * Constructor.
     */
    public function __construct(CategoryRepository $repository)
    {
        parent::__construct($repository);
    }

    /**
     * Gets the selected group or null if none.
     */
    public function getGroup(GroupRepository $repository): ?Group
    {
        if (0 !== $this->groupId) {
            return $repository->find($this->groupId);
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    protected function addSearch(Request $request, QueryBuilder $builder): string
    {
        $search = parent::addSearch($request, $builder);

        // category?
        $this->groupId = (int) $request->get(self::PARAM_GROUP, 0);
        if (0 !== $this->groupId) {
            $field = $this->repository->getSearchFields('group.id');
            $builder->andWhere($field . '=:' . self::PARAM_GROUP)
                ->setParameter(self::PARAM_GROUP, $this->groupId, Types::INTEGER);
        }

        return $search;
    }

    /**
     * {@inheritdoc}
     */
    protected function getColumnDefinitions(): string
    {
        return __DIR__ . '/Definition/category.json';
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultOrder(): array
    {
        return ['code' => Column::SORT_ASC];
    }
}
