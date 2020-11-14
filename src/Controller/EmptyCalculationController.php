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

use App\DataTable\CalculationEmptyDataTable;
use App\Report\CalculationEmptyTableReport;
use App\Repository\CalculationRepository;
use Doctrine\Common\Collections\Criteria;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Controller for calculations where items has the price or the quantity is equal to 0.
 *
 * @author Laurent Muller
 *
 * @Route("/empty")
 * @IsGranted("ROLE_ADMIN")
 */
class EmptyCalculationController extends AbstractController
{
    /**
     * Shows calculations, as card, where items has the price or the quantity is equal to 0.
     *
     * @Route("", name="empty_card")
     */
    public function card(Request $request, CalculationRepository $repository): Response
    {
        $calculations = $repository->getEmptyItems();
        $selection = $request->get('selection', 0);
        $edit = $this->getApplication()->isEditAction();

        // number of items
        $items_count = \array_reduce($calculations, function (float $carry, array $calculation) {
            return $carry + \count($calculation['items']);
        }, 0);

        // parameters
        $parameters = [
                'items' => $calculations,
                'items_count' => $items_count,
                'query' => false,
                'selection' => $selection,
                'sortField' => 'id',
                'sortMode' => Criteria::DESC,
                'sortFields' => [],
                'edit' => $edit,
            ];

        return $this->render('calculation/calculation_card_empty.html.twig', $parameters);
    }

    /**
     * Export the calculations where items has the price or the quantity is equal to 0.
     *
     * @Route("/pdf", name="empty_pdf")
     */
    public function pdf(CalculationRepository $repository): Response
    {
        $items = $repository->getEmptyItems();
        if (empty($items)) {
            $this->warningTrans('empty.empty');

            return  $this->redirectToHomePage();
        }

        $report = new CalculationEmptyTableReport($this);
        $report->setItems($items);

        return $this->renderPdfDocument($report);
    }

    /**
     * Shows calculations, as table, where items has the price or the quantity is equal to 0.
     *
     * @Route("/table", name="empty_table")
     */
    public function table(Request $request, CalculationEmptyDataTable $table): Response
    {
        $results = $table->handleRequest($request);
        if ($table->isCallback()) {
            return $this->json($results);
        }

        // attributes
        $attributes = [
            'edit-action' => \json_encode($this->getApplication()->isEditAction()),
            'itemsCount' => $table->getItemCounts(),
        ];

        // parameters
        $parameters = [
            'results' => $results,
            'attributes' => $attributes,
            'columns' => $table->getColumns(),
        ];

        return $this->render('calculation/calculation_table_empty.html.twig', $parameters);
    }
}
