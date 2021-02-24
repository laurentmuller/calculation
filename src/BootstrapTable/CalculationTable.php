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
 */
class CalculationTable extends AbstractEntityTable
{
    /**
     * The state parameter name.
     */
    public const PARAM_STATE = 'stateId';

    /**
     * The calculation state repository.
     */
    protected CalculationStateRepository $stateRepository;

    /**
     * The selected state identifier.
     */
    private int $stateId = 0;

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
     * Gets calculation states.
     *
     * @return CalculationState[]
     */
    protected function getCalculationStates(): array
    {
        return $this->stateRepository->getListCount();
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
     * {@inheritdoc}
     */
    protected function updateParameters(array $parameters): array
    {
        return \array_merge_recursive(parent::updateParameters($parameters), [
            'state' => $this->getCalculationState(),
            'states' => $this->getCalculationStates(),
            'params' => [
                self::PARAM_STATE => $this->stateId,
            ],
            'attributes' => [
                'row-style' => 'styleCalculationEditable',
            ],
        ]);
    }

    /**
     * Gets the selected calculation state or null if none.
     */
    private function getCalculationState(): ?CalculationState
    {
        if (0 !== $this->stateId) {
            return $this->stateRepository->find($this->stateId);
        }

        return null;
    }
}
