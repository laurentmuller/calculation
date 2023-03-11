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

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Service to clear the cache.
 */
readonly class ClearCacheService
{
    public function __construct(private KernelInterface $kernel)
    {
    }

    /**
     * @throws \Exception if the running command fail
     */
    public function execute(): bool
    {
        $input = new ArrayInput([
            'command' => 'cache:pool:clear',
            'pools' => ['cache.global_clearer'],
            '--env' => $this->kernel->getEnvironment(),
        ]);
        $application = new Application($this->kernel);
        $application->setCatchExceptions(false);
        $application->setAutoExit(false);
        $result = $application->run($input);

        return Command::SUCCESS === $result;
    }
}
