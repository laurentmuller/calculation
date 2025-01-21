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
use App\Service\CalculationService;
use Doctrine\ORM\Exception\ORMException;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Controller to update calculation's total or the user margin within XMLHttpRequest (Ajax) calls.
 */
#[AsController]
#[Route(path: '/calculation', name: 'calculation_')]
#[IsGranted(RoleInterface::ROLE_USER)]
class AjaxCalculationController extends AbstractController
{
    /**
     * @throws ORMException
     */
    #[Post(path: '/update', name: 'update')]
    public function update(
        CalculationService $service,
        LoggerInterface $logger,
        #[MapRequestPayload]
        CalculationQuery $query = new CalculationQuery()
    ): JsonResponse {
        try {
            $parameters = $service->createParameters($query);
            if ($parameters['result']) {
                $view = $this->renderView('calculation/calculation_ajax_totals.html.twig', $parameters);
                $parameters = \array_merge($parameters, [
                    'adjust' => $query->adjust,
                    'view' => $view,
                ]);
            }

            return $this->json($parameters);
        } catch (\Exception $e) {
            $message = $this->trans('calculation.edit.error.update_total');
            $context = $this->getExceptionContext($e);
            $logger->error($message, $context);

            return $this->jsonException($e, $message);
        }
    }
}
