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

use App\DataTable\CalculationDuplicateDataTable;
use App\Report\CalculationDuplicateTableReport;
use App\Repository\CalculationRepository;
use Doctrine\Common\Collections\Criteria;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Controller to display and export duplicate items in the calculations.
 *
 * @author Laurent Muller
 *
 * @Route("/duplicate")
 * @IsGranted("ROLE_ADMIN")
 */
class DuplicateCalculationController extends AbstractController
{
    /**
     * Shows duplicate items, as card, in the calculations.
     *
     * @Route("", name="duplicate_card")
     */
    public function card(Request $request, CalculationRepository $repository): Response
    {
        $items = $this->getItems($repository);
        if (empty($items)) {
            $this->warningTrans('duplicate.empty');

            return $this->redirectToHomePage();
        }

        // number of items
        $items_count = \array_reduce($items, function (int $carry, array $calculation) {
            foreach ($calculation['items'] as $item) {
                $carry += $item['count'];
            }

            return $carry;
        }, 0);

        // parameters
        $parameters = [
                'items' => $items,
                'items_count' => $items_count,
                'query' => false,
                'id' => $request->get('id', 0),
                'sortField' => 'id',
                'sortMode' => Criteria::DESC,
                'sortFields' => [],
            ];

        return $this->render('calculation/calculation_card_duplicate.html.twig', $parameters);
    }

    /**
     * Exports the duplicate items in the calculations.
     *
     * @Route("/pdf", name="duplicate_pdf")
     */
    public function pdf(CalculationRepository $repository): Response
    {
        $items = $this->getItems($repository);
        if (empty($items)) {
            $this->warningTrans('duplicate.empty');

            return $this->redirectToHomePage();
        }

        $report = new CalculationDuplicateTableReport($this);
        $report->setItems($items);

        return $this->renderPdfDocument($report);
    }

    /**
     * Display the duplicate items, as table, in the calculations.
     *
     * @Route("/table", name="duplicate_table")
     */
    public function table(Request $request, CalculationDuplicateDataTable $table, CalculationRepository $repository): Response
    {
        $results = $table->handleRequest($request);
        if ($table->isCallback()) {
            return $this->json($results);
        }

        $items = $this->getItems($repository);
        if (empty($items)) {
            $this->warningTrans('duplicate.empty');

            return $this->redirectToHomePage();
        }

        // attributes
        $attributes = [
            'itemsCount' => $table->getItemCounts(),
        ];

        // parameters
        $parameters = [
            'results' => $results,
            'attributes' => $attributes,
            'columns' => $table->getColumns(),
        ];

        return $this->render('calculation/calculation_table_duplicate.html.twig', $parameters);
    }

    /**
     * Gets items to display.
     */
    private function getItems(CalculationRepository $repository): array
    {
        return $repository->getDuplicateItems();
    }
}
