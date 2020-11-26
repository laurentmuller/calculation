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
use App\Entity\Product;
use App\Repository\CategoryRepository;
use App\Util\FormatUtils;
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
     * Creates the link to prodcuts.
     *
     * @param Collection|Product[] $products the list of products that fall into the given category
     * @param Category             $item     the category
     *
     * @return string the link, if applicable, the value otherwise
     */
    public function productsFormatter(Collection $products, Category $item): string
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
