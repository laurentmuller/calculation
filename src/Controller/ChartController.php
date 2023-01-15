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
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Requirement\Requirement;
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
     * @throws \Psr\Cache\InvalidArgumentException
     * @throws \Doctrine\ORM\Exception\ORMException
     * @throws \Exception
     */
    #[Route(path: '/month/{count}', name: 'chart_by_month', requirements: ['count' => Requirement::DIGITS])]
    public function month(MonthChart $chart, int $count = 6): Response
    {
        $data = $chart->generate($count);

        return $this->render('chart/chart_month.html.twig', $data);
    }

    /**
     * Gets the calculations by state.
     *
     * @throws \Psr\Cache\InvalidArgumentException
     * @throws \Exception
     */
    #[Route(path: '/state', name: 'chart_by_state')]
    public function state(StateChart $chart): Response
    {
        $data = $chart->generate();

        return $this->render('chart/chart_state.html.twig', $data);
    }
}
