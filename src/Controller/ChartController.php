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

use App\Chart\MonthChart;
use App\Chart\StateChart;
use App\Traits\MathTrait;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * The controller for charts.
 *
 * @author Laurent Muller
 *
 * @Route("/chart")
 * @IsGranted("ROLE_USER")
 */
class ChartController extends AbstractController
{
    use MathTrait;

    /**
     * Gets the calculations by month.
     *
     * @Route("/month/{count}", name="chart_by_month", requirements={"count" = "\d+" })
     */
    public function month(MonthChart $chart, int $count = 6): Response
    {
        $data = $chart->generate($count);

        return $this->renderForm('chart/chart_month.html.twig', $data);
    }

    /**
     * Gets the calculations by state.
     *
     * @Route("/state", name="chart_by_state")
     */
    public function state(StateChart $chart): Response
    {
        $data = $chart->generate();

        return $this->renderForm('chart/chart_state.html.twig', $data);
    }
}
