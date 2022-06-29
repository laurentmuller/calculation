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
use App\Util\Utils;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Controller to display the home page.
 */
#[AsController]
class IndexController extends AbstractController
{
    use MathTrait;

    /**
     * The restriction query parameter.
     */
    final public const PARAM_RESTRICT = 'restrict';

    /**
     * Constructor.
     */
    public function __construct(TranslatorInterface $translator, private readonly EntityManagerInterface $manager)
    {
        parent::__construct($translator);
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
        $application = $this->getApplication();
        $restrict = $this->getRestrict($request);
        $user = $restrict ? $this->getUser() : null;
        $application->updateCache();
        $service->updateCache();
        $parameters = [
            'min_margin' => $application->getMinMargin(),
            'calculations' => $this->getCalculations($application->getPanelCalculation(), $user),
        ];
        if ($restrict) {
            $parameters[self::PARAM_RESTRICT] = 1;
        }
        if ($application->isPanelState()) {
            $parameters['states'] = $this->getStates();
        }
        if ($application->isPanelMonth()) {
            $parameters['months'] = $this->getMonths();
        }
        if ($application->isPanelCatalog()) {
            $parameters['task_count'] = $this->count(Task::class);
            $parameters['group_count'] = $this->count(Group::class);
            $parameters['product_count'] = $this->count(Product::class);
            $parameters['category_count'] = $this->count(Category::class);
            $parameters['margin_count'] = $this->count(GlobalMargin::class);
            $parameters['state_count'] = $this->count(CalculationState::class);
        }
        // save
        if ($request->hasSession()) {
            $request->getSession()->set(self::PARAM_RESTRICT, $restrict);
        }

        return $this->renderForm('index/index.html.twig', $parameters);
    }

    /**
     * @template T of \App\Entity\AbstractEntity
     * @psalm-param class-string<T> $className
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

    private function getStates(): array
    {
        $states = $this->manager->getRepository(CalculationState::class)->getListCountCalculations();

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
