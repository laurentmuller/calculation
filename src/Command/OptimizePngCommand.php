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
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
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
        #[Option(description: 'Run the command without replace images.', name: 'dry-run', shortcut: 'd')]
        bool $dryRun = false
    ): int {
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

        $io->title(\sprintf('Optimize PNG images in "%s"', $source));

        $finder = $this->createFinder($path);
        $count = $finder->count();
        if (0 === $count) {
            $io->warning(\sprintf('No PNG image found in directory "%s".', $source));

            return Command::SUCCESS;
        }

        $results = [
            'error' => 0,
            'updated' => 0,
            'unchanged' => 0,
            'source_size' => 0,
            'target_size' => 0,
        ];

        $this->start();
        $filesystem = new Filesystem();
        $tempDir = Path::join(\sys_get_temp_dir(), 'optimize-png');
        $progressBar = $this->createProgressBar($io, $count);

        try {
            $filesystem->mkdir($tempDir);
            /** @var SplFileInfo $file */
            foreach ($progressBar->iterate($finder, $count) as $file) {
                $source = $file->getPathname();
                $basename = $file->getBasename();
                $progressBar->setMessage($basename);
                $target = Path::join($tempDir, $basename);

                try {
                    $filesystem->copy($source, $target, true);
                    $arguments = $this->createArguments($binary, $level, $target);
                    if (!$this->convert($io, $arguments)) {
                        ++$results['error'];
                        continue;
                    }

                    $sourceSize = (int) \filesize($source);
                    $targetSize = (int) \filesize($target);
                    $results['source_size'] += $sourceSize;
                    $results['target_size'] += $targetSize;
                    if ($sourceSize === $targetSize) {
                        ++$results['unchanged'];
                        continue;
                    }

                    if (!$dryRun) {
                        $filesystem->copy($target, $source, true);
                    }
                    ++$results['updated'];
                } finally {
                    $filesystem->remove($target);
                }
            }
            $io->newLine(2);

            $this->updateResults($results);
            $message = \sprintf(
                "File(s) processed: %d, Updated: %d, Unchanged: %d, Error: %d, Size reduction: %s (%0.2f%%).\n%s.",
                $results['total'],
                $results['updated'],
                $results['unchanged'],
                $results['error'],
                $results['delta_size'],
                $results['delta_percent'],
                $this->stop()
            );
            if ($dryRun) {
                $message .= "\nDry-run mode enabled (no change applied).";
            }
            $io->success($message);
        } finally {
            $filesystem->remove($tempDir);
        }

        return Command::SUCCESS;
    }

    private function convert(SymfonyStyle $io, array $arguments): bool
    {
        $process = new Process($arguments);
        $process->run();
        if ($process->isSuccessful()) {
            return true;
        }

        $io->newLine(2);
        $io->error($process->getErrorOutput());

        return false;
    }

    /**
     * @return string[]
     */
    private function createArguments(string $binary, int $level, string $file): array
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

    private function createProgressBar(SymfonyStyle $io, int $count): ProgressBar
    {
        $progressBar = $io->createProgressBar($count);
        $progressBar->setFormat('%current%/%max% [%bar%] %message%');
        $progressBar->setMessage('Processing images');

        return $progressBar;
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

    private function updateResults(array &$results): void
    {
        $sourceSize = $results['source_size'];
        $deltaSize = $results['target_size'] - $sourceSize;
        $deltaPercent = $sourceSize > 0 ? (float) $deltaSize * 100.0 / (float) $sourceSize : 0.0;

        $results['delta_percent'] = $deltaPercent;
        $results['delta_size'] = $this->formatMemory($deltaSize);
        $results['total'] = $results['updated'] + $results['unchanged'] + $results['error'];
    }
}
