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
use App\Attribute\PostRoute;
use App\Model\CalculationAdjustQuery;
use App\Model\CalculationAdjustResult;
use App\Service\CalculationService;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Controller to update calculation's total or the user margin within XMLHttpRequest (Ajax) calls.
 */
#[ForUser]
#[Route(path: '/calculation', name: 'calculation_')]
class AjaxCalculationController extends AbstractController
{
    #[PostRoute(path: '/update', name: 'update')]
    public function update(
        CalculationService $service,
        LoggerInterface $logger,
        #[MapRequestPayload]
        CalculationAdjustQuery $query = new CalculationAdjustQuery()
    ): JsonResponse {
        try {
            $parameters = $service->createParameters($query);
            $parameters->view = $this->renderTotalView($parameters);

            return $this->json($parameters);
        } catch (\Exception $e) {
            $message = $this->trans('calculation.edit.error.update_total');
            $context = $this->getExceptionContext($e);
            $logger->error($message, $context);

            return $this->jsonException($e, $message);
        }
    }

    private function renderTotalView(CalculationAdjustResult $parameters): string
    {
        return $this->renderView('calculation/calculation_ajax_totals.html.twig', $parameters->toArray());
    }
}
