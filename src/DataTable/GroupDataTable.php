<?php
/*
 * This file is part of the Calculation package.
 *
 * Copyright (c) 2019 bibi.nu. All rights reserved.
 *
 * This computer code is protected by copyright law and international
 * treaties. Unauthorised reproduction or distribution of this code, or
 * any portion of it, may result in severe civil and criminal penalties,
 * and will be prosecuted to the maximum extent possible under the law.
 */

declare(strict_types=1);

namespace App\DataTable;

use App\DataTable\Model\AbstractEntityDataTable;
use App\DataTable\Model\DataColumn;
use App\DataTable\Model\DataColumnFactory;
use App\Entity\Category;
use App\Repository\AbstractRepository;
use App\Repository\CategoryRepository;
use App\Util\FormatUtils;
use DataTables\DataTablesInterface;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Twig\Environment;

/**
 * Parent category (group) data table handler.
 *
 * @author Laurent Muller
 */
class GroupDataTable extends AbstractEntityDataTable
{
    /**
     * The datatable identifier.
     */
    public const ID = Category::class . '.group';

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
     * Creates the link to catégories.
     *
     * @param Collection|Category[] $categories the list of categories that fall into the given parent category
     *
     * @return string the link, if applicable, the value otherwise
     */
    public function categoriesFormatter(Collection $categories): string
    {
        return FormatUtils::formatInt(\count($categories));
    }

    /**
     * The margins formatter.
     *
     * @param Collection $margins the margins to format
     *
     * @return string the formatted margins
     */
    public function maginsFormatter(Collection $margins): string
    {
        return FormatUtils::formatInt(\count($margins));
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
    protected function createQueryBuilder($alias = AbstractRepository::DEFAULT_ALIAS): QueryBuilder
    {
        $predicate = CategoryRepository::getGroupPredicate($alias);

        return parent::createQueryBuilder($alias)
            ->where($predicate);
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultOrder(): array
    {
        return ['code' => DataColumn::SORT_ASC];
    }
}
