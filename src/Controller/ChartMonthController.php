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

use App\Attribute\IndexRoute;
use App\Attribute\PdfRoute;
use App\Chart\MonthChart;
use App\Entity\Calculation;
use App\Enums\EntityPermission;
use App\Interfaces\RoleInterface;
use App\Report\CalculationByMonthReport;
use App\Repository\CalculationRepository;
use App\Response\PdfResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * The controller for calculations by month chart.
 */
#[Route(path: '/chart/month', name: 'chart_month_')]
#[IsGranted(RoleInterface::ROLE_USER)]
class ChartMonthController extends AbstractController
{
    private const KEY_MONTHS = 'chart_month';

    #[IndexRoute]
    public function index(Request $request, MonthChart $chart): Response
    {
        $this->checkPermission(EntityPermission::LIST);
        $months = $this->getRequestMonths($request);
        $parameters = $chart->generate($months);
        $this->setSessionValue(self::KEY_MONTHS, $parameters['months']);

        return $this->render('chart/chart_month.html.twig', $parameters);
    }

    #[PdfRoute]
    public function pdf(
        Request $request,
        CalculationRepository $repository,
        UrlGeneratorInterface $generator
    ): PdfResponse {
        $this->checkPermission(EntityPermission::EXPORT);
        $months = $this->getRequestMonths($request);
        $data = $repository->getByMonth($months);
        $report = new CalculationByMonthReport($this, $data, $generator);

        return $this->renderPdfDocument($report);
    }

    private function checkPermission(EntityPermission $permission): void
    {
        $this->denyAccessUnlessGranted($permission, Calculation::class);
    }

    /**
     * @throws BadRequestHttpException
     */
    private function getRequestMonths(Request $request): int
    {
        $sessionCount = $this->getSessionInt(self::KEY_MONTHS, 6);
        $requestCount = $this->getRequestInt($request, 'count', $sessionCount);
        if ($requestCount <= 0) {
            throw new BadRequestHttpException($this->trans('chart.month.error'));
        }

        return $requestCount;
    }
}
