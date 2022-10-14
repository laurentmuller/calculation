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

namespace App\Table;

use App\Repository\CalculationRepository;
use App\Traits\EmptyItemsTrait;
use Doctrine\Common\Collections\Criteria;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Calculation table for empty items.
 */
class CalculationEmptyTable extends AbstractCalculationItemsTable
{
    use EmptyItemsTrait;

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
     */
    public function __construct(CalculationRepository $repository, TranslatorInterface $translator)
    {
        parent::__construct($repository);
        $this->priceLabel = $translator->trans('calculationitem.fields.price');
        $this->quantityLabel = $translator->trans('calculationitem.fields.quantity');
    }

    /**
     * {@inheritDoc}
     *
     * @throws \Doctrine\ORM\Exception\ORMException
     */
    public function count(): int
    {
        return $this->repository->countEmptyItems();
    }

    /**
     * {@inheritDoc}
     */
    public function getEmptyMessage(): string
    {
        return 'empty.empty';
    }

    /**
     * {@inheritdoc}
     */
    protected function getEntities(string $orderColumn = 'id', string $orderDirection = Criteria::DESC): array
    {
        return $this->repository->getEmptyItems($orderColumn, $orderDirection);
    }

    /**
     * {@inheritdoc}
     */
    protected function getItemsCount(array $items): int
    {
        return \array_reduce($items, fn (int $carry, array $item) => $carry + \count((array) $item['items']), 0);
    }

    protected function getItemsSeparator(): string
    {
        return '<br>';
    }

    protected function getPriceLabel(): string
    {
        return $this->priceLabel;
    }

    protected function getQuantityLabel(): string
    {
        return $this->quantityLabel;
    }
}
