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

use App\Utils\StringUtils;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * Service for cache commands.
 */
readonly class CacheService
{
    public function __construct(
        private CommandService $service,
        #[Target('calculation.cache')]
        private CacheInterface $cache
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
            'cache.pools.list',
            fn (ItemInterface $item, bool &$save): array => $this->loadContent($save)
        );
    }

    /**
     * @return array<string, string[]>
     *
     * @throws \Exception if loading command fail
     */
    private function loadContent(bool &$save): array
    {
        $save = false;
        $result = $this->service->execute('cache:pool:list');
        if (!$result->isSuccess()) {
            return [];
        }
        $content = $this->parseContent($result->content);
        $save = [] !== $content;

        return $content;
    }

    /**
     * @return array<string, string[]>
     */
    private function parseContent(string $content): array
    {
        $lines = StringUtils::splitLines(\trim($content), true);
        $lines = \array_filter(\array_map(\trim(...), $lines));
        \sort($lines, \SORT_NATURAL);

        $results = [];
        foreach ($lines as $line) {
            if (\str_starts_with($line, '-') || \str_starts_with($line, 'Pool name')) {
                continue;
            }
            $values = \explode('.', $line, 2);
            $results[$values[0]][] = $values[1];
        }

        return $results;
    }
}
