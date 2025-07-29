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

namespace App\Service;

use App\Entity\Calculation;
use App\Entity\CalculationState;
use App\Entity\Category;
use App\Entity\GlobalMargin;
use App\Entity\Group;
use App\Entity\Product;
use App\Entity\Task;
use App\Interfaces\DisableListenerInterface;
use App\Interfaces\EntityInterface;
use App\Model\CalculationsMonth;
use App\Model\CalculationsState;
use App\Traits\CacheKeyTrait;
use App\Traits\DisableListenerTrait;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Events;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\NamespacedPoolInterface;

/**
 * Service to cache computed values for the home page (index page).
 */
#[AsDoctrineListener(Events::postFlush)]
class IndexService implements DisableListenerInterface
{
    use CacheKeyTrait;
    use DisableListenerTrait;

    private CacheItemPoolInterface&CacheInterface&NamespacedPoolInterface $cache;

    /** @noinspection PhpFieldAssignmentTypeMismatchInspection */
    public function __construct(
        private readonly EntityManagerInterface $manager,
        #[Target('calculation.application')]
        CacheItemPoolInterface&CacheInterface&NamespacedPoolInterface $cache
    ) {
        $this->cache = $cache->withSubNamespace('counters');
    }

    /**
     * Clear this cache.
     */
    public function clear(): void
    {
        $this->cache->clear();
    }

    /**
     * Gets the calculations grouped by years and months sorted by date in descending order.
     *
     * @param int $maxResults the maximum number of results to retrieve (the "limit")
     */
    public function getCalculationByMonths(int $maxResults = 6): CalculationsMonth
    {
        return $this->cache->get(
            \sprintf('calculations.months.%d', $maxResults),
            fn (): CalculationsMonth => $this->loadCalculationsByMonths($maxResults)
        );
    }

    /**
     * Gets calculations grouped by state.
     */
    public function getCalculationByStates(): CalculationsState
    {
        return $this->cache->get(
            'calculations.states',
            fn (): CalculationsState => $this->loadCalculationsByStates()
        );
    }

    /**
     * Gets the number entities.
     *
     * @return array<string, int>
     */
    public function getCatalog(): array
    {
        return [
            'task' => $this->count(Task::class),
            'group' => $this->count(Group::class),
            'product' => $this->count(Product::class),
            'category' => $this->count(Category::class),
            'globalMargin' => $this->count(GlobalMargin::class),
            'calculationState' => $this->count(CalculationState::class),
        ];
    }

    /**
     * Gets the last calculations.
     *
     * @param int            $maxResults the maximum number of calculations to retrieve (the "limit")
     * @param ?UserInterface $user       if not null, returns the user's last calculations
     */
    public function getLastCalculations(int $maxResults, ?UserInterface $user): array
    {
        $id = $this->cleanKey($user?->getUserIdentifier() ?? 'all');

        return $this->cache->get(
            \sprintf('calculations.last.%d.%s', $maxResults, $id),
            fn (): array => $this->loadLastCalculations($maxResults, $user)
        );
    }

    public function postFlush(): void
    {
        if ($this->isEnabled()) {
            $this->clear();
        }
    }

    /**
     * @param class-string<EntityInterface> $className
     */
    private function count(string $className): int
    {
        return $this->cache->get(
            $this->cleanKey($className),
            fn (): int => $this->manager->getRepository($className)->count()
        );
    }

    private function loadCalculationsByMonths(int $maxResults): CalculationsMonth
    {
        return $this->manager->getRepository(Calculation::class)
            ->getByMonth($maxResults);
    }

    private function loadCalculationsByStates(): CalculationsState
    {
        return $this->manager->getRepository(CalculationState::class)
            ->getCalculations();
    }

    private function loadLastCalculations(int $maxResults, ?UserInterface $user): array
    {
        return $this->manager->getRepository(Calculation::class)
            ->getLastCalculations($maxResults, $user);
    }
}
