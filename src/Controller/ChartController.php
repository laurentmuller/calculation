<?php
/*
 * This file is part of the Calculation package.
 *
 * (c) bibi.nu <bibi@bibi.nu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace App\Controller;

use App\Chart\MonthChart;
use App\Chart\StateChart;
use App\Interfaces\RoleInterface;
use App\Traits\MathTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * The controller for charts.
 */
#[AsController]
#[Route(path: '/chart')]
#[IsGranted(RoleInterface::ROLE_USER)]
class ChartController extends AbstractController
{
    use MathTrait;

    /**
     * Gets the calculations by month.
     *
     * @throws \Doctrine\ORM\Exception\ORMException
     * @throws \Exception
     */
    #[Route(path: '/month', name: 'chart_by_month', methods: Request::METHOD_GET)]
    public function month(Request $request, MonthChart $chart): Response
    {
        $key = 'chart_by_month';
        $count = $this->getSessionInt($key, 6);
        $count = $this->getRequestInt($request, 'count', $count);
        $parameters = $chart->generate($count);
        $this->setSessionValue($key, $parameters['months']);

        return $this->render('chart/chart_month.html.twig', $parameters);
    }

    /**
     * Gets the calculations by state.
     *
     * @throws \Exception
     */
    #[Route(path: '/state', name: 'chart_by_state')]
    public function state(StateChart $chart): Response
    {
        return $this->render('chart/chart_state.html.twig', $chart->generate());
    }
}
