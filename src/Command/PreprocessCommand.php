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
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

#[AsCommand(name: 'app:preprocess', description: 'Add the preprocess tag to CSS and JS files.')]
class PreprocessCommand
{
    use WatchTrait;

    private const array PATTERNS = ['*.js', '*.css'];
    private const string PREPROCESS_TAG = "/**! compile */\n";

    public function __construct(
        #[Autowire(param: 'kernel.project_dir')]
        private readonly string $projectDir
    ) {
    }

    public function __invoke(
        SymfonyStyle $io,
        #[Argument(description: 'The path, relative to the project directory, where to search in.')]
        string $path = 'public',
        #[Option(description: 'Remove existing preprocess tag.', name: 'remove', shortcut: 'r')]
        bool $remove = false,
        #[Option(description: 'Simulate update without modify files.', name: 'dry-run', shortcut: 'd')]
        bool $dryRun = false
    ): int {
        $fullPath = Path::join($this->projectDir, $path);
        $finder = $this->createFinder($fullPath);
        if (!$finder->hasResults()) {
            $io->note(\sprintf('No file found in directory "%s".', $path));

            return Command::SUCCESS;
        }

        $skipped = 0;
        $updated = 0;
        $this->start();
        $io->block(\sprintf('Update files in directory: "%s"', $path));
        foreach ($io->progressIterate($finder) as $file) {
            /** @var SplFileInfo $file */
            $content = $file->getContents();
            if ($remove) {
                if (!\str_contains($content, self::PREPROCESS_TAG)) {
                    ++$skipped;
                    continue;
                }
                if (!$dryRun) {
                    $content = \str_replace(self::PREPROCESS_TAG, '', $content);
                    $this->setContents($file, $content);
                }
                ++$updated;
                continue;
            }

            if (\str_contains($content, self::PREPROCESS_TAG)) {
                ++$skipped;
                continue;
            }
            if (!$dryRun) {
                $content = self::PREPROCESS_TAG . $content;
                $this->setContents($file, $content);
            }
            ++$updated;
        }

        $message = \sprintf(
            'Updated: %d, Skipped: %d, Total: %d, %s.',
            $updated,
            $skipped,
            $updated + $skipped,
            $this->stop()
        );
        if ($dryRun) {
            $message .= "\nThe update was simulated without changing the content of the files.";
        }
        $io->success($message);

        return Command::SUCCESS;
    }

    private function createFinder(string $path): Finder
    {
        return Finder::create()
            ->in($path)
            ->name(self::PATTERNS)
            ->files();
    }

    private function setContents(SplFileInfo $file, string $content): void
    {
        \file_put_contents($file->getRealPath(), $content);
    }
}
