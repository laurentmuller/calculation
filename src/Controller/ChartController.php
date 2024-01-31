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

use App\Attribute\Get;
use App\Chart\MonthChart;
use App\Chart\StateChart;
use App\Entity\Calculation;
use App\Enums\EntityPermission;
use App\Interfaces\RoleInterface;
use App\Report\CalculationByMonthReport;
use App\Report\CalculationByStateReport;
use App\Repository\CalculationRepository;
use App\Repository\CalculationStateRepository;
use App\Response\PdfResponse;
use App\Traits\MathTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * The controller for charts.
 *
 * @psalm-import-type QueryCalculationType from CalculationStateRepository
 */
#[AsController]
#[Route(path: '/chart')]
#[IsGranted(RoleInterface::ROLE_USER)]
class ChartController extends AbstractController
{
    use MathTrait;

    private const KEY_MONTHS = 'chart_month';

    /**
     * @throws \Doctrine\ORM\Exception\ORMException
     * @throws BadRequestHttpException
     */
    #[Get(path: '/month', name: 'chart_month')]
    public function month(Request $request, MonthChart $chart): Response
    {
        $this->checkAccess();
        $months = $this->getMonths($request);
        /** @psalm-var array{months: int} $parameters */
        $parameters = $chart->generate($months);
        $this->setSessionValue(self::KEY_MONTHS, $parameters['months']);

        return $this->render('chart/chart_month.html.twig', $parameters);
    }

    /**
     * @throws BadRequestHttpException
     * @throws \Exception
     */
    #[Get(path: '/month/pdf', name: 'chart_month_pdf')]
    public function monthPdf(Request $request, CalculationRepository $repository): PdfResponse
    {
        $this->checkAccess(EntityPermission::EXPORT);
        $months = $this->getMonths($request);
        $data = $repository->getByMonth($months);
        $report = new CalculationByMonthReport($this, $data);

        return $this->renderPdfDocument($report);
    }

    /**
     * @throws \Exception
     */
    #[Get(path: '/state', name: 'chart_state')]
    public function state(StateChart $chart): Response
    {
        $this->checkAccess();
        $parameters = $chart->generate();

        return $this->render('chart/chart_state.html.twig', $parameters);
    }

    #[Get(path: '/state/pdf', name: 'chart_state_pdf')]
    public function statePdf(CalculationStateRepository $repository): PdfResponse
    {
        $this->checkAccess(EntityPermission::EXPORT);
        $data = $repository->getCalculations();
        $report = new CalculationByStateReport($this, $data);

        return $this->renderPdfDocument($report);
    }

    private function checkAccess(EntityPermission $permission = EntityPermission::LIST): void
    {
        $this->denyAccessUnlessGranted($permission, Calculation::class);
    }

    /**
     * @throws BadRequestHttpException
     */
    private function getMonths(Request $request): int
    {
        $count = $this->getSessionInt(self::KEY_MONTHS, 6);
        $months = $this->getRequestInt($request, 'count', $count);
        if ($months <= 0) {
            throw new BadRequestHttpException($this->trans('error.month', [], 'chart'));
        }

        return $months;
    }
}
