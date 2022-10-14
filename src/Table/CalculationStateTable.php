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
use App\Repository\CalculationStateRepository;
use App\Traits\AuthorizationCheckerAwareTrait;
use App\Traits\TranslatorAwareTrait;
use App\Util\FileUtils;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Symfony\Contracts\Service\ServiceSubscriberTrait;
use Twig\Environment;

/**
 * The calculation states table.
 *
 * @template-extends AbstractEntityTable<CalculationState>
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class CalculationStateTable extends AbstractEntityTable implements ServiceSubscriberInterface
{
    use AuthorizationCheckerAwareTrait;
    use ServiceSubscriberTrait;
    use TranslatorAwareTrait;

    /**
     * Constructor.
     */
    public function __construct(
        CalculationStateRepository $repository,
        private readonly Environment $twig
    ) {
        parent::__construct($repository);
    }

    /**
     * Formatter for the calculation column.
     *
     * @throws \Twig\Error\Error
     */
    public function formatCalculations(\Countable $calculations, CalculationState $state): string
    {
        $context = [
            'count' => $calculations->count(),
            'title' => 'calculationstate.list.calculation_title',
            'route' => $this->isGrantedList(Calculation::class) ? 'calculation_table' : false,
            'parameters' => [
                CalculationTable::PARAM_STATE => $state->getId(),
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
