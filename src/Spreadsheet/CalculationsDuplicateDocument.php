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

namespace App\Spreadsheet;

use App\Controller\AbstractController;

/**
 * Spreadsheet document for the list of calculations with duplicate items.
 *
 * @author Laurent Muller
 */
class CalculationsDuplicateDocument extends AbstractCalculationItemsDocument
{
    /**
     * Constructor.
     *
     * @psalm-param array<int, array{
     *      id: int,
     *      date: \DateTimeInterface,
     *      stateCode: string,
     *      customer: string,
     *      description: string,
     *      items: array{
     *          description: string,
     *          quantity: float,
     *          price: float,
     *          count: int}
     *      }> $entities
     */
    public function __construct(AbstractController $controller, array $entities)
    {
        parent::__construct($controller, $entities, 'duplicate.title');
    }

    /**
     * {@inheritdoc}
     */
    protected function formatItems(array $items): string
    {
        $result = \array_map(function (array $item): string {
            return \sprintf('%s (%d)', (string) $item['description'], (int) $item['count']);
        }, $items);

        return \implode("\n", $result);
    }
}
