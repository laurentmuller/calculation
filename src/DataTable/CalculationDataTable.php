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
use App\Entity\Calculation;
use App\Repository\CalculationRepository;
use DataTables\DataTablesInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Environment;

/**
 * Calculation data table handler.
 *
 * @author Laurent Muller
 *
 * @template-extends AbstractEntityDataTable<Calculation>
 */
class CalculationDataTable extends AbstractEntityDataTable
{
    /**
     * The datatable identifier.
     */
    public const ID = Calculation::class;

    /**
     * Constructor.
     */
    public function __construct(RequestStack $requestStack, DataTablesInterface $datatables, CalculationRepository $repository, Environment $environment)
    {
        parent::__construct($requestStack, $datatables, $repository, $environment);
    }

    /**
     * {@inheritdoc}
     */
    protected function createColumns(): array
    {
        $path = __DIR__ . '/Definition/calculation.json';

        return DataColumnFactory::fromJson($this, $path);
    }

    /**
     * {@inheritdoc}
     */
    protected function createSearchExpression(string $field, string $parameter)
    {
        switch ($field) {
            case 's.id':
                return "{$field} = :{$parameter}";
            default:
                return parent::createSearchExpression($field, $parameter);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function createSearchParameterValue(string $field, string $value)
    {
        switch ($field) {
            case 's.id':
                return (int) $value;
            default:
                return parent::createSearchParameterValue($field, $value);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultOrder(): array
    {
        return ['id' => DataColumn::SORT_DESC];
    }
}
