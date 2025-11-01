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

use App\Traits\ArrayTrait;
use App\Utils\StringUtils;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * Service for cache commands.
 */
class CacheService
{
    use ArrayTrait;

    public function __construct(
        private readonly CommandService $service,
        #[Target('calculation.cache')]
        private readonly CacheInterface $cache
    ) {
    }

    /**
     * Clear the cache.
     *
     * @throws \Exception if the running command fail
     */
    public function clear(): bool
    {
        $parameters = ['--all' => true];
        $result = $this->service->execute('cache:pool:clear', $parameters);

        return $result->isSuccess();
    }

    /**
     * Gets all available cache pools.
     *
     * @return array<string, string[]>
     */
    public function list(): array
    {
        return $this->cache->get(
            'cache.pools',
            fn (ItemInterface $item, bool &$save): array => $this->loadContent($save)
        );
    }

    /**
     * @return array<string, string[]>
     *
     * @throws \Exception
     */
    private function loadContent(bool &$save): array
    {
        $save = false;
        $result = $this->service->execute('cache:pool:list');
        if (!$result->isSuccess()) {
            return [];
        }
        $content = $this->parseContent($result->content);
        $save = true;

        return $content;
    }

    /**
     * @return array<string, string[]>
     */
    private function parseContent(string $content): array
    {
        $lines = StringUtils::splitLines(\trim($content), true);
        $callback = static fn (string $line): bool => !\str_starts_with($line, '-')
            && !\str_starts_with($line, 'Pool name');
        $lines = $this->getSorted($this->getFiltered(\array_map(trim(...), $lines), $callback));

        $results = [];
        foreach ($lines as $line) {
            /** @phpstan-var array{0: string, 1: string} $values */
            $values = \explode('.', $line, 2);
            $results[$values[0]][] = $values[1];
        }

        return $results;
    }
}
