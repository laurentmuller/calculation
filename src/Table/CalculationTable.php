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
use App\Utils\FileUtils;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpFoundation\Request;
use Twig\Environment;

/**
 * The calculations table.
 *
 * @method CalculationRepository getRepository()
 *
 * @template-extends AbstractEntityTable<Calculation>
 */
class CalculationTable extends AbstractEntityTable
{
    /**
     * The state editable parameter name (bool).
     */
    final public const PARAM_EDITABLE = 'stateEditable';
    /**
     * The state parameter name (int).
     */
    final public const PARAM_STATE = 'stateId';

    public function __construct(// phpcs:ignore
        CalculationRepository $repository,
        protected readonly CalculationStateRepository $stateRepository,
        protected readonly Environment $twig
    ) {
        parent::__construct($repository);
    }

    /**
     * Render the overall margin column.
     *
     * @throws \Twig\Error\Error
     *
     * @psalm-param Calculation|array{groups: int} $entity
     */
    public function formatOverallMargin(float $margin, Calculation|array $entity): string
    {
        $empty = \is_array($entity) ? 0 === $entity['groups'] : $entity->isEmpty();

        return $this->twig->render('macros/_cell_calculation_margin.html.twig', [
            'margin' => $margin,
            'empty' => $empty,
        ]);
    }

    public function getDataQuery(Request $request): DataQuery
    {
        $query = parent::getDataQuery($request);
        $stateId = $this->getRequestInt($request, self::PARAM_STATE);
        $query->addCustomData(self::PARAM_STATE, $stateId);
        $stateEditable = $this->getRequestInt($request, self::PARAM_EDITABLE);
        $query->addCustomData(self::PARAM_EDITABLE, $stateEditable);

        return $query;
    }

    protected function createDefaultQueryBuilder(string $alias = AbstractRepository::DEFAULT_ALIAS): QueryBuilder
    {
        return $this->getRepository()->getTableQueryBuilder($alias);
    }

    protected function getColumnDefinitions(): string
    {
        return FileUtils::buildPath(__DIR__, 'Definition', 'calculation.json');
    }

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

    protected function search(DataQuery $query, QueryBuilder $builder, string $alias): bool
    {
        $result = parent::search($query, $builder, $alias);
        /** @psalm-var int $stateId */
        $stateId = $query->getCustomData(self::PARAM_STATE, 0);
        if (0 !== $stateId) {
            /** @psalm-var string $field */
            $field = $this->repository->getSearchFields('state.id', $alias);
            $builder->andWhere($field . '=:' . self::PARAM_STATE)
                ->setParameter(self::PARAM_STATE, $stateId, Types::INTEGER);

            return true;
        }

        /** @psalm-var int $stateEditable */
        $stateEditable = $query->getCustomData(self::PARAM_EDITABLE, 0);
        if (0 !== $stateEditable) {
            /** @psalm-var string $field */
            $field = $this->repository->getSearchFields('state.editable', $alias);
            $builder->andWhere($field . '=:' . self::PARAM_EDITABLE)
                ->setParameter(self::PARAM_EDITABLE, $this->isEditable($stateEditable), Types::BOOLEAN);

            return true;
        }

        return $result;
    }

    protected function updateResults(DataQuery $query, DataResults &$results): void
    {
        parent::updateResults($query, $results);
        if (!$query->callback) {
            $results->addAttribute('row-style', 'styleTextMuted');
            /** @psalm-var int $stateId */
            $stateId = $query->getCustomData(self::PARAM_STATE, 0);
            $results->addParameter(self::PARAM_STATE, $stateId);

            $stateEditable = $query->getCustomData(self::PARAM_EDITABLE, 0);
            $results->addParameter(self::PARAM_EDITABLE, $stateEditable);

            $results->addCustomData('dropdown', $this->getDropDownValues());
            $results->addCustomData('state', $this->getCalculationState($stateId));
            $results->addCustomData('editable', $stateEditable);
        }
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

    private function isEditable(int $stateEditable): bool
    {
        return 1 === $stateEditable;
    }
}
