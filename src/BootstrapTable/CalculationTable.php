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

namespace App\BootstrapTable;

use App\Entity\CalculationState;
use App\Repository\CalculationRepository;
use App\Repository\CalculationStateRepository;
use App\Util\FormatUtils;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpFoundation\Request;

/**
 * The calculations table.
 *
 * @author Laurent Muller
 */
class CalculationTable extends AbstractBootstrapEntityTable
{
    /**
     * The state parameter name.
     */
    private const PARAM_STATE = 'stateId';

    /**
     * The selected state identifier.
     */
    private int $stateId = 0;

    /**
     * Constructor.
     */
    public function __construct(CalculationRepository $repository)
    {
        parent::__construct($repository);
    }

    public function formatId(int $value): string
    {
        return FormatUtils::formatId($value);
    }

    /**
     * Gets the selected calculation state or null if none.
     */
    public function getCalculationState(CalculationStateRepository $repository): ?CalculationState
    {
        if (0 !== $this->stateId) {
            return $repository->find($this->stateId);
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    protected function addSearch(Request $request, QueryBuilder $builder): string
    {
        $search = parent::addSearch($request, $builder);

        // state?
        $this->stateId = (int) $request->get(self::PARAM_STATE, 0);
        if (0 !== $this->stateId) {
            $field = $this->repository->getSearchFields('state.id');
            $builder->andWhere($field . '=:' . self::PARAM_STATE)
                ->setParameter(self::PARAM_STATE, $this->stateId, Types::INTEGER);
        }

        return $search;
    }

    /**
     * {@inheritdoc}
     */
    protected function createColumns(): array
    {
        $path = __DIR__ . '/Definition/calculation.json';

        return $this->deserializeColumns($path);
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultOrder(): array
    {
        return ['id' => BootstrapColumn::SORT_DESC];
    }
}
