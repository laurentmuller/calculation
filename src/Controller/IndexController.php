<?php
/*
 * This file is part of the Calculation package.
 *
 * (c) bibi.nu. <bibi@bibi.nu>
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
use Doctrine\ORM\EntityManagerInterface;
use SlopeIt\BreadcrumbBundle\Annotation\Breadcrumb;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * The index controller for home page.
 *
 * @author Laurent Muller
 */
class IndexController extends AbstractController
{
    use MathTrait;

    /**
     * Display the home page.
     *
     * @Route("/", name="homepage")
     * @Breadcrumb({
     *     {"label" = "index.title"}
     * })
     */
    public function invoke(EntityManagerInterface $manager): Response
    {
        $application = $this->getApplication();
        $parameters = [
            'min_margin' => $application->getMinMargin(),
            'calculations' => $this->getCalculations($manager, $application->getPanelCalculation()),
        ];
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

    private function getCalculations(EntityManagerInterface $manager, int $maxResults): array
    {
        // @phpstan-ignore-next-line
        return $manager->getRepository(Calculation::class)->getLastCalculations($maxResults);
    }

    private function getMonths(EntityManagerInterface $manager): array
    {
        // @phpstan-ignore-next-line
        return $manager->getRepository(Calculation::class)->getByMonth();
    }

    private function getStates(EntityManagerInterface $manager): array
    {
        // @phpstan-ignore-next-line
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
