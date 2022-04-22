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
use App\Service\UserService;
use App\Traits\MathTrait;
use App\Util\Utils;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * The index controller for home page.
 *
 * @author Laurent Muller
 */
class IndexController extends AbstractController
{
    use MathTrait;

    /**
     * The restriction query parameter.
     */
    final public const PARAM_RESTRICT = 'restrict';

    /**
     *  Display the home page.
     *
     * @Route("/", name="homepage")
     */
    public function invoke(Request $request, EntityManagerInterface $manager, UserService $service): Response
    {
        $application = $this->getApplication();
        $restrict = $this->getRestrict($request);
        $user = $restrict ? $this->getUser() : null;
        $application->updateCache();
        $service->updateCache();

        $parameters = [
            'min_margin' => $application->getMinMargin(),
            'calculations' => $this->getCalculations($manager, $application->getPanelCalculation(), $user),
        ];
        if ($restrict) {
            $parameters[self::PARAM_RESTRICT] = 1;
        }
        if ($application->isPanelState()) {
            $parameters['states'] = $this->getStates($manager);
        }
        if ($application->isPanelMonth()) {
            $parameters['months'] = $this->getMonths($manager);
        }
        if ($application->isPanelCatalog()) {
            $parameters['task_count'] = $this->count($manager, Task::class);
            $parameters['group_count'] = $this->count($manager, Group::class);
            $parameters['product_count'] = $this->count($manager, Product::class);
            $parameters['category_count'] = $this->count($manager, Category::class);
            $parameters['margin_count'] = $this->count($manager, GlobalMargin::class);
            $parameters['state_count'] = $this->count($manager, CalculationState::class);
        }

        // save
        if ($request->hasSession()) {
            $request->getSession()->set(self::PARAM_RESTRICT, $restrict);
        }

        return $this->renderForm('index/index.html.twig', $parameters);
    }

    /**
     * @psalm-param class-string<T> $className
     * @psalm-template T
     */
    private function count(EntityManagerInterface $manager, $className): int
    {
        // @phpstan-ignore-next-line
        return $manager->getRepository($className)->count([]);
    }

    private function getCalculations(EntityManagerInterface $manager, int $maxResults, ?UserInterface $user): array
    {
        return $manager->getRepository(Calculation::class)->getLastCalculations($maxResults, $user);
    }

    private function getMonths(EntityManagerInterface $manager): array
    {
        return $manager->getRepository(Calculation::class)->getByMonth();
    }

    private function getRestrict(Request $request): bool
    {
        $input = Utils::getRequestInputBag($request);
        if ($input->has(self::PARAM_RESTRICT)) {
            return $input->getBoolean(self::PARAM_RESTRICT);
        }
        if ($request->hasSession()) {
            return (bool) $request->getSession()->get(self::PARAM_RESTRICT, false);
        }

        return false;
    }

    private function getStates(EntityManagerInterface $manager): array
    {
        $states = $manager->getRepository(CalculationState::class)->getListCountCalculations();

        // add overall entry
        $count = \array_sum(\array_column($states, 'count'));
        $total = \array_sum(\array_column($states, 'total'));
        $items = \array_sum(\array_column($states, 'items'));
        $margin = $this->safeDivide($total, $items);

        $states[] = [
            'id' => 0,
            'color' => false,
            'code' => $this->trans('calculation.fields.total'),
            'count' => $count,
            'total' => $total,
            'margin' => $margin,
        ];

        return $states;
    }
}
