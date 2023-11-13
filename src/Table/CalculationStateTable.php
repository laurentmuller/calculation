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
use App\Traits\AuthorizationCheckerAwareTrait;
use App\Traits\TableCellTrait;
use App\Traits\TranslatorAwareTrait;
use App\Utils\FileUtils;
use Doctrine\ORM\QueryBuilder;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Symfony\Contracts\Service\ServiceSubscriberTrait;
use Twig\Environment;

/**
 * The calculation states table.
 *
 * @method CalculationStateRepository getRepository()
 *
 * @template-extends AbstractEntityTable<CalculationState>
 */
class CalculationStateTable extends AbstractEntityTable implements ServiceSubscriberInterface
{
    use AuthorizationCheckerAwareTrait;
    use ServiceSubscriberTrait;
    use TableCellTrait;
    use TranslatorAwareTrait;

    public function __construct(// phpcs:ignore
        CalculationStateRepository $repository,
        protected readonly Environment $twig
    ) {
        parent::__construct($repository);
    }

    /**
     * Formatter for the calculation column.
     *
     * @throws \Twig\Error\Error
     *
     * @psalm-param array{id: int} $entity
     */
    public function formatCalculations(int $value, array $entity): string
    {
        $route = $this->isGrantedList(Calculation::class) ? 'calculation_table' : false;

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

    protected function createDefaultQueryBuilder(string $alias = AbstractRepository::DEFAULT_ALIAS): QueryBuilder
    {
        return $this->getRepository()->getTableQueryBuilder($alias);
    }

    protected function getColumnDefinitions(): string
    {
        return FileUtils::buildPath(__DIR__, 'Definition', 'calculation_state.json');
    }

    protected function getDefaultOrder(): array
    {
        return ['code' => self::SORT_ASC];
    }

    protected function updateResults(DataQuery $query, DataResults &$results): void
    {
        parent::updateResults($query, $results);
        if (!$query->callback) {
            $results->addAttribute('row-style', 'styleTextMuted');
        }
    }
}
