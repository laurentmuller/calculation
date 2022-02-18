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

namespace App\Controller;

use App\Repository\CalculationRepository;
use App\Repository\CalculationStateRepository;
use App\Traits\MathTrait;
use SlopeIt\BreadcrumbBundle\Annotation\Breadcrumb;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * The index controler for home page.
 *
 * @author Laurent Muller
 */
class IndexController extends AbstractController
{
    use MathTrait;

    /**
     * Display the home page.
     *
     * @Route("/", name="homepage")
     * @Breadcrumb({
     *     {"label" = "index.title"}
     * })
     */
    public function invoke(CalculationRepository $calculRepository, CalculationStateRepository $stateRepository): Response
    {
        // get values to display
        $tabular = $this->isDisplayTabular();
        $months = $calculRepository->getByMonth();
        $states = $stateRepository->getListCountCalculations();
        $calculations = $calculRepository->getLastCalculations($tabular ? 10 : 6);
        $min_margin = $this->getApplication()->getMinMargin();

        // get state count, overall total and items total
        $states_count = \array_sum(\array_column($states, 'count'));
        $states_total = \array_sum(\array_column($states, 'total'));
        $states_items = \array_sum(\array_column($states, 'items'));
        $states_margin = $this->safeDivide($states_total, $states_items);

        // render view
        return $this->renderForm('index/index.html.twig', [
            'calculations' => $calculations,
            'min_margin' => $min_margin,
            'states_count' => $states_count,
            'states_total' => $states_total,
            'states_margin' => $states_margin,
            'states' => $states,
            'months' => $months,
        ]);
    }
}
