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

namespace App\Report;

use App\Controller\AbstractController;
use App\Traits\DuplicateItemsTrait;

/**
 * Report for calculations with duplicate items.
 */
class CalculationDuplicateReport extends AbstractCalculationItemsReport
{
    use DuplicateItemsTrait;

    /**
     * Constructor.
     *
     * @psalm-param array<int, array{
     *      id: int,
     *      date: \DateTimeInterface,
     *      stateCode: string,
     *      customer: string,
     *      description: string,
     *      items: array<array{
     *          description: string,
     *          quantity: float,
     *          price: float,
     *          count: int}>
     *      }> $items
     */
    public function __construct(AbstractController $controller, array $items)
    {
        parent::__construct($controller, $items, 'duplicate.title', 'duplicate.description');
    }

    protected function computeItemsCount(array $items): int
    {
        return \array_reduce($items, function (int $carry, array $item) {
            /** @var array $child */
            foreach ($item['items'] as $child) {
                $carry += (int) $child['count'];
            }

            return $carry;
        }, 0);
    }

    protected function transCount(array $parameters): string
    {
        return $this->trans('duplicate.count', $parameters);
    }
}
