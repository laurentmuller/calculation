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

use App\Repository\CalculationRepository;
use App\Service\ApplicationService;
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
class CalculationEmptyDataTable extends CalculationItemsDataTable
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
     * @param ApplicationService    $application the application to get parameters
     * @param SessionInterface      $session     the session to save/retrieve user parameters
     * @param DataTablesInterface   $datatables  the datatables to handle request
     * @param CalculationRepository $repository  the repository to get entities
     * @param Environment           $environment the Twig environment to render actions cells
     * @param TranslatorInterface   $translator  the service to translate messages
     */
    public function __construct(ApplicationService $application, SessionInterface $session, DataTablesInterface $datatables, CalculationRepository $repository, Environment $environment, TranslatorInterface $translator)
    {
        parent::__construct($application, $session, $datatables, $repository, $environment);
        $this->priceLabel = $translator->trans('calculationitem.fields.price');
        $this->quantityLabel = $translator->trans('calculationitem.fields.quantity');
    }

    /**
     * {@inheritdoc}
     */
    public function itemsFormatter(array $items): string
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
