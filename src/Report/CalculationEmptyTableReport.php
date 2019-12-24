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

namespace App\Report;

use App\Controller\BaseController;

/**
 * Report for calculations with empty items.
 *
 * @author Laurent Muller
 */
class CalculationEmptyTableReport extends CalculationItemsTableReport
{
    /**
     * The price label.
     *
     * @var string
     */
    private $priceLabel;

    /**
     * The quantity label.
     *
     * @var string
     */
    private $quantityLabel;

    /**
     * Constructor.
     *
     * @param BaseController $controller the parent controller
     */
    public function __construct(BaseController $controller)
    {
        parent::__construct($controller, 'empty.title', 'empty.description');
        $this->priceLabel = $this->trans('calculationitem.fields.price');
        $this->quantityLabel = $this->trans('calculationitem.fields.quantity');
    }

    /**
     * {@inheritdoc}
     */
    protected function computeItemsCount(array $items): int
    {
        return \array_reduce($items, function (int $carry, array $item) {
            return $carry + \count($item['items']);
        }, 0);
    }

    /**
     * {@inheritdoc}
     */
    protected function formatItems(array $items): string
    {
        $result = \array_map(function (array $item) {
            $founds = [];
            if (0.0 === $item['price']) {
                $founds[] = $this->priceLabel;
            }
            if (0.0 === $item['quantity']) {
                $founds[] = $this->quantityLabel;
            }

            return \sprintf('%s (%s)', $item['description'], \implode(', ', $founds));
        }, $items);

        return \implode("\n", $result);
    }

    /**
     * {@inheritdoc}
     */
    protected function transCount(array $parameters): string
    {
        return $this->trans('empty.count', $parameters);
    }
}
