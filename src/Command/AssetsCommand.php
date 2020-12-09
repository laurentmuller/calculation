<?php
/*
 * This file is part of the Calculation package.
 *
 * (c) bibi.nu. <bibi@bibi.nu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Abstrac command for assets.
 *
 * @author Laurent Muller
 */
abstract class AssetsCommand extends Command
{
    use FileTrait;

    /**
     * Executes the command.
     *
     * @return int 0 if everything went fine, or an exit code
     */
    abstract protected function doExecute(InputInterface $input, OutputInterface $output): int;

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // to output messages
        $this->io = new SymfonyStyle($input, $output);

        // delegate to subclass
        return $this->doExecute($input, $output);
    }

    /**
     * Gets the project directory.
     *
     * @return string|null the project directory, if found; null otherwise
     */
    protected function getProjectDir(): ?string
    {
        /** @var \Symfony\Bundle\FrameworkBundle\Console\Application|null $application */
        $application = $this->getApplication();
        if (!$application) {
            $this->writeError('The Application is not defined.');

            return null;
        }

        /** @var \Symfony\Component\HttpKernel\KernelInterface|null $kernel */
        $kernel = $application->getKernel();
        if (!$kernel) {
            $this->writeError('The Kernel is not defined.');

            return null;
        }

        return $kernel->getProjectDir();
    }

    /**
     * Gets the public directory.
     *
     * @return string|null the public directory, if found; null otherwise
     */
    protected function getPublicDir(): ?string
    {
        if ($projectDir = $this->getProjectDir()) {
            return \str_replace('\\', '/', $projectDir) . '/public';
        }

        return null;
    }
}
