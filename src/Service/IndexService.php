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
use App\Interfaces\EntityInterface;
use App\Interfaces\RoleInterface;
use App\Model\MonthChartData;
use App\Model\StateChartData;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Events;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

/**
 * Service to cache computed values for the home page (index page).
 */
#[AsDoctrineListener(Events::onFlush)]
class IndexService
{
    /** @var array<string, class-string<EntityInterface>> */
    public const array CATALOG = [
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
        private readonly Security $security,
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
     * @return array<key-of<self::CATALOG>, int>
     */
    public function getCatalog(): array
    {
        $catalog = $this->loadData(
            'catalog',
            fn (): array => \array_replace(self::CATALOG, \array_map($this->countEntities(...), self::CATALOG))
        );
        if (!$this->security->isGranted(RoleInterface::ROLE_WEBMASTER)) {
            $catalog['user'] = $this->countFilteredUsers();
        }

        return $catalog;
    }

    /**
     * Gets the number of entities for the given entity key.
     *
     * @phpstan-param key-of<self::CATALOG> $key the entity key
     */
    public function getEntitiesCount(string $key): int
    {
        return $this->getCatalog()[$key];
    }

    /**
     * Gets the last created or modified calculations.
     *
     * @param int   $maxResults the maximum number of calculations to retrieve (the "limit")
     * @param ?User $user       if not null, returns the user's calculations
     */
    public function getLastCalculations(int $maxResults, ?User $user = null): array
    {
        return $this->loadData(
            \sprintf('last.%d.%d', $maxResults, $user?->getId() ?? 0),
            fn (): array => $this->manager->getRepository(Calculation::class)->getLastCalculations($maxResults, $user)
        );
    }

    /**
     * Gets the data used to display the calculations by month.
     *
     * @param int $maxResults the maximum number of results to retrieve (the "limit")
     */
    public function getMonthChartData(int $maxResults = 6): MonthChartData
    {
        return $this->loadData(
            \sprintf('month.%d', $maxResults),
            fn (): MonthChartData => $this->manager->getRepository(Calculation::class)->getMonthChartData($maxResults)
        );
    }

    /**
     * Gets the data used to display the calculations by state.
     */
    public function getStateChartData(): StateChartData
    {
        return $this->loadData(
            'state',
            fn (): StateChartData => $this->manager->getRepository(CalculationState::class)->getStateChartData()
        );
    }

    public function onFlush(OnFlushEventArgs $args): void
    {
        if ($this->isScheduledEntities($args)) {
            $this->clear();
        }
    }

    /**
     * @param class-string<EntityInterface> $className
     */
    private function countEntities(string $className): int
    {
        return $this->manager->getRepository($className)->count();
    }

    private function countFilteredUsers(): int
    {
        return $this->loadData(
            'users',
            fn (): int => $this->manager->getRepository(User::class)->enableWebmasterFilter()->count()
        );
    }

    private function isScheduledEntities(OnFlushEventArgs $args): bool
    {
        $unitOfWork = $args->getObjectManager()->getUnitOfWork();

        return [] !== $unitOfWork->getScheduledEntityInsertions()
            || [] !== $unitOfWork->getScheduledEntityDeletions()
            || [] !== $unitOfWork->getScheduledEntityUpdates();
    }

    /**
     * @template T
     *
     * @param callable(): T $callable
     *
     * @return T
     */
    private function loadData(string $key, callable $callable): mixed
    {
        return $this->cache->get(
            self::TAG_NAME . '.' . $key,
            static function (ItemInterface $item) use ($callable): mixed {
                $item->tag(self::TAG_NAME);

                return $callable();
            }
        );
    }
}
