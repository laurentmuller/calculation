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

use App\DataTable\Model\AbstractEntityDataTable;
use App\DataTable\Model\DataColumn;
use App\DataTable\Model\DataColumnFactory;
use App\Entity\GlobalMargin;
use App\Repository\GlobalMarginRepository;
use App\Util\FormatUtils;
use DataTables\DataTablesInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Twig\Environment;

/**
 * GlobalMargin data table handler.
 *
 * @author Laurent Muller
 */
class GlobalMarginDataTable extends AbstractEntityDataTable
{
    /**
     * The datatable identifier.
     */
    public const ID = GlobalMargin::class;

    /**
     * Constructor.
     *
     * @param SessionInterface       $session     the session to save/retrieve user parameters
     * @param DataTablesInterface    $datatables  the datatables to handle request
     * @param GlobalMarginRepository $repository  the repository to get entities
     * @param Environment            $environment the Twig environment to render actions cells
     */
    public function __construct(SessionInterface $session, DataTablesInterface $datatables, GlobalMarginRepository $repository, Environment $environment)
    {
        parent::__construct($session, $datatables, $repository, $environment);
    }

    /**
     * Format the amount.
     *
     * @param float $value the amount to format
     *
     * @return string the formatted amount
     */
    public function amountFormatter(float $value): string
    {
        return FormatUtils::formatAmount($value);
    }

    /**
     * Formats the given value as percent.
     *
     * @param float $number the value to format
     *
     * @return string the formatted value
     */
    public function percentFormatter(float $number): string
    {
        return FormatUtils::formatPercent($number);
    }

    /**
     * {@inheritdoc}
     */
    protected function createColumns(): array
    {
        $path = __DIR__ . '/Definition/global_margin.json';

        return DataColumnFactory::fromJson($this, $path);
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultOrder(): array
    {
        return ['minimum' => DataColumn::SORT_ASC];
    }
}
