<?php

/*
 * This file is part of the Calculation package.
 *
 * (c) bibi.nu <bibi@bibi.nu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/*
 * This file is part of the Calculation package.
 *
 * (c) bibi.nu <bibi@bibi.nu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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
use App\Traits\ArrayTrait;
use App\Traits\CacheKeyTrait;
use App\Traits\DisableListenerTrait;
use App\Traits\MathTrait;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Events;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\NamespacedPoolInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Service to cache computed entities for the home page (index page).
 */
#[AsDoctrineListener(Events::onFlush)]
class IndexService implements DisableListenerInterface
{
    use ArrayTrait;
    use CacheKeyTrait;
    use DisableListenerTrait;
    use MathTrait;

    private CacheItemPoolInterface&CacheInterface&NamespacedPoolInterface $cache;

    /** @noinspection PhpFieldAssignmentTypeMismatchInspection */
    public function __construct(
        private readonly EntityManagerInterface $manager,
        private readonly TranslatorInterface $translator,
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
    public function getCalculationByMonths(int $maxResults = 6): array
    {
        return $this->cache->get(
            \sprintf('calculations.months.%d', $maxResults),
            fn (): array => $this->loadCalculationsByMonths($maxResults)
        );
    }

    /**
     * Gets calculations grouped by state.
     */
    public function getCalculationByStates(): array
    {
        return $this->cache->get('calculations.states', fn (): array => $this->loadCalculationsByStates());
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

    public function onFlush(OnFlushEventArgs $args): void
    {
        if ($this->isEnabled() && $this->isScheduledEntities($args)) {
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

    private function isScheduledEntities(OnFlushEventArgs $args): bool
    {
        $unitOfWork = $args->getObjectManager()->getUnitOfWork();

        return [] !== $unitOfWork->getScheduledEntityInsertions()
            || [] !== $unitOfWork->getScheduledEntityDeletions()
            || [] !== $unitOfWork->getScheduledEntityUpdates();
    }

    private function loadCalculationsByMonths(int $maxResults): array
    {
        return $this->manager->getRepository(Calculation::class)
            ->getByMonth($maxResults);
    }

    private function loadCalculationsByStates(): array
    {
        $results = $this->manager->getRepository(CalculationState::class)
            ->getCalculations();
        $count = $this->getColumnSum($results, 'count');
        $total = $this->getColumnSum($results, 'total');
        $items = $this->getColumnSum($results, 'items');
        $margin = $this->safeDivide($total, $items);

        $results[] = [
            'id' => 0,
            'code' => $this->translator->trans('calculation.fields.total'),
            'color' => false,
            'count' => $count,
            'total' => $total,
            'margin_percent' => $margin,
        ];

        return $results;
    }

    private function loadLastCalculations(int $maxResults, ?UserInterface $user): array
    {
        return $this->manager->getRepository(Calculation::class)
            ->getLastCalculations($maxResults, $user);
    }
}
