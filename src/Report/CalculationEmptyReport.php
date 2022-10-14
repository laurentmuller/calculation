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
use App\Traits\EmptyItemsTrait;
use App\Traits\MathTrait;

/**
 * Report for calculations with empty items.
 */
class CalculationEmptyReport extends AbstractCalculationItemsReport
{
    use EmptyItemsTrait;
    use MathTrait;

    /**
     * The price label.
     */
    private readonly string $priceLabel;

    /**
     * The quantity label.
     */
    private readonly string $quantityLabel;

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
     *
     * @throws \Psr\Cache\InvalidArgumentException
     * @throws \Psr\Container\ContainerExceptionInterface
     */
    public function __construct(AbstractController $controller, array $items)
    {
        parent::__construct($controller, $items, 'empty.title', 'empty.description');
        $this->priceLabel = $this->trans('calculationitem.fields.price');
        $this->quantityLabel = $this->trans('calculationitem.fields.quantity');
    }

    /**
     * {@inheritdoc}
     */
    protected function computeItemsCount(array $items): int
    {
        return \array_reduce($items, fn (int $carry, array $item) => $carry + \count((array) $item['items']), 0);
    }

    protected function getPriceLabel(): string
    {
        return $this->priceLabel;
    }

    protected function getQuantityLabel(): string
    {
        return $this->quantityLabel;
    }

    /**
     * {@inheritdoc}
     */
    protected function transCount(array $parameters): string
    {
        return $this->trans('empty.count', $parameters);
    }
}
