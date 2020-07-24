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

use App\DataTables\SearchDataTable;
use App\Interfaces\EntityVoterInterface;
use App\Repository\CalculationRepository;
use App\Repository\CalculationStateRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * The index controler (home page).
 */
class IndexController extends AbstractController
{
    /**
     * The home page route.
     */
    public const HOME_PAGE = 'homepage';

    /**
     * Display the home page.
     *
     * @Route("/", name="homepage")
     */
    public function index(CalculationRepository $calculationRepository, CalculationStateRepository $stateRepository): Response
    {
        $tabular = $this->isDisplayTabular();

        // get values to display
        $states = $stateRepository->getByState();
        $months = $calculationRepository->getByMonth();
        $calculations = $calculationRepository->getLastCalculations($tabular ? 10 : 6);
        $edit = $this->getApplication()->isEditAction();

        // get states count and total
        $count = 0;
        $total = 0;
        foreach ($states as $state) {
            $count += $state['count'];
            $total += $state['total'];
        }

        // render view
        return $this->render('index/index.html.twig', [
            'min_margin' => $this->getApplication()->getMinMargin(),
            'calculations' => $calculations,
            'tabular' => $tabular,
            'states' => $states,
            'months' => $months,
            'count' => $count,
            'total' => $total,
            'edit' => $edit,
        ]);
    }

    /**
     * Render datatable for native query search.
     *
     * @param Request         $request the request to get parameters
     * @param SearchDataTable $table   the datatable
     *
     * @Route("/search", name="search")
     */
    public function search(Request $request, SearchDataTable $table): Response
    {
        $results = $table->handleRequest($request);
        if ($table->isCallback()) {
            return $this->json($results);
        }

        // authorizations
        $show_granted = $table->isActionGranted(EntityVoterInterface::ATTRIBUTE_SHOW);
        $edit_granted = $table->isActionGranted(EntityVoterInterface::ATTRIBUTE_EDIT);
        $delete_granted = $table->isActionGranted(EntityVoterInterface::ATTRIBUTE_DELETE);

        // attributes
        $attributes = [
            'edit-action' => \json_encode($this->getApplication()->isEditAction()),
        ];

        // render
        $parameters = [
            'results' => $results,
            'columns' => $table->getColumns(),
            'show_granted' => $show_granted,
            'edit_granted' => $edit_granted,
            'delete_granted' => $delete_granted,
            'attributes' => $attributes,
        ];

        return $this->render('index/search.html.twig', $parameters);
    }

    /**
     * Display the Site Map.
     *
     * @Route("/sitemap", name="site_map"): Response
     */
    public function siteMap()
    {
        return $this->render('index/sitemap.html.twig');
    }
}
