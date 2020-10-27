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

namespace App\Controller;

use App\DataTable\CalculationBelowDataTable;
use App\Report\CalculationsReport;
use App\Repository\CalculationRepository;
use Doctrine\Common\Collections\Criteria;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Controller for calculations where margins are below the minimum.
 *
 * @author Laurent Muller
 *
 * @Route("/below")
 * @IsGranted("ROLE_ADMIN")
 */
class BelowController extends AbstractController
{
    /**
     * Shows calculations, as card, where margins is below the minimum.
     *
     * @Route("", name="below_card")
     */
    public function card(Request $request, CalculationRepository $repository): Response
    {
        // get values
        $minMargin = $this->getApplication()->getMinMargin();
        $calculations = $repository->getBelowMargin($minMargin);
        $selection = $request->get('selection', 0);
        $edit = $this->getApplication()->isEditAction();

        // parameters
        $parameters = [
            'items' => $calculations,
            'items_count' => \count($calculations),
            'min_margin' => $minMargin,
            'query' => false,
            'selection' => $selection,
            'sortField' => 'id',
            'sortMode' => Criteria::DESC,
            'sortFields' => [],
            'edit' => $edit,
        ];

        return $this->render('calculation/calculation_card_below.html.twig', $parameters);
    }

    /**
     * Exports calculations where margins is below the minimum.
     *
     * @Route("/pdf", name="below_pdf")
     */
    public function pdf(CalculationRepository $repository): Response
    {
        $minMargin = $this->getApplication()->getMinMargin();
        $calculations = $repository->getBelowMargin($minMargin);
        if (empty($calculations)) {
            $this->warningTrans('below.empty');

            return  $this->redirectToHomePage();
        }

        $percent = $this->localePercent($minMargin);
        $description = $this->trans('below.description', ['%margin%' => $percent]);

        $report = new CalculationsReport($this);
        $report->setCalculations($calculations)
            ->setTitleTrans('below.title')
            ->setDescription($description);

        return $this->renderDocument($report);
    }

    /**
     * Shows calculations, as table, where margins is below the minimum.
     *
     * @Route("/table", name="below_table")
     */
    public function table(Request $request, CalculationBelowDataTable $table): Response
    {
        $results = $table->handleRequest($request);
        if ($table->isCallback()) {
            return $this->json($results);
        }

        // get values
        $margin = $this->getApplication()->getMinMargin();
        $margin_text = $this->trans('calculation.list.margin_below', ['%minimum%' => $this->localePercent($margin)]);

        $attributes = [
            'min_margin' => $margin,
            'min_margin_text' => $margin_text,
            'edit-action' => \json_encode($this->getApplication()->isEditAction()),
        ];

        // parameters
        $parameters = [
            'results' => $results,
            'attributes' => $attributes,
            'columns' => $table->getColumns(),
        ];

        return $this->render('calculation/calculation_table_below.html.twig', $parameters);
    }
}
