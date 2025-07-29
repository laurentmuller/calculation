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
use App\Repository\CalculationStateRepository;
use App\Service\IndexService;
use App\Traits\AuthorizationCheckerAwareTrait;
use App\Traits\TableCellTrait;
use App\Traits\TranslatorAwareTrait;
use App\Utils\FileUtils;
use Doctrine\ORM\QueryBuilder;
use Symfony\Contracts\Service\ServiceMethodsSubscriberTrait;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Twig\Environment;

/**
 * The calculation state table.
 *
 * @template-extends AbstractEntityTable<CalculationState, CalculationStateRepository>
 */
class CalculationStateTable extends AbstractEntityTable implements ServiceSubscriberInterface
{
    use AuthorizationCheckerAwareTrait;
    use ServiceMethodsSubscriberTrait;
    use TableCellTrait;
    use TranslatorAwareTrait;

    public function __construct(
        CalculationStateRepository $repository,
        protected readonly Environment $twig,
        private readonly IndexService $indexService
    ) {
        parent::__construct($repository);
    }

    /**
     * Formatter for the calculation column.
     *
     * @phpstan-param array{id: int} $entity
     *
     * @throws \Twig\Error\Error
     */
    public function formatCalculations(int $value, array $entity): string
    {
        $route = $this->isGrantedList(Calculation::class) ? 'calculation_index' : false;

        return $this->renderCell(
            $value,
            $entity,
            'calculationstate.list.calculation_title',
            $route,
            CalculationTable::PARAM_STATE
        );
    }

    /**
     * Format the editable state.
     */
    public function formatEditable(bool $value): string
    {
        if ($value) {
            return $this->trans('common.value_true');
        }

        return $this->trans('common.value_false');
    }

    #[\Override]
    protected function count(): int
    {
        return $this->indexService->getCatalog()['calculationState'];
    }

    #[\Override]
    protected function createQueryBuilder(string $alias = AbstractRepository::DEFAULT_ALIAS): QueryBuilder
    {
        return $this->getRepository()->getTableQueryBuilder($alias);
    }

    #[\Override]
    protected function getColumnDefinitions(): string
    {
        return FileUtils::buildPath(__DIR__, 'Definition', 'calculation_state.json');
    }

    #[\Override]
    protected function updateResults(DataQuery $query, DataResults &$results): void
    {
        parent::updateResults($query, $results);
        if (!$query->callback) {
            $results->addAttribute('row-style', 'styleTextMuted');
        }
    }
}
