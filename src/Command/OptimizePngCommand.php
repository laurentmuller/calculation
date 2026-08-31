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

namespace App\Command;

use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Process\Process;

/**
 * Command to optimize PNG images.
 */
#[AsCommand(name: 'app:optimize-png', description: 'Optimize PNG images.')]
class OptimizePngCommand
{
    use WatchTrait;

    public function __construct(
        #[Autowire(param: 'kernel.project_dir')]
        private readonly string $projectDir
    ) {
    }

    public function __invoke(
        SymfonyStyle $io,
        #[Argument(description: 'The image directory. If not absolute, it is relative to the project directory.')]
        string $source,
        #[Option(description: 'The optimization level (0-7).', name: 'level', shortcut: 'l')]
        int $level = 7,
        #[Option(description: 'The path to the optipng binary.', name: 'binary', shortcut: 'b')]
        string $binary = '/usr/bin/optipng',
    ): int {
        $io->title(\sprintf('Optimize PNG images in "%s"', $source));

        $path = $this->getSourcePath($source);
        if (!\is_dir($path)) {
            return $this->error($io, 'The source path "%s" is not a directory.', $source);
        }

        if (!\is_executable($binary)) {
            return $this->error($io, 'The optipng binary "%s" is not executable.', $binary);
        }

        if (!\in_array($level, \range(0, 7), true)) {
            return $this->error($io, 'The optimization level must be between 0 and 7, "%d" given.', $level);
        }

        $error = 0;
        $updated = 0;
        $unchanged = 0;

        $this->start();
        $filesystem = new Filesystem();
        $tempDir = Path::join(\sys_get_temp_dir(), 'optimize-png');

        $io->writeln($tempDir);

        try {
            $filesystem->mkdir($tempDir);
            $finder = $this->createFinder($path);
            foreach ($finder as $file) {
                $source = $file->getPathname();
                $target = Path::join($tempDir, $file->getBasename());
                $io->writeln($file->getRelativePathname());

                try {
                    $filesystem->copy($source, $target);
                    $command = $this->createCommand($binary, $level, $target);
                    if (!$this->convert($io, $command)) {
                        ++$error;
                        continue;
                    }
                    if (\filesize($source) === \filesize($target)) {
                        ++$unchanged;
                        continue;
                    }
                    $filesystem->copy($target, $source);
                    ++$updated;
                } finally {
                    $filesystem->remove($target);
                }
            }

            $io->success(\sprintf(
                'Processed: %d, Updated: %d, Unchanged: %d, Error: %d. %s',
                $updated + $unchanged + $error,
                $updated,
                $unchanged,
                $error,
                $this->stop()
            ));
        } finally {
            $filesystem->remove($tempDir);
        }

        return Command::SUCCESS;
    }

    private function convert(SymfonyStyle $io, array $command): bool
    {
        $process = new Process($command);
        $process->run();
        if ($process->isSuccessful()) {
            return true;
        }
        $io->error($process->getErrorOutput());

        return false;
    }

    /**
     * @return string[]
     */
    private function createCommand(string $binary, int $level, string $file): array
    {
        return [
            $binary,
            \sprintf('-o%d', $level),
            '-strip',
            'all',
            $file,
        ];
    }

    private function createFinder(string $path): Finder
    {
        return Finder::create()
            ->ignoreUnreadableDirs()
            ->in($path)
            ->files()
            ->name('*.png');
    }

    private function error(SymfonyStyle $io, string $message, string|int ...$values): int
    {
        $io->error(\sprintf($message, ...$values));

        return Command::INVALID;
    }

    private function getSourcePath(string $source): string
    {
        return Path::isAbsolute($source) ? $source : Path::join($this->projectDir, $source);
    }
}
