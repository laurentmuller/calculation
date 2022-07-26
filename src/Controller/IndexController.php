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
use App\Traits\MathTrait;
use App\Traits\ParameterTrait;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Controller to display the home page.
 */
#[AsController]
class IndexController extends AbstractController
{
    use MathTrait;
    use ParameterTrait;

    /**
     * The restriction query parameter.
     */
    final public const PARAM_RESTRICT = 'restrict';

    /**
     * Constructor.
     */
    public function __construct(private readonly EntityManagerInterface $manager)
    {
    }

    /**
     *  Display the home page.
     *
     * @throws \Psr\Cache\InvalidArgumentException
     * @throws \Psr\Container\ContainerExceptionInterface
     */
    #[Route(path: '/', name: 'homepage')]
    public function invoke(Request $request): Response
    {
        $service = $this->getUserService();
        $application = $service->getApplication();
        $restrict = $this->getParamBoolean($request, self::PARAM_RESTRICT);
        $user = $restrict ? $this->getUser() : null;
        $application->updateCache();
        $service->updateCache();

        $parameters = [
            'min_margin' => $application->getMinMargin(),
            'calculations' => $this->getCalculations($service->getPanelCalculation(), $user),
        ];
        if ($restrict) {
            $parameters[self::PARAM_RESTRICT] = 1;
        }
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

        $response = $this->renderForm('index/index.html.twig', $parameters);
        $path = $this->getParameterString('cookie_path');
        $this->setCookie($response, self::PARAM_RESTRICT, $restrict, '', $path);

        return $response;
    }

    protected function getSessionKey(string $key): string
    {
        return self::PARAM_RESTRICT === $key ? \strtoupper($key) : $key;
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
        return $this->manager->getRepository(Calculation::class)->getLastCalculations($maxResults, $user);
    }

    private function getMonths(): array
    {
        return $this->manager->getRepository(Calculation::class)->getByMonth();
    }

    private function getStates(): array
    {
        $results = $this->manager->getRepository(CalculationState::class)->getCalculations();

        // add overall entry
        $count = \array_sum(\array_column($results, 'count'));
        $total = \array_sum(\array_column($results, 'total'));
        $items = \array_sum(\array_column($results, 'items'));
        $margin = $this->safeDivide($total, $items);

        $results[] = [
            'id' => 0,
            'code' => $this->trans('calculation.fields.total'),
            'color' => false,
            'count' => $count,
            'total' => $total,
            'margin' => $margin,
        ];

        return $results;
    }
}
