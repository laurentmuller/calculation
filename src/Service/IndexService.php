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

use App\Constant\CacheAttributes;
use App\Entity\Calculation;
use App\Entity\CalculationState;
use App\Entity\Category;
use App\Entity\GlobalMargin;
use App\Entity\Group;
use App\Entity\Product;
use App\Entity\Task;
use App\Entity\User;
use App\Model\CalculationsMonth;
use App\Model\CalculationsState;
use App\Traits\CacheKeyTrait;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Events;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\NamespacedPoolInterface;

/**
 * Service to cache computed values for the home page (index page).
 */
#[AsDoctrineListener(Events::onFlush)]
class IndexService
{
    use CacheKeyTrait;

    private const array CATALOG = [
        'user' => User::class,
        'task' => Task::class,
        'group' => Group::class,
        'product' => Product::class,
        'category' => Category::class,
        'calculation' => Calculation::class,
        'globalMargin' => GlobalMargin::class,
        'calculationState' => CalculationState::class,
    ];

    private readonly CacheItemPoolInterface&CacheInterface&NamespacedPoolInterface $cache;

    /** @noinspection PhpFieldAssignmentTypeMismatchInspection */
    public function __construct(
        private readonly EntityManagerInterface $manager,
        #[Target(CacheAttributes::CACHE_PARAMETERS)]
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
            \sprintf('index.calculations.months.%d', $maxResults),
            fn (): CalculationsMonth => $this->loadCalculationsByMonths($maxResults)
        );
    }

    /**
     * Gets calculations grouped by state.
     */
    public function getCalculationByStates(): CalculationsState
    {
        return $this->cache->get('index.calculations.states', $this->loadCalculationsByStates(...));
    }

    /**
     * Gets the number of entities.
     *
     * @return array<string, int>
     */
    public function getCatalog(): array
    {
        return $this->cache->get('index.catalog', $this->loadCatalog(...));
    }

    /**
     * Gets the last created or modified calculations.
     *
     * @param int            $maxResults the maximum number of calculations to retrieve (the "limit")
     * @param ?UserInterface $user       if not null, returns the user's calculations
     */
    public function getLastCalculations(int $maxResults, ?UserInterface $user = null): array
    {
        $id = $this->cleanKey($user?->getUserIdentifier() ?? '--all--');
        $key = \sprintf('index.calculations.last.%d.%s', $maxResults, $id);

        return $this->cache->get($key, fn (): array => $this->loadLastCalculations($maxResults, $user));
    }

    public function onFlush(OnFlushEventArgs $args): void
    {
        if ($this->isScheduledEntities($args)) {
            $this->clear();
        }
    }

    /**
     * @param class-string $className
     */
    private function countEntities(string $className): int
    {
        return $this->manager->getRepository($className)->count();
    }

    private function isScheduledEntities(OnFlushEventArgs $args): bool
    {
        $unitOfWork = $args->getObjectManager()->getUnitOfWork();

        return [] !== $unitOfWork->getScheduledEntityInsertions()
            || [] !== $unitOfWork->getScheduledEntityDeletions()
            || [] !== $unitOfWork->getScheduledEntityUpdates();
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

    /**
     * @return array<string, int>
     */
    private function loadCatalog(): array
    {
        return \array_replace(self::CATALOG, \array_map($this->countEntities(...), self::CATALOG));
    }

    private function loadLastCalculations(int $maxResults, ?UserInterface $user): array
    {
        return $this->manager->getRepository(Calculation::class)
            ->getLastCalculations($maxResults, $user);
    }
}
