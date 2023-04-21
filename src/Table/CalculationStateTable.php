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
    use TranslatorAwareTrait;

    /**
     * Constructor.
     */
    public function __construct(CalculationStateRepository $repository, private readonly Environment $twig)
    {
        parent::__construct($repository);
    }

    /**
     * Formatter for the calculation column.
     *
     * @throws \Twig\Error\Error
     *
     * @psalm-param CalculationState|array{id: int} $state
     */
    public function formatCalculations(\Countable|int $calculations, CalculationState|array $state): string
    {
        $id = \is_array($state) ? $state['id'] : $state->getId();
        $count = $calculations instanceof \Countable ? $calculations->count() : $calculations;
        $context = [
            'count' => $count,
            'title' => 'calculationstate.list.calculation_title',
            'route' => $this->isGrantedList(Calculation::class) ? 'calculation_table' : false,
            'parameters' => [
                CalculationTable::PARAM_STATE => $id,
            ],
        ];

        return $this->twig->render('macros/_cell_table_link.html.twig', $context);
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

    /**
     * {@inheritdoc}
     */
    protected function createDefaultQueryBuilder(string $alias = AbstractRepository::DEFAULT_ALIAS): QueryBuilder
    {
        return $this->getRepository()->getTableQueryBuilder($alias);
    }

    /**
     * {@inheritdoc}
     */
    protected function getColumnDefinitions(): string
    {
        return FileUtils::buildPath(__DIR__, 'Definition', 'calculation_state.json');
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultOrder(): array
    {
        return ['code' => self::SORT_ASC];
    }

    /**
     * {@inheritDoc}
     */
    protected function updateResults(DataQuery $query, DataResults &$results): void
    {
        parent::updateResults($query, $results);
        if (!$query->callback) {
            $results->addAttribute('row-style', 'styleTextMuted');
        }
    }
}
