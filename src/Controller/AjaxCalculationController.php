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

use App\Interfaces\RoleInterface;
use App\Service\CalculationService;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Controller for calculation XMLHttpRequest (Ajax) calls.
 */
#[AsController]
#[Route(path: '/ajax')]
#[IsGranted(RoleInterface::ROLE_USER)]
class AjaxCalculationController extends AbstractController
{
    /**
     * Return the edit item dialog template.
     */
    #[Route(path: '/dialog/item', name: 'ajax_dialog_item', methods: Request::METHOD_GET)]
    public function itemDialog(Request $request): JsonResponse
    {
        return $this->renderDialog($request, 'dialog/dialog_edit_item.html.twig');
    }

    /**
     * Return the edit task dialog template.
     */
    #[Route(path: '/dialog/task', name: 'ajax_dialog_task', methods: Request::METHOD_GET)]
    public function taskDialog(Request $request): JsonResponse
    {
        return $this->renderDialog($request, 'dialog/dialog_edit_task.html.twig');
    }

    /**
     * Update the calculation's totals.
     */
    #[Route(path: '/update', name: 'ajax_update', methods: Request::METHOD_POST)]
    public function update(Request $request, CalculationService $service, LoggerInterface $logger): JsonResponse
    {
        if (($response = $this->checkAjaxCall($request)) instanceof JsonResponse) {
            return $response;
        }

        try {
            $source = $this->getRequestAll($request, 'calculation');
            $parameters = $service->createGroupsFromData($source);
            if (!$parameters['result']) {
                return $this->json($parameters);
            }
            $parameters['min_margin'] = $service->getMinMargin();
            if ($this->getRequestBoolean($request, 'adjust') && $parameters['overall_below']) {
                $service->adjustUserMargin($parameters);
            }
            $body = $this->renderView('calculation/calculation_ajax_totals.html.twig', $parameters);
            $result = [
                'result' => true,
                'body' => $body,
                'user_margin' => $parameters['user_margin'] ?? 0,
                'overall_margin' => $parameters['overall_margin'],
                'overall_total' => $parameters['overall_total'],
                'overall_below' => $parameters['overall_below'],
            ];

            return $this->json($result);
        } catch (\Exception $e) {
            $message = $this->trans('calculation.edit.error.update_total');
            $context = $this->getExceptionContext($e);
            $logger->error($message, $context);

            return $this->jsonException($e, $message);
        }
    }

    /**
     * Checks if the given request is a XMLHttpRequest (ajax) call.
     *
     * @return JsonResponse|null null if the request is a XMLHttpRequest call, a JSON error response otherwise
     */
    private function checkAjaxCall(Request $request): ?JsonResponse
    {
        if (!$request->isXmlHttpRequest()) {
            return $this->jsonFalse([
                'message' => $this->trans('errors.invalid_request'),
            ]);
        }

        return null;
    }

    private function renderDialog(Request $request, string $view): JsonResponse
    {
        if (($response = $this->checkAjaxCall($request)) instanceof JsonResponse) {
            return $response;
        }

        return $this->json($this->renderView($view));
    }
}
