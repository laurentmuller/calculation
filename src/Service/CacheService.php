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
use Psr\Cache\InvalidArgumentException;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * Service for cache commands.
 */
class CacheService
{
    use ArrayTrait;

    public function __construct(
        private readonly CommandService $service,
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
        $parameters = [
            '--all' => true,
            '--env' => $this->getEnvironment(),
        ];
        $result = $this->service->execute('cache:pool:clear', $parameters);

        return $result->isSuccess();
    }

    /**
     * Gets all available cache pools.
     *
     * @throws InvalidArgumentException
     */
    public function list(): array
    {
        return $this->cache->get('cache.pools', function (): array {
            $parameters = ['--env' => $this->getEnvironment()];
            $result = $this->service->execute('cache:pool:list', $parameters);
            if (!$result->isSuccess()) {
                return [];
            }

            return $this->parseContent($result->content);
        });
    }

    private function getEnvironment(): string
    {
        return $this->service->getEnvironment();
    }

    private function parseContent(string $content): array
    {
        /** @psalm-var string[] $lines */
        $lines = \preg_split('/$\R?^/m', $content, flags: \PREG_SPLIT_NO_EMPTY);
        $callback = static fn (string $line): bool => !\str_starts_with($line, '-')
            && !\str_starts_with($line, 'Pool name');

        return $this->getSorted($this->getFiltered(\array_map('trim', $lines), $callback));
    }
}
