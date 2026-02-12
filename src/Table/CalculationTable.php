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

use App\Entity\Calculation;
use App\Entity\CalculationState;
use App\Repository\AbstractRepository;
use App\Repository\CalculationRepository;
use App\Repository\CalculationStateRepository;
use App\Traits\MathTrait;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Filesystem\Path;
use Twig\Environment;

/**
 * The calculation table.
 *
 * @extends AbstractEntityTable<Calculation, CalculationRepository>
 */
class CalculationTable extends AbstractEntityTable
{
    use MathTrait;

    /** The editable state parameter name (bool). */
    public const string PARAM_EDITABLE = 'stateEditable';

    /** The state parameter name (int). */
    public const string PARAM_STATE = 'stateId';

    public function __construct(
        CalculationRepository $repository,
        protected readonly CalculationStateRepository $stateRepository,
        protected readonly Environment $twig
    ) {
        parent::__construct($repository);
    }

    /**
     * Render the overall margin column.
     *
     * @phpstan-param array{overallTotal: float} $entity
     *
     * @throws \Twig\Error\Error
     */
    public function formatOverallMargin(float $margin, array $entity): string
    {
        return $this->twig->render('macros/_cell_calculation_margin.html.twig', [
            'empty' => $this->isFloatZero($entity['overallTotal']),
            'margin' => $margin,
        ]);
    }

    #[\Override]
    protected function addSearch(DataQuery $query, QueryBuilder $builder, string $alias): bool
    {
        $repository = $this->getRepository();
        $result = parent::addSearch($query, $builder, $alias);
        $stateId = $this->getQueryStateId($query);
        if (0 !== $stateId) {
            /** @phpstan-var string $field */
            $field = $repository->getSearchFields('state.id', $alias);
            $builder->andWhere($field . '=:' . self::PARAM_STATE)
                ->setParameter(self::PARAM_STATE, $stateId, Types::INTEGER);

            return true;
        }

        $stateEditable = $this->getQueryEditable($query);
        if (0 !== $stateEditable) {
            /** @phpstan-var string $field */
            $field = $repository->getSearchFields('state.editable', $alias);
            $builder->andWhere($field . '=:' . self::PARAM_EDITABLE)
                ->setParameter(self::PARAM_EDITABLE, $this->isEditable($stateEditable), Types::BOOLEAN);

            return true;
        }

        return $result;
    }

    #[\Override]
    protected function createQueryBuilder(string $alias = AbstractRepository::DEFAULT_ALIAS): QueryBuilder
    {
        return $this->getRepository()->getTableQueryBuilder($alias);
    }

    #[\Override]
    protected function getColumnDefinitions(): string
    {
        return Path::join(__DIR__, 'Definition', 'calculation.json');
    }

    #[\Override]
    protected function getDefaultOrder(): array
    {
        return ['id' => self::SORT_DESC];
    }

    /**
     * Gets drop-down values.
     */
    protected function getDropDownValues(): array
    {
        return $this->stateRepository->getDropDown();
    }

    #[\Override]
    protected function updateResults(DataQuery $query, DataResults &$results): void
    {
        parent::updateResults($query, $results);
        if ($query->callback) {
            return;
        }
        $stateId = $this->getQueryStateId($query);
        $editable = $this->getQueryEditable($query);
        $results->addAttribute('row-style', 'styleTextMuted');
        $results->addParameter(self::PARAM_STATE, $stateId);
        $results->addParameter(self::PARAM_EDITABLE, $editable);
        $results->addCustomData('dropdown', $this->getDropDownValues());
        $results->addCustomData('state', $this->getCalculationState($stateId));
        $results->addCustomData('editable', $editable);
    }

    /**
     * Gets the calculation state for the given identifier.
     */
    private function getCalculationState(int $stateId): ?array
    {
        if (0 === $stateId) {
            return null;
        }
        $entity = $this->stateRepository->find($stateId);
        if (!$entity instanceof CalculationState) {
            return null;
        }

        return [
            'id' => $entity->getId(),
            'code' => $entity->getCode(),
        ];
    }

    private function getQueryEditable(DataQuery $query): int
    {
        return $query->getIntParameter(self::PARAM_EDITABLE);
    }

    private function getQueryStateId(DataQuery $query): int
    {
        return $query->getIntParameter(self::PARAM_STATE);
    }

    private function isEditable(int $stateEditable): bool
    {
        return 1 === $stateEditable;
    }
}
