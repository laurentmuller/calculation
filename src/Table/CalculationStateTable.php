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

use App\Entity\CalculationState;
use App\Repository\CalculationStateRepository;
use App\Traits\TranslatorTrait;
use App\Util\FileUtils;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

/**
 * The calculation states table.
 *
 * @template-extends AbstractEntityTable<CalculationState>
 */
class CalculationStateTable extends AbstractEntityTable
{
    use TranslatorTrait;

    /**
     * Constructor.
     */
    public function __construct(CalculationStateRepository $repository, TranslatorInterface $translator, private readonly Environment $twig)
    {
        parent::__construct($repository);
        $this->setTranslator($translator);
    }

    /**
     * Formatter for the calculation column.
     */
    public function formatCalculations(\Countable $calculations, CalculationState $state): string
    {
        return $this->twig->render('macros/_cell_table_link.html.twig', [
            'route' => 'calculation_table',
            'count' => $calculations->count(),
            'title' => 'calculationstate.list.calculation_title',
            'parameters' => [
                CalculationTable::PARAM_STATE => $state->getId(),
            ],
        ]);
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
