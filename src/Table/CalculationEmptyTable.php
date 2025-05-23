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
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Calculation table for empty items.
 *
 * @phpstan-import-type CalculationItemType from CalculationRepository
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

    public function __construct(CalculationRepository $repository, TranslatorInterface $translator)
    {
        parent::__construct($repository);
        $this->priceLabel = $translator->trans('calculationitem.fields.price');
        $this->quantityLabel = $translator->trans('calculationitem.fields.quantity');
    }

    /**
     * @return int<0, max>
     */
    #[\Override]
    public function count(): int
    {
        return $this->repository->countItemsEmpty();
    }

    #[\Override]
    public function getEmptyMessage(): ?string
    {
        return 0 === $this->count() ? 'empty.empty' : null;
    }

    /**
     * @phpstan-param self::SORT_* $orderDirection
     *
     * @phpstan-return CalculationItemType[]
     */
    #[\Override]
    protected function getEntities(string $orderColumn = 'id', string $orderDirection = self::SORT_DESC): array
    {
        return $this->repository->getItemsEmpty($orderColumn, $orderDirection);
    }

    #[\Override]
    protected function getItemsCount(array $items): int
    {
        return \array_reduce(
            $items,
            /** @phpstan-param CalculationItemType $item */
            fn (int $carry, array $item): int => $carry + \count($item['items']),
            0
        );
    }

    /**
     * Gets the separator used to implode items.
     */
    protected function getItemsSeparator(): string
    {
        return '<br>';
    }

    #[\Override]
    protected function getPriceLabel(): string
    {
        return $this->priceLabel;
    }

    #[\Override]
    protected function getQuantityLabel(): string
    {
        return $this->quantityLabel;
    }
}
