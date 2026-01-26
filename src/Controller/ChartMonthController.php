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
use App\Chart\MonthChart;
use App\Entity\Calculation;
use App\Enums\EntityPermission;
use App\Report\CalculationByMonthReport;
use App\Repository\CalculationRepository;
use App\Response\PdfResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * The controller for calculations by month chart.
 */
#[ForUser]
#[Route(path: '/chart/month', name: 'chart_month_')]
class ChartMonthController extends AbstractController
{
    private const string KEY_MONTHS = 'chart_months';

    #[IndexRoute]
    public function index(
        MonthChart $chart,
        #[MapQueryParameter]
        ?int $count = null
    ): Response {
        $this->checkPermission(EntityPermission::LIST);
        $count = $this->validateCount($count);
        $parameters = $chart->generate($count);
        $this->setSessionValue(self::KEY_MONTHS, $parameters['months']);

        return $this->render('chart/chart_month.html.twig', $parameters);
    }

    #[PdfRoute]
    public function pdf(
        CalculationRepository $repository,
        UrlGeneratorInterface $generator,
        #[MapQueryParameter]
        ?int $count = null
    ): PdfResponse {
        $this->checkPermission(EntityPermission::EXPORT);
        $count = $this->validateCount($count);
        $month = $repository->getByMonth($count);
        $report = new CalculationByMonthReport($this, $month, $generator);

        return $this->renderPdfDocument($report);
    }

    private function checkPermission(EntityPermission $permission): void
    {
        $this->denyAccessUnlessGranted($permission, Calculation::class);
    }

    /**
     * @throws BadRequestHttpException
     */
    private function validateCount(?int $count): int
    {
        $count ??= $this->getSessionInt(self::KEY_MONTHS, 6);
        if ($count <= 0) {
            throw new BadRequestHttpException($this->trans('chart.month.error'));
        }

        return $count;
    }
}
