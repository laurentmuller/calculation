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

namespace App\Table;

use App\Entity\Calculation;
use App\Entity\CalculationState;
use App\Interfaces\SortModeInterface;
use App\Repository\CalculationRepository;
use App\Repository\CalculationStateRepository;
use App\Util\FileUtils;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpFoundation\Request;
use Twig\Environment;

/**
 * The calculations table.
 *
 * @author Laurent Muller
 * @template-extends AbstractEntityTable<Calculation>
 */
class CalculationTable extends AbstractEntityTable
{
    /**
     * The state parameter name (int).
     */
    final public const PARAM_STATE = 'stateid';

    /**
     * Constructor.
     */
    public function __construct(CalculationRepository $repository, protected readonly CalculationStateRepository $stateRepository, protected readonly Environment $twig)
    {
        parent::__construct($repository);
    }

    /**
     * Render the overall margin column.
     */
    public function formatOverallMargin(float $margin, Calculation $entity): string
    {
        return $this->twig->render('macros/_cell_calculation_margin.html.twig', [
            'empty' => $entity->isEmpty(),
            'margin' => $margin,
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function getDataQuery(Request $request): DataQuery
    {
        $query = parent::getDataQuery($request);
        $stateId = (int) $this->getRequestValue($request, self::PARAM_STATE, 0, false);
        $query->addCustomData(self::PARAM_STATE, $stateId);

        return $query;
    }

    /**
     * Gets calculation states.
     */
    protected function getCalculationStates(): array
    {
        return $this->stateRepository->getDropDownStates();
    }

    /**
     * {@inheritdoc}
     */
    protected function getColumnDefinitions(): string
    {
        return FileUtils::buildPath(__DIR__, 'Definition', 'calculation.json');
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultOrder(): array
    {
        return ['id' => SortModeInterface::SORT_DESC];
    }

    /**
     * {@inheritDoc}
     */
    protected function search(DataQuery $query, QueryBuilder $builder): void
    {
        parent::search($query, $builder);
        if (0 !== $stateId = (int) $query->getCustomData(self::PARAM_STATE, 0)) {
            /** @var string $field */
            $field = $this->repository->getSearchFields('state.id');
            $builder->andWhere($field . '=:' . self::PARAM_STATE)
                ->setParameter(self::PARAM_STATE, $stateId, Types::INTEGER);
        }
    }

    /**
     * {@inheritDoc}
     */
    protected function updateResults(DataQuery $query, DataResults &$results): void
    {
        parent::updateResults($query, $results);
        if (!$query->callback) {
            $results->addAttribute('row-style', 'styleTextMuted');
            $stateId = (int) $query->getCustomData(self::PARAM_STATE, 0);
            $results->addCustomData('state', $this->getCalculationState($stateId));
            $results->addCustomData('states', $this->getCalculationStates());
            $results->addParameter(self::PARAM_STATE, $stateId);
        }
    }

    /**
     * Gets the calculation state for the given identifier.
     */
    private function getCalculationState(int $stateId): ?CalculationState
    {
        return 0 !== $stateId ? $this->stateRepository->find($stateId) : null;
    }
}
