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
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpFoundation\Request;
use Twig\Environment;

/**
 * The calculations table.
 *
 * @author Laurent Muller
 * @template-extends AbstractEntityTable<\App\Entity\Calculation>
 */
class CalculationTable extends AbstractEntityTable
{
    /**
     * The state parameter name (int).
     */
    public const PARAM_STATE = 'stateId';

    /**
     * The calculation state repository.
     */
    protected CalculationStateRepository $stateRepository;

    /**
     * The template renderer.
     */
    private Environment $twig;

    /**
     * Constructor.
     */
    public function __construct(CalculationRepository $repository, CalculationStateRepository $stateRepository, Environment $twig)
    {
        parent::__construct($repository);
        $this->stateRepository = $stateRepository;
        $this->twig = $twig;
    }

    /**
     * Formats the overall margin column.
     */
    public function formatOverallMargin(float $margin): string
    {
        return $this->twig->render('table/_cell_calculation_margin.html.twig', ['margin' => $margin]);
    }

    /**
     * {@inheritDoc}
     */
    public function getDataQuery(Request $request): DataQuery
    {
        $query = parent::getDataQuery($request);
        $stateId = (int) $request->get(self::PARAM_STATE, 0);
        $query->addCustomData(self::PARAM_STATE, $stateId);

        return $query;
    }

    /**
     * {@inheritdoc}
     */
    protected function getColumnDefinitions(): string
    {
        return __DIR__ . '/Definition/calculation.json';
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultOrder(): array
    {
        return ['id' => Column::SORT_DESC];
    }

    /**
     * {@inheritDoc}
     */
    protected function search(DataQuery $query, QueryBuilder $builder): void
    {
        parent::search($query, $builder);
        if (0 !== $stateId = $query->getCustomData(self::PARAM_STATE, 0)) {
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
            $stateId = $query->getCustomData(self::PARAM_STATE, 0);
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

    /**
     * Gets calculation states.
     *
     * @return CalculationState[]
     */
    private function getCalculationStates(): array
    {
        return $this->stateRepository->getListCount();
    }
}
