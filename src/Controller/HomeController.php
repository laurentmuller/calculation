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

use App\DataTable\SearchDataTable;
use App\Entity\Calculation;
use App\Entity\CalculationState;
use App\Entity\Category;
use App\Entity\Customer;
use App\Entity\Product;
use App\Interfaces\EntityVoterInterface;
use App\Repository\CalculationRepository;
use App\Repository\CalculationStateRepository;
use App\Util\Utils;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * The home controler (home page, search and site map).
 */
class HomeController extends AbstractController
{
    /**
     * Display the home page.
     *
     * @Route("/", name="homepage")
     */
    public function home(CalculationRepository $calculRepository, CalculationStateRepository $stateRepository): Response
    {
        // get values to display
        $tabular = $this->isDisplayTabular();
        $states = $stateRepository->getListCount();
        $months = $calculRepository->getByMonth();
        $calculations = $calculRepository->getLastCalculations($tabular ? 10 : 6);
        $margin = $this->getApplication()->getMinMargin();
        $edit = $this->getApplication()->isEditAction();

        // get states count and total
        $count = 0;
        $total = 0;
        foreach ($states as $state) {
            $count += $state['count'];
            $total += $state['total'];
        }

        // render view
        return $this->render('home/index.html.twig', [
            'calculations' => $calculations,
            'min_margin' => $margin,
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
     * @IsGranted("ROLE_USER")
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

        // entity types
        $entities = [
            \strtolower(Utils::getShortName(Calculation::class)) => 'calculation.name',
            \strtolower(Utils::getShortName(Product::class)) => 'product.name',
            \strtolower(Utils::getShortName(Category::class)) => 'category.name',
            \strtolower(Utils::getShortName(CalculationState::class)) => 'calculationstate.name',
        ];
        if ($this->isDebug()) {
            $entities[\strtolower(Utils::getShortName(Customer::class))] = 'customer.name';
        }

        // render
        $parameters = [
            'results' => $results,
            'columns' => $table->getColumns(),
            'show_granted' => $show_granted,
            'edit_granted' => $edit_granted,
            'delete_granted' => $delete_granted,
            'attributes' => $attributes,
            'entities' => $entities,
        ];

        return $this->render('home/search.html.twig', $parameters);
    }

    /**
     * Display the Site Map.
     *
     * @Route("/sitemap", name="site_map")
     * @IsGranted("ROLE_USER")
     */
    public function siteMap(): Response
    {
        return $this->render('home/sitemap.html.twig');
    }
}
