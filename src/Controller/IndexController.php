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

use App\Entity\Calculation;
use App\Entity\CalculationState;
use App\Entity\Category;
use App\Entity\GlobalMargin;
use App\Entity\Group;
use App\Entity\Product;
use App\Entity\Task;
use App\Enums\TableView;
use App\Form\Parameters\AbstractParametersType;
use App\Interfaces\PropertyServiceInterface;
use App\Interfaces\RoleInterface;
use App\Interfaces\TableInterface;
use App\Repository\CalculationRepository;
use App\Repository\CalculationStateRepository;
use App\Traits\ArrayTrait;
use App\Traits\MathTrait;
use App\Traits\ParameterTrait;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Controller to display the home page.
 */
#[AsController]
#[IsGranted(RoleInterface::ROLE_USER)]
class IndexController extends AbstractController
{
    use ArrayTrait;
    use MathTrait;
    use ParameterTrait;

    /**
     * The custom view query parameter.
     */
    final public const PARAM_CUSTOM = 'custom';

    /**
     * The restriction query parameter.
     */
    final public const PARAM_RESTRICT = 'restrict';

    public function __construct(private readonly EntityManagerInterface $manager)
    {
    }

    /**
     * Hide the catalog panel.
     *
     * @psalm-api
     */
    #[Route(path: '/hide/catalog', name: 'homepage_hide_catalog', methods: Request::METHOD_POST)]
    public function hideCatalog(Request $request): JsonResponse
    {
        return $this->hidePanel($request, PropertyServiceInterface::P_PANEL_CATALOG, 'index.panel_catalog_hide_success');
    }

    /**
     * Hide the month panel.
     *
     * @psalm-api
     */
    #[Route(path: '/hide/month', name: 'homepage_hide_month', methods: Request::METHOD_POST)]
    public function hideMonth(Request $request): JsonResponse
    {
        return $this->hidePanel($request, PropertyServiceInterface::P_PANEL_MONTH, 'index.panel_month_hide_success');
    }

    /**
     * Hide the state panel.
     *
     * @psalm-api
     */
    #[Route(path: '/hide/state', name: 'homepage_hide_state', methods: Request::METHOD_POST)]
    public function hideState(Request $request): JsonResponse
    {
        return $this->hidePanel($request, PropertyServiceInterface::P_PANEL_STATE, 'index.panel_state_hide_success');
    }

    /**
     * Display the home page.
     *
     * @throws \Exception
     */
    #[Route(path: '/', name: self::HOME_PAGE, methods: Request::METHOD_GET)]
    public function index(
        Request $request,
        #[MapQueryParameter]
        bool $restrict = null,
        #[MapQueryParameter]
        bool $custom = null
    ): Response {
        $service = $this->getUserService();
        $restrict ??= $this->getCookieBoolean($request, self::PARAM_RESTRICT);
        if (null === $custom) {
            $view = $this->getCookieEnum($request, TableInterface::PARAM_VIEW, $service->getDisplayMode());
        } else {
            $view = $custom ? TableView::CUSTOM : TableView::TABLE;
            $service->setProperty(PropertyServiceInterface::P_DISPLAY_MODE, $view);
        }

        $user = $restrict ? $this->getUser() : null;
        $parameters = [
            'min_margin' => $this->getMinMargin(),
            'calculations' => $this->getCalculations($service->getPanelCalculation(), $user),
            'calculations_range' => AbstractParametersType::getCalculationRange(),
            self::PARAM_CUSTOM => TableView::CUSTOM === $view,
            self::PARAM_RESTRICT => $restrict,
        ];
        if ($service->isPanelState()) {
            $parameters['states'] = $this->getStates();
        }
        if ($service->isPanelMonth()) {
            $parameters['months'] = $this->getMonths();
        }
        if ($service->isPanelCatalog()) {
            $parameters['product_count'] = $this->count(Product::class);
            $parameters['task_count'] = $this->count(Task::class);
            $parameters['category_count'] = $this->count(Category::class);
            $parameters['group_count'] = $this->count(Group::class);
            $parameters['state_count'] = $this->count(CalculationState::class);
            $parameters['margin_count'] = $this->count(GlobalMargin::class);
        }

        $path = $this->getCookiePath();
        $response = $this->render('index/index.html.twig', $parameters);
        $this->updateCookie($response, self::PARAM_RESTRICT, $restrict, path: $path);
        $this->updateCookie($response, TableInterface::PARAM_VIEW, $view, path: $path);

        return $response;
    }

    /**
     * Update the numbers of displayed calculations.
     *
     * @psalm-api
     */
    #[Route(path: '/update/count', name: 'homepage_calculation', methods: Request::METHOD_POST)]
    public function updateCalculation(Request $request): JsonResponse
    {
        $this->checkAjaxRequest($request);
        $service = $this->getUserService();
        $default = $service->getPanelCalculation();
        $count = $this->getRequestInt($request, 'count', $default);
        if ($default !== $count) {
            $service->setProperty(PropertyServiceInterface::P_PANEL_CALCULATION, $count);
        }

        return $this->sendJsonMessage('index.panel_table_count_success');
    }

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

    /**
     * @psalm-param class-string $className
     */
    private function count($className): int
    {
        return $this->manager->getRepository($className)->count([]);
    }

    private function getCalculations(int $maxResults, ?UserInterface $user): array
    {
        /** @psalm-var CalculationRepository $repository */
        $repository = $this->manager->getRepository(Calculation::class);

        return $repository->getLastCalculations($maxResults, $user);
    }

    /**
     * @throws \Exception
     */
    private function getMonths(): array
    {
        /** @psalm-var CalculationRepository $repository */
        $repository = $this->manager->getRepository(Calculation::class);

        return $repository->getByMonth();
    }

    private function getStates(): array
    {
        /** @psalm-var CalculationStateRepository $repository */
        $repository = $this->manager->getRepository(CalculationState::class);

        $results = $repository->getCalculations();
        $count = $this->getColumnSum($results, 'count');
        $total = $this->getColumnSum($results, 'total');
        $items = $this->getColumnSum($results, 'items');
        $margin = $this->safeDivide($total, $items);

        $results[] = [
            'id' => 0,
            'code' => $this->trans('calculation.fields.total'),
            'color' => false,
            'count' => $count,
            'total' => $total,
            'margin_percent' => $margin,
        ];

        return $results;
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
