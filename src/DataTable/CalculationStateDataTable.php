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
use App\Entity\Calculation;
use App\Entity\CalculationState;
use App\Repository\CalculationStateRepository;
use DataTables\DataTablesInterface;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

/**
 * Calculation state data table handler.
 *
 * @author Laurent Muller
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
     *
     * @param SessionInterface           $session     the session to save/retrieve user parameters
     * @param DataTablesInterface        $datatables  the datatables to handle request
     * @param CalculationStateRepository $repository  the repository to get entities
     * @param Environment                $environment the Twig environment to render cells
     * @param TranslatorInterface        $translator  the service to translate messages
     */
    public function __construct(SessionInterface $session, DataTablesInterface $datatables, CalculationStateRepository $repository, Environment $environment, TranslatorInterface $translator)
    {
        parent::__construct($session, $datatables, $repository, $environment);
        $this->translator = $translator;
    }

    /**
     * Creates the link to calculations.
     *
     * @param Collection|Calculation[] $calculations the list of calculations that fall into the given state
     * @param CalculationState         $item         the calculation state
     *
     * @return string the link, if applicable, the value otherwise
     */
    public function calculationsFormatter(Collection $calculations, CalculationState $item): string
    {
        $context = [
            'id' => $item->getId(),
            'code' => $item->getCode(),
            'count' => \count($calculations),
        ];

        return $this->renderTemplate('calculationstate/calculationstate_calculation_cell.html.twig', $context);
    }

    /**
     * Transalte a boolean value to a "Yes/No" string.
     *
     * @param bool $value the value to translate
     *
     * @return string the translated value
     */
    public function editableFormatter(bool $value): string
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
