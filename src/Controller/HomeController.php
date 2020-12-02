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

use App\Repository\CalculationRepository;
use App\Repository\CalculationStateRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
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

        // get states count and total
        [$count, $total] = \array_reduce($states, function (array $carry, array $state) {
            $carry[0] += $state['count'];
            $carry[1] += $state['total'];

            return $carry;
        }, [0, 0]);

        // render view
        return $this->render('home/index.html.twig', [
            'calculations' => $calculations,
            'min_margin' => $margin,
            'states' => $states,
            'months' => $months,
            'count' => $count,
            'total' => $total,
        ]);
    }

    /**
     * Display the site map.
     *
     * @Route("/sitemap", name="site_map")
     * @IsGranted("ROLE_USER")
     */
    public function siteMap(): Response
    {
        return $this->render('home/sitemap.html.twig');
    }
}
