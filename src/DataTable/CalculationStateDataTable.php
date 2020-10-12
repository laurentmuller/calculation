<?php
/*
 * This file is part of the Calculation package.
 *
 * Copyright (c) 2019 bibi.nu. All rights reserved.
 *
 * This computer code is protected by copyright law and international
 * treaties. Unauthorised reproduction or distribution of this code, or
 * any portion of it, may result in severe civil and criminal penalties,
 * and will be prosecuted to the maximum extent possible under the law.
 */

declare(strict_types=1);

namespace App\DataTable;

use App\DataTable\Model\AbstractEntityDataTable;
use App\DataTable\Model\DataColumn;
use App\Entity\Calculation;
use App\Entity\CalculationState;
use App\Repository\CalculationStateRepository;
use App\Service\ApplicationService;
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
     * @param ApplicationService         $application the application to get parameters
     * @param SessionInterface           $session     the session to save/retrieve user parameters
     * @param DataTablesInterface        $datatables  the datatables to handle request
     * @param CalculationStateRepository $repository  the repository to get entities
     * @param Environment                $environment the Twig environment to render cells
     * @param TranslatorInterface        $translator  the service to translate messages
     */
    public function __construct(ApplicationService $application, SessionInterface $session, DataTablesInterface $datatables, CalculationStateRepository $repository, Environment $environment, TranslatorInterface $translator)
    {
        parent::__construct($application, $session, $datatables, $repository, $environment);
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
    public function linkCalculations(Collection $calculations, CalculationState $item): string
    {
        $parameters = [
            'id' => $item->getId(),
            'code' => $item->getCode(),
            'count' => \count($calculations),
        ];

        return $this->environment->render('calculationstate/calculationstate_calculation_cell.html.twig', $parameters);
    }

    /**
     * Transalte a boolean value to a "Yes/No" string.
     *
     * @param bool $value the value to translate
     *
     * @return string the translated value
     */
    public function translateEditable(bool $value): string
    {
        $id = $value ? 'common.value_true' : 'common.value_false';

        return $this->translator->trans($id);
    }

    /**
     * {@inheritdoc}
     */
    protected function createColumns(): array
    {
        return [
            DataColumn::hidden('id'),
            DataColumn::instance('code')
                ->setTitle('calculationstate.fields.code')
                ->setCallback('renderStateColor')
                ->addClassName('text-code text-nowrap')
                ->setDefault(true),
            DataColumn::instance('description')
                ->setTitle('calculationstate.fields.description')
                ->setClassName('w-auto cell'),
            DataColumn::instance('editable')
                ->setTitle('calculationstate.fields.editable')
                ->setClassName('text-state')
                ->setSearchable(false)
                //->setOrderable(false)
                ->setFormatter([$this, 'translateEditable']),
            DataColumn::currency('calculations')
                ->setTitle('calculationstate.fields.calculations')
                ->setSearchable(false)
                ->setOrderable(false)
                ->setRawData(true)
                ->setFormatter([$this, 'linkCalculations']),
            DataColumn::hidden('color'),
            DataColumn::actions([$this, 'renderActions']),
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultOrder(): array
    {
        return ['code' => DataColumn::SORT_ASC];
    }
}
