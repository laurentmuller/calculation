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

namespace App\Report;

use App\Controller\AbstractController;

/**
 * Report for calculations with duplicate items.
 *
 * @author Laurent Muller
 */
class CalculationDuplicateReport extends CalculationItemsReport
{
    /**
     * Constructor.
     *
     * @param AbstractController $controller the parent controller
     * @param array              $items      the items to render
     */
    public function __construct(AbstractController $controller, array $items)
    {
        parent::__construct($controller, $items, 'duplicate.title', 'duplicate.description');
    }

    /**
     * {@inheritdoc}
     */
    protected function computeItemsCount(array $items): int
    {
        return \array_reduce($items, function (int $carry, array $item) {
            foreach ($item['items'] as $child) {
                $carry += $child['count'];
            }

            return $carry;
        }, 0);
    }

    /**
     * {@inheritdoc}
     */
    protected function formatItems(array $items): string
    {
        $result = \array_map(function (array $item) {
            return \sprintf('%s (%d)', $item['description'], $item['count']);
        }, $items);

        return \implode("\n", $result);
    }

    /**
     * {@inheritdoc}
     */
    protected function transCount(array $parameters): string
    {
        return $this->trans('duplicate.count', $parameters);
    }
}
