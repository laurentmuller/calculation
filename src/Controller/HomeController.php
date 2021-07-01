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
use SlopeIt\BreadcrumbBundle\Annotation\Breadcrumb;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * The home controler (home page, search and site map).
 *
 * @author Laurent Muller
 */
class HomeController extends AbstractController
{
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
        $states = $stateRepository->getListCountCalculations();
        $months = $calculRepository->getByMonth();
        $calculations = $calculRepository->getLastCalculations($tabular ? 10 : 6);
        $min_margin = $this->getApplication()->getMinMargin();

        // get states count and total
        [$states_count, $states_total] = \array_reduce($states, function (array $carry, array $state) {
            $carry[0] += $state['count'];
            $carry[1] += $state['total'];

            return $carry;
        }, [0, 0]);

        // render view
        return $this->renderForm('home/index.html.twig', [
            'calculations' => $calculations,
            'min_margin' => $min_margin,
            'states_count' => $states_count,
            'states_total' => $states_total,
            'states' => $states,
            'months' => $months,
        ]);
    }
}
