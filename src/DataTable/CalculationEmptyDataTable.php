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

namespace App\DataTable;

use App\Repository\CalculationRepository;
use App\Traits\MathTrait;
use DataTables\DataTablesInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

/**
 * Data table handler for calculations with empty items (price or quantity).
 *
 * @author Laurent Muller
 */
class CalculationEmptyDataTable extends AbstractCalculationItemsDataTable
{
    use MathTrait;

    /**
     * The datatable identifier.
     */
    public const ID = 'Calculation.empty';

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
     * @param SessionInterface      $session     the session to save/retrieve user parameters
     * @param DataTablesInterface   $datatables  the datatables to handle request
     * @param CalculationRepository $repository  the repository to get entities
     * @param Environment           $environment the Twig environment to render actions cells
     * @param TranslatorInterface   $translator  the service to translate messages
     */
    public function __construct(SessionInterface $session, DataTablesInterface $datatables, CalculationRepository $repository, Environment $environment, TranslatorInterface $translator)
    {
        parent::__construct($session, $datatables, $repository, $environment);
        $this->priceLabel = $translator->trans('calculationitem.fields.price');
        $this->quantityLabel = $translator->trans('calculationitem.fields.quantity');
    }

    /**
     * {@inheritdoc}
     */
    public function formatItems(array $items): string
    {
        $result = \array_map(function (array $item) {
            $founds = [];
            if ($this->isFloatZero($item['price'])) {
                $founds[] = $this->priceLabel;
            }
            if ($this->isFloatZero($item['quantity'])) {
                $founds[] = $this->quantityLabel;
            }

            return \sprintf('%s (%s)', $item['description'], \implode(', ', $founds));
        }, $items);

        return \implode('<br>', $result);
    }

    /**
     * {@inheritdoc}
     */
    protected function getItems(CalculationRepository $repository, string $orderColumn, string $orderDirection): array
    {
        return $repository->getEmptyItems($orderColumn, $orderDirection);
    }

    /**
     * {@inheritdoc}
     */
    protected function getItemsCount(array $items): int
    {
        return \array_reduce($items, function (int $carry, array $item) {
            return $carry + \count($item['items']);
        }, 0);
    }
}
