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

use App\Attribute\Post;
use App\Interfaces\RoleInterface;
use App\Model\CalculationQuery;
use App\Resolver\CalculationQueryResolver;
use App\Service\CalculationService;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Controller to update calculation's total within XMLHttpRequest (Ajax) calls.
 */
#[AsController]
#[Route(path: '/calculation', name: 'calculation_')]
#[IsGranted(RoleInterface::ROLE_USER)]
class AjaxCalculationController extends AbstractController
{
    /**
     * Update the calculation's total.
     *
     * @throws \Doctrine\ORM\Exception\ORMException
     */
    #[Post(path: '/update', name: 'update')]
    public function update(
        CalculationService $service,
        LoggerInterface $logger,
        #[MapRequestPayload(resolver: CalculationQueryResolver::class)]
        CalculationQuery $query = new CalculationQuery()
    ): JsonResponse {
        try {
            $parameters = $service->createGroupsFromQuery($query);
            if (!$parameters['result']) {
                return $this->json($parameters);
            }

            $view = $this->renderView('calculation/calculation_ajax_totals.html.twig', $parameters);

            return $this->jsonTrue([
                'view' => $view,
                'adjust' => $query->adjust,
                'user_margin' => $parameters['user_margin'],
                'overall_margin' => $parameters['overall_margin'],
                'overall_total' => $parameters['overall_total'],
                'overall_below' => $parameters['overall_below'],
            ]);
        } catch (\Exception $e) {
            $message = $this->trans('calculation.edit.error.update_total');
            $context = $this->getExceptionContext($e);
            $logger->error($message, $context);

            return $this->jsonException($e, $message);
        }
    }
}
