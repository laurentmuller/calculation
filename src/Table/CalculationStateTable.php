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
use App\Traits\CheckerTrait;
use App\Traits\TranslatorTrait;
use App\Util\FileUtils;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

/**
 * The calculation states table.
 *
 * @template-extends AbstractEntityTable<CalculationState>
 */
class CalculationStateTable extends AbstractEntityTable
{
    use CheckerTrait;
    use TranslatorTrait;

    /**
     * Constructor.
     */
    public function __construct(
        CalculationStateRepository $repository,
        TranslatorInterface $translator,
        AuthorizationCheckerInterface $checker,
        private readonly Environment $twig
    ) {
        parent::__construct($repository);
        $this->translator = $translator;
        $this->checker = $checker;
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
