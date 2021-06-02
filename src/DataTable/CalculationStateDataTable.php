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

namespace App\DataTable;

use App\DataTable\Model\AbstractEntityDataTable;
use App\DataTable\Model\DataColumn;
use App\DataTable\Model\DataColumnFactory;
use App\Entity\CalculationState;
use App\Repository\CalculationStateRepository;
use DataTables\DataTablesInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

/**
 * Calculation state data table handler.
 *
 * @author Laurent Muller
 *
 * @template-extends AbstractEntityDataTable<CalculationState>
 */
class CalculationStateDataTable extends AbstractEntityDataTable
{
    /**
     * The datatable identifier.
     */
    public const ID = CalculationState::class;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * Constructor.
     */
    public function __construct(RequestStack $requestStack, DataTablesInterface $datatables, CalculationStateRepository $repository, Environment $environment, TranslatorInterface $translator)
    {
        parent::__construct($requestStack, $datatables, $repository, $environment);
        $this->translator = $translator;
    }

    /**
     * Creates the link to calculations.
     */
    public function formatCalculations(\Countable $calculations, CalculationState $item): string
    {
        $context = [
            'id' => $item->getId(),
            'code' => $item->getCode(),
            'count' => $calculations->count(),
        ];

        return $this->renderTemplate('calculationstate/calculationstate_cell_calculation.html.twig', $context);
    }

    /**
     * Transalte a boolean value to a "Yes/No" string.
     *
     * @param bool $value the value to translate
     *
     * @return string the translated value
     */
    public function formatEditable(bool $value): string
    {
        $id = $value ? 'common.value_true' : 'common.value_false';

        return $this->translator->trans($id);
    }

    /**
     * {@inheritdoc}
     */
    protected function createColumns(): array
    {
        $path = __DIR__ . '/Definition/calculation_state.json';

        return DataColumnFactory::fromJson($this, $path);
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultOrder(): array
    {
        return ['code' => DataColumn::SORT_ASC];
    }
}
