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
use App\Attribute\GetRoute;
use App\Attribute\IndexRoute;
use App\Attribute\PostRoute;
use App\Enums\TableView;
use App\Interfaces\TableInterface;
use App\Model\IndexQuery;
use App\Parameter\HomePageParameter;
use App\Service\IndexService;
use App\Traits\ParameterTrait;
use App\Utils\StringUtils;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Controller to display the home page.
 */
#[ForUser]
#[Route(path: '/', name: self::HOME_PAGE)]
class IndexController extends AbstractController
{
    use ParameterTrait;

    /**
     * The custom view query parameter.
     */
    public const string PARAM_CUSTOM = 'custom';

    /**
     * The restriction query parameter.
     */
    public const string PARAM_RESTRICT = 'restrict';

    /**
     * The view parameter.
     */
    private const string PARAM_VIEW = TableInterface::PARAM_VIEW;

    /**
     * Gets the displayed calculations.
     */
    #[GetRoute(path: '/content', name: '_content')]
    public function getCalculations(
        IndexService $indexService,
        Request $request,
        #[MapQueryString]
        IndexQuery $query
    ): JsonResponse {
        $this->checkAjaxRequest($request);
        $parameters = $this->getParameters($indexService, $request, $query);
        $content = $this->renderContent($parameters);
        $response = $this->json($content);
        $this->saveParameters($response, $parameters);

        return $response;
    }

    /**
     * Hide the catalog panel.
     */
    #[PostRoute(path: '/hide/catalog', name: '_hide_catalog')]
    public function hideCatalog(Request $request): JsonResponse
    {
        $this->checkAjaxRequest($request);
        $parameters = $this->getUserParameters();
        $homePage = $parameters->getHomePage();
        if ($homePage->isPanelCatalog()) {
            $homePage->setPanelCatalog(false);
            $parameters->save();
        }

        return $this->json($this->trans('index.panel_catalog_hide_success'));
    }

    /**
     * Hide the month panel.
     */
    #[PostRoute(path: '/hide/month', name: '_hide_month')]
    public function hideMonth(Request $request): JsonResponse
    {
        $this->checkAjaxRequest($request);
        $parameters = $this->getUserParameters();
        $homePage = $parameters->getHomePage();
        if ($homePage->isPanelMonth()) {
            $homePage->setPanelMonth(false);
            $parameters->save();
        }

        return $this->json($this->trans('index.panel_month_hide_success'));
    }

    /**
     * Hide the state panel.
     */
    #[PostRoute(path: '/hide/state', name: '_hide_state')]
    public function hideState(Request $request): JsonResponse
    {
        $this->checkAjaxRequest($request);
        $parameters = $this->getUserParameters();
        $homePage = $parameters->getHomePage();
        if ($homePage->isPanelState()) {
            $homePage->setPanelState(false);
            $parameters->save();
        }

        return $this->json($this->trans('index.panel_state_hide_success'));
    }

    /**
     * Display the home page.
     */
    #[GetRoute(path: IndexRoute::PATH, name: '')]
    public function index(IndexService $indexService, Request $request, #[MapQueryString] IndexQuery $query): Response
    {
        $homePage = $this->getUserParameters()
            ->getHomePage();
        $parameters = $this->getParameters($indexService, $request, $query);
        if ($homePage->isPanelMonth()) {
            $parameters['months'] = $indexService->getCalculationByMonths();
        }
        if ($homePage->isPanelState()) {
            $parameters['states'] = $indexService->getCalculationByStates();
        }
        if ($homePage->isPanelCatalog()) {
            $parameters['catalog'] = $indexService->getCatalog();
        }
        $response = $this->render('index/index.html.twig', $parameters);
        $this->saveParameters($response, $parameters);

        return $response;
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

    private function getCount(?int $count): int
    {
        $parameters = $this->getUserParameters();
        $homePage = $parameters->getHomePage();
        $defaultCount = $homePage->getCalculations();
        if (null === $count || !\in_array($count, HomePageParameter::CALCULATIONS_RANGE, true)) {
            return $defaultCount;
        }
        if ($count !== $defaultCount) {
            $homePage->setCalculations($count);
            $parameters->save();
        }

        return $count;
    }

    private function getLastCalculations(IndexService $indexService, int $maxResults, bool $restrict): array
    {
        $user = $restrict ? $this->getUser() : null;

        return $indexService->getLastCalculations($maxResults, $user);
    }

    /**
     * @phpstan-return array{restrict: bool, view: TableView,  ...}
     */
    private function getParameters(IndexService $indexService, Request $request, IndexQuery $query): array
    {
        $view = $this->getTableView($request, $query->custom);
        $restrict = $query->restrict ?? $this->getCookieBoolean($request, self::PARAM_RESTRICT);
        $count = $this->getCount($query->count);
        $calculations = $this->getLastCalculations($indexService, $count, $restrict);

        return [
            'count' => $count,
            'calculations' => $calculations,
            'min_margin' => $this->getMinMargin(),
            'calculations_range' => HomePageParameter::CALCULATIONS_RANGE,
            self::PARAM_VIEW => $view,
            self::PARAM_RESTRICT => $restrict,
            self::PARAM_CUSTOM => TableView::CUSTOM === $view,
        ];
    }

    private function getTableView(Request $request, ?bool $custom): TableView
    {
        $parameters = $this->getUserParameters();
        $display = $parameters->getDisplay();
        $defaultMode = $display->getDisplayMode();
        if (null === $custom) {
            return $this->getCookieEnum($request, self::PARAM_VIEW, $defaultMode);
        }

        $newMode = $custom ? TableView::CUSTOM : TableView::TABLE;
        if ($newMode !== $defaultMode) {
            $display->setDisplayMode($newMode);
            $parameters->save();
        }

        return $newMode;
    }

    private function renderContent(array $parameters): string
    {
        $content = $this->renderView('index/_calculation_content.html.twig', $parameters);

        return StringUtils::pregReplace('/\s+/', ' ', $content);
    }

    /**
     * @phpstan-param array{restrict: bool, view: TableView,  ...} $parameters
     */
    private function saveParameters(Response $response, array $parameters): void
    {
        $this->updateCookie($response, self::PARAM_VIEW, $parameters[self::PARAM_VIEW]);
        $this->updateCookie($response, self::PARAM_RESTRICT, $parameters[self::PARAM_RESTRICT]);
    }
}
