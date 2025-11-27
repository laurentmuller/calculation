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

use App\Attribute\ForUser;
use App\Attribute\IndexRoute;
use App\Attribute\PdfRoute;
use App\Chart\StateChart;
use App\Entity\Calculation;
use App\Enums\EntityPermission;
use App\Report\CalculationByStateReport;
use App\Repository\CalculationStateRepository;
use App\Response\PdfResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * The controller for calculations by state chart.
 */
#[ForUser]
#[Route(path: '/chart/state', name: 'chart_state_')]
class ChartStateController extends AbstractController
{
    #[IndexRoute]
    public function index(StateChart $chart): Response
    {
        $this->checkPermission(EntityPermission::LIST);
        $parameters = $chart->generate();

        return $this->render('chart/chart_state.html.twig', $parameters);
    }

    #[PdfRoute]
    public function pdf(CalculationStateRepository $repository, UrlGeneratorInterface $generator): PdfResponse
    {
        $this->checkPermission(EntityPermission::EXPORT);
        $state = $repository->getCalculations();

        return $this->renderPdfDocument(new CalculationByStateReport($this, $state, $generator));
    }

    private function checkPermission(EntityPermission $permission): void
    {
        $this->denyAccessUnlessGranted($permission, Calculation::class);
    }
}
