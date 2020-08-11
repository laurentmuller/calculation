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
use App\Entity\Category;
use App\Repository\CategoryRepository;
use App\Service\ApplicationService;
use DataTables\DataTablesInterface;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Twig\Environment;

/**
 * Category data table handler.
 *
 * @author Laurent Muller
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
     * @param ApplicationService  $application the application to get parameters
     * @param SessionInterface    $session     the session to save/retrieve user parameters
     * @param DataTablesInterface $datatables  the datatables to handle request
     * @param CategoryRepository  $repository  the repository to get entities
     * @param Environment         $environment the Twig environment to render cells
     */
    public function __construct(ApplicationService $application, SessionInterface $session, DataTablesInterface $datatables, CategoryRepository $repository, Environment $environment)
    {
        parent::__construct($application, $session, $datatables, $repository);
        $this->environment = $environment;
    }

    /**
     * Creates the link to prodcuts.
     *
     * @param Collection $products the list of products that fall into the given category
     * @param Category   $item     the category
     *
     * @return string the link, if applicable, the value otherwise
     */
    public function linkProducts(Collection $products, Category $item): string
    {
        $parameters = [
            'id' => $item->getId(),
            'code' => $item->getCode(),
            'count' => \count($products),
        ];

        return $this->environment->render('category/category_product_cell.html.twig', $parameters);
    }

    /**
     * {@inheritdoc}
     */
    protected function createColumns(): array
    {
        // callbacks
        $intFormatter = function (Collection $margins): string {
            return $this->localeInt(\count($margins));
        };

        return [
            DataColumn::hidden('id'),
            DataColumn::instance('code')
                ->setTitle('category.fields.code')
                ->setClassName('text-code text-nowrap')
                ->setDefault(true),
            DataColumn::instance('description')
                ->setTitle('category.fields.description')
                ->setClassName('w-50 cell'),
            DataColumn::currency('margins')
                ->setTitle('category.fields.margins')
                ->setSearchable(false)
                ->setOrderable(false)
                ->setFormatter($intFormatter),
            DataColumn::currency('products')
                ->setTitle('category.fields.products')
                ->setSearchable(false)
                ->setOrderable(false)
                ->setRawData(true)
                ->setFormatter([$this, 'linkProducts']),
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultOrder(): array
    {
        return ['code' => DataColumn::SORT_ASC];
    }
}
