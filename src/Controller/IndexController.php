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

use App\Attribute\GetRoute;
use App\Attribute\IndexRoute;
use App\Attribute\PostRoute;
use App\Enums\TableView;
use App\Form\Parameters\AbstractParametersType;
use App\Interfaces\PropertyServiceInterface;
use App\Interfaces\RoleInterface;
use App\Interfaces\TableInterface;
use App\Service\IndexService;
use App\Traits\ParameterTrait;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Controller to display the home page.
 */
#[Route(path: '/', name: self::HOME_PAGE)]
#[IsGranted(RoleInterface::ROLE_USER)]
class IndexController extends AbstractController
{
    use ParameterTrait;

    /**
     * The custom view query parameter.
     */
    final public const PARAM_CUSTOM = 'custom';

    /**
     * The restriction query parameter.
     */
    final public const PARAM_RESTRICT = 'restrict';

    /**
     * Hide the catalog panel.
     */
    #[PostRoute(path: '/hide/catalog', name: '_hide_catalog')]
    public function hideCatalog(Request $request): JsonResponse
    {
        return $this->hidePanel($request, PropertyServiceInterface::P_PANEL_CATALOG, 'index.panel_catalog_hide_success');
    }

    /**
     * Hide the month panel.
     */
    #[PostRoute(path: '/hide/month', name: '_hide_month')]
    public function hideMonth(Request $request): JsonResponse
    {
        return $this->hidePanel($request, PropertyServiceInterface::P_PANEL_MONTH, 'index.panel_month_hide_success');
    }

    /**
     * Hide the state panel.
     */
    #[PostRoute(path: '/hide/state', name: '_hide_state')]
    public function hideState(Request $request): JsonResponse
    {
        return $this->hidePanel($request, PropertyServiceInterface::P_PANEL_STATE, 'index.panel_state_hide_success');
    }

    /**
     * Display the home page.
     *
     * @throws \Exception
     */
    #[GetRoute(path: IndexRoute::PATH, name: '')]
    public function index(
        Request $request,
        IndexService $indexService,
        #[MapQueryParameter]
        ?bool $restrict = null,
        #[MapQueryParameter]
        ?bool $custom = null
    ): Response {
        $userService = $this->getUserService();
        $restrict ??= $this->getCookieBoolean($request, self::PARAM_RESTRICT);
        if (null === $custom) {
            $view = $this->getCookieEnum($request, TableInterface::PARAM_VIEW, $userService->getDisplayMode());
        } else {
            $view = $custom ? TableView::CUSTOM : TableView::TABLE;
            $userService->setProperty(PropertyServiceInterface::P_DISPLAY_MODE, $view);
        }

        $maxResults = $userService->getCalculations();
        $user = $restrict ? $this->getUser() : null;
        $calculations = $indexService->getLastCalculations($maxResults, $user);

        $parameters = [
            'min_margin' => $this->getMinMargin(),
            'calculations' => $calculations,
            'calculations_range' => AbstractParametersType::getCalculationRange(),
            self::PARAM_CUSTOM => TableView::CUSTOM === $view,
            self::PARAM_RESTRICT => $restrict,
        ];

        if ($userService->isPanelState()) {
            $parameters['states'] = $indexService->getCalculationByStates();
        }
        if ($userService->isPanelMonth()) {
            $parameters['months'] = $indexService->getCalculationByMonths();
        }
        if ($userService->isPanelCatalog()) {
            $parameters['catalog'] = $indexService->getCatalog();
        }

        $response = $this->render('index/index.html.twig', $parameters);
        $this->updateCookie($response, self::PARAM_RESTRICT, $restrict);
        $this->updateCookie($response, TableInterface::PARAM_VIEW, $view);

        return $response;
    }

    /**
     * Update the numbers of displayed calculations.
     */
    #[PostRoute(path: '/update/count', name: '_calculation')]
    public function updateCalculation(Request $request): JsonResponse
    {
        $this->checkAjaxRequest($request);
        $service = $this->getUserService();
        $default = $service->getCalculations();
        $count = $this->getRequestInt($request, 'count', $default);
        if ($default !== $count) {
            $service->setProperty(PropertyServiceInterface::P_CALCULATIONS, $count);
        }

        return $this->sendJsonMessage('index.panel_table_count_success');
    }

    #[\Override]
    protected function getSessionKey(string $key): string
    {
        return match ($key) {
            self::PARAM_CUSTOM,
            self::PARAM_RESTRICT => \strtoupper($key),
            default => $key,
        };
    }

    private function checkAjaxRequest(Request $request): void
    {
        if (!$request->isXmlHttpRequest()) {
            throw new BadRequestHttpException('Invalid request format.');
        }
    }

    private function hidePanel(Request $request, string $key, string $id): JsonResponse
    {
        $this->checkAjaxRequest($request);
        $this->getUserService()->setProperty($key, false);

        return $this->sendJsonMessage($id);
    }

    private function sendJsonMessage(string $id): JsonResponse
    {
        return $this->json($this->trans($id));
    }
}
