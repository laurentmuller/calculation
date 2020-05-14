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
class CalculationEmptyReport extends CalculationItemsReports
{
    /**
     * Constructor.
     *
     * @param BaseController $controller the parent controller
     */
    public function __construct(BaseController $controller)
    {
        parent::__construct($controller, 'calculation.empty.title', 'calculation.empty.description');
    }

    /**
     * {@inheritdoc}
     */
    protected function countItems(array $calculations): int
    {
        return \array_reduce($calculations, function (int $carry, array $calculation) {
            return $carry + \count($calculation['items']);
        }, 0);
    }

    /**
     * {@inheritdoc}
     */
    protected function formatItem(array $item): string
    {
        $texts = [];
        if (empty($item['price'])) {
            $texts[] = $this->trans('calculationitem.fields.price');
        }
        if (empty($item['quantity'])) {
            $texts[] = $this->trans('calculationitem.fields.quantity');
        }

        return \sprintf('%s (%s)', $item['description'], \implode(', ', $texts));
    }

    /**
     * {@inheritdoc}
     */
    protected function transCount(array $parameters): string
    {
        return $this->trans('calculation.empty.count', $parameters);
    }
}
