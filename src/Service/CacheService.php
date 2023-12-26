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
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Service for cache commands.
 */
class CacheService
{
    use ArrayTrait;

    private const CACHE_KEY = 'cache.pools';

    public function __construct(private readonly KernelInterface $kernel, private readonly RequestStack $requestStack)
    {
    }

    /**
     * Clear the cache.
     *
     * @throws \Exception if the running command fail
     */
    public function clear(): bool
    {
        $this->getSessions()->remove(self::CACHE_KEY);

        $input = new ArrayInput([
            'command' => 'cache:pool:clear',
            'pools' => ['cache.global_clearer'],
            '--env' => $this->kernel->getEnvironment(),
        ]);

        return $this->run($input);
    }

    /**
     * Gets all available cache pools.
     *
     * @throws \Exception if the running command fail
     */
    public function list(): array
    {
        $session = $this->getSessions();
        /** @psalm-var string[]|null $pools */
        $pools = $session->get(self::CACHE_KEY);
        if (\is_array($pools)) {
            return $pools;
        }

        $input = new ArrayInput([
            'command' => 'cache:pool:list',
            '--env' => $this->kernel->getEnvironment(),
        ]);
        $output = new BufferedOutput();
        if (!$this->run($input, $output)) {
            return [];
        }

        $pools = $this->parseContent($output->fetch());
        if ([] !== $pools) {
            $session->set(self::CACHE_KEY, $pools);
        }

        return $pools;
    }

    private function getSessions(): SessionInterface
    {
        return $this->requestStack->getSession();
    }

    private function parseContent(string $content): array
    {
        /** @psalm-var string[] $lines */
        $lines = \preg_split('/\r\n|\n|\r/', $content, flags: \PREG_SPLIT_NO_EMPTY);
        $callback = static fn (string $line): bool => !\str_starts_with($line, '-')
            && !\str_starts_with($line, 'Pool name');

        return $this->getSorted($this->getFiltered(\array_map('trim', $lines), $callback));
    }

    /**
     * @throws \Exception
     */
    private function run(InputInterface $input, OutputInterface $output = null): bool
    {
        $application = new Application($this->kernel);
        $application->setCatchExceptions(false);
        $application->setAutoExit(false);
        $result = $application->run($input, $output);
        unset($application);

        return Command::SUCCESS === $result;
    }
}
