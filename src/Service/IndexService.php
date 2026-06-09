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

use App\Constants\CacheAttributes;
use App\Entity\Calculation;
use App\Entity\CalculationState;
use App\Entity\Category;
use App\Entity\GlobalMargin;
use App\Entity\Group;
use App\Entity\Product;
use App\Entity\Task;
use App\Entity\User;
use App\Model\MonthChartData;
use App\Model\StateChartData;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Events;
use Psr\Cache\CacheException;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

/**
 * Service to cache computed values for the home page (index page).
 */
#[AsDoctrineListener(Events::onFlush)]
class IndexService
{
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

    private const string TAG_NAME = 'index.service';

    public function __construct(
        private readonly EntityManagerInterface $manager,
        #[Target(CacheAttributes::CACHE_PARAMETERS)]
        private readonly TagAwareCacheInterface $cache
    ) {
    }

    /**
     * Clear this cache.
     */
    public function clear(): void
    {
        $this->cache->invalidateTags([self::TAG_NAME]);
    }

    /**
     * Gets the number of entities.
     *
     * @return array<string, int>
     */
    public function getCatalog(): array
    {
        return $this->cache->get(
            'index.catalog',
            fn (ItemInterface $item): array => $this->loadCatalog($item)
        );
    }

    /**
     * Gets the last created or modified calculations.
     *
     * @param int   $maxResults the maximum number of calculations to retrieve (the "limit")
     * @param ?User $user       if not null, returns the user's calculations
     */
    public function getLastCalculations(int $maxResults, ?User $user = null): array
    {
        return $this->cache->get(
            \sprintf('index.calculations.last.%d.%d', $maxResults, $user?->getId() ?? 0),
            fn (ItemInterface $item): array => $this->loadLastCalculations($item, $maxResults, $user)
        );
    }

    /**
     * Gets the data used to display the calculations by month.
     *
     * @param int $maxResults the maximum number of results to retrieve (the "limit")
     */
    public function getMonthChartData(int $maxResults = 6): MonthChartData
    {
        return $this->cache->get(
            \sprintf('index.calculations.months.%d', $maxResults),
            fn (ItemInterface $item): MonthChartData => $this->loadMonthChartData($item, $maxResults)
        );
    }

    /**
     * Gets the data used to display the calculations by state.
     */
    public function getStateChartData(): StateChartData
    {
        return $this->cache->get(
            'index.calculations.states',
            fn (ItemInterface $item): StateChartData => $this->loadStateChartData($item)
        );
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

    /**
     * @return array<string, int>
     *
     * @throws CacheException
     */
    private function loadCatalog(ItemInterface $item): array
    {
        $item->tag(self::TAG_NAME);

        return \array_replace(self::CATALOG, \array_map($this->countEntities(...), self::CATALOG));
    }

    /**
     * @throws CacheException
     */
    private function loadLastCalculations(ItemInterface $item, int $maxResults, ?UserInterface $user): array
    {
        $item->tag(self::TAG_NAME);

        return $this->manager->getRepository(Calculation::class)
            ->getLastCalculations($maxResults, $user);
    }

    /**
     * @throws CacheException
     */
    private function loadMonthChartData(ItemInterface $item, int $maxResults): MonthChartData
    {
        $item->tag(self::TAG_NAME);

        return $this->manager->getRepository(Calculation::class)
            ->getMonthChartData($maxResults);
    }

    /**
     * @throws CacheException
     */
    private function loadStateChartData(ItemInterface $item): StateChartData
    {
        $item->tag(self::TAG_NAME);

        return $this->manager->getRepository(CalculationState::class)
            ->getStateChartData();
    }
}
