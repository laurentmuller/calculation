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

use App\Attribute\GetPostRoute;
use App\Attribute\IndexRoute;
use App\Attribute\IsUser;
use App\Attribute\PdfRoute;
use App\Chart\MonthChart;
use App\Entity\Calculation;
use App\Enums\EntityPermission;
use App\Form\Type\MonthRangeType;
use App\Model\MonthRange;
use App\Model\TranslatableFlashMessage;
use App\Report\CalculationByMonthReport;
use App\Repository\CalculationRepository;
use App\Response\PdfResponse;
use App\Traits\RenderPdfDocumentTrait;
use App\Utils\DateUtils;
use Symfony\Component\Clock\DatePoint;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * The controller for calculations by month chart.
 */
#[IsUser]
#[Route(path: '/chart/month', name: 'chart_month_')]
class ChartMonthController extends AbstractController
{
    use RenderPdfDocumentTrait;

    public function __construct(private readonly CalculationRepository $repository)
    {
    }

    #[IndexRoute]
    public function index(
        MonthChart $chart,
        #[MapQueryString]
        ?MonthRange $monthRange = null
    ): Response {
        $this->checkPermission(EntityPermission::LIST);

        $dates = $this->getMinMaxDates();
        $monthRange ??= $this->createMonthRange($dates);
        $parameters = $chart->generate($monthRange);
        $this->saveMonthRange($monthRange);

        return $this->render('chart/chart_month.html.twig', $parameters);
    }

    #[PdfRoute]
    public function pdf(
        UrlGeneratorInterface $generator,
        #[MapQueryString]
        ?MonthRange $monthRange = null
    ): PdfResponse {
        $this->checkPermission(EntityPermission::EXPORT);

        $dates = $this->getMinMaxDates();
        $monthRange ??= $this->createMonthRange($dates);
        $monthChartData = $this->repository->getMonthChartData($monthRange);
        $report = new CalculationByMonthReport($this, $monthChartData, $generator);
        $this->saveMonthRange($monthRange);

        return $this->renderPdfDocument($report);
    }

    #[GetPostRoute('/period', 'period')]
    public function period(
        Request $request,
        #[MapQueryString]
        ?MonthRange $monthRange = null
    ): Response {
        $this->checkPermission(EntityPermission::LIST);

        $dates = $this->getMinMaxDates();
        $monthRange ??= $this->createMonthRange($dates);
        $form = $this->createForm(MonthRangeType::class, $monthRange, [
            'min_date' => $dates[0],
            'max_date' => $dates[1],
            'from_label' => 'chart.period.fields.from',
            'to_label' => 'chart.period.fields.to',
        ]);
        if ($this->handleRequestForm($request, $form)) {
            $message = TranslatableFlashMessage::success('chart.period.success', [
                '%from%' => MonthRangeType::formatDate($monthRange->from),
                '%to%' => MonthRangeType::formatDate($monthRange->to),
            ]);
            $this->addFlash($message->getType(), $message);

            return $this->redirectToRoute('chart_month_index', [
                'from' => DateUtils::formatFormDate($monthRange->from),
                'to' => DateUtils::formatFormDate($monthRange->to),
            ]);
        }

        return $this->render('chart/chart_period.html.twig', [
            'form' => $form,
        ]);
    }

    private function checkPermission(EntityPermission $permission): void
    {
        $this->denyAccessUnlessGranted($permission, Calculation::class);
    }

    /**
     * @param array{0: DatePoint, 1: DatePoint} $dates
     */
    private function createMonthRange(array $dates): MonthRange
    {
        $defaultFrom = $dates[1]->sub(new \DateInterval('P5M'));
        $from = $this->getSessionDate('chart_months_from', $defaultFrom);
        $to = $this->getSessionDate('chart_months_to', $dates[1]);

        return new MonthRange()
            ->withFrom($from)
            ->withTo($to);
    }

    /**
     * @return array{0: DatePoint, 1: DatePoint}
     */
    private function getMinMaxDates(): array
    {
        /** @phpstan-var array{0: DatePoint, 1: DatePoint} */
        return $this->repository->getMinMaxDates();
    }

    private function saveMonthRange(MonthRange $monthRange): void
    {
        $this->setSessionValue('chart_months_from', $monthRange->from);
        $this->setSessionValue('chart_months_to', $monthRange->to);
    }
}
