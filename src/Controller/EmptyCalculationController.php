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
use App\Report\CalculationEmptyReport;
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
        $items = $this->getItems($repository);
        if (empty($items)) {
            $this->warningTrans('empty.empty');

            return $this->redirectToHomePage();
        }

        // number of items
        $items_count = \array_reduce($items, function (int $carry, array $calculation) {
            return $carry + \count($calculation['items']);
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

        return $this->render('calculation/calculation_card_empty.html.twig', $parameters);
    }

    /**
     * Export the calculations where items has the price or the quantity is equal to 0.
     *
     * @Route("/pdf", name="empty_pdf")
     */
    public function pdf(CalculationRepository $repository): Response
    {
        $items = $this->getItems($repository);
        if (empty($items)) {
            $this->warningTrans('empty.empty');

            return $this->redirectToHomePage();
        }

        $doc = new CalculationEmptyReport($this, $items);

        return $this->renderPdfDocument($doc);
    }

    /**
     * Shows calculations, as table, where items has the price or the quantity is equal to 0.
     *
     * @Route("/table", name="empty_table")
     */
    public function table(Request $request, CalculationEmptyDataTable $table, CalculationRepository $repository): Response
    {
        $results = $table->handleRequest($request);
        if ($table->isCallback()) {
            return $this->json($results);
        }

        // empty?
        $items = $this->getItems($repository);
        if (empty($items)) {
            $this->warningTrans('empty.empty');

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

        return $this->render('calculation/calculation_table_empty.html.twig', $parameters);
    }

    /**
     * Gets items to display.
     */
    private function getItems(CalculationRepository $repository): array
    {
        return $repository->getEmptyItems();
    }
}
