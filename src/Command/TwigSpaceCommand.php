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

use App\Utils\FileUtils;
use App\Utils\StringUtils;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

#[AsCommand(name: 'app:twig-space', description: 'Replace consecutive spaces in Twig templates.')]
class TwigSpaceCommand
{
    use WatchTrait;

    private const PATTERN = '/(\S)([ ]{2,})(\S)/m';
    private const REPLACEMENT = '$1 $3';

    public function __construct(
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir
    ) {
    }

    public function __invoke(
        SymfonyStyle $io,
        #[Argument(description: 'The path, relative to the project directory, where to search templates for.', )]
        string $path = 'templates',
        #[Option(description: 'Run the command without making changes.', name: 'dry-run', shortcut: 'd')]
        bool $dryRun = false
    ): int {
        if (!$this->validateInputPath($io, $path)) {
            return Command::INVALID;
        }
        $fullPath = FileUtils::buildPath($this->projectDir, $path);
        if (!$this->validateFullPath($io, $fullPath)) {
            return Command::INVALID;
        }

        $count = 0;
        $this->start();
        $finder = $this->createFinder($fullPath);
        foreach ($finder as $file) {
            $content = $file->getContents();
            if (!StringUtils::pregMatch(self::PATTERN, $content)) {
                continue;
            }
            $io->text($file->getRelativePathname());
            $content = $this->updateContent($io, $dryRun, $file->getContents());
            if (!$dryRun && !$this->setContents($io, $file, $content)) {
                return Command::FAILURE;
            }
            ++$count;
        }

        if (0 === $count) {
            return $this->success(
                $io,
                'No template updated from directory "%s". %s.',
                $path,
                $this->stop()
            );
        }

        if ($dryRun) {
            return $this->success(
                $io,
                'Simulate updated %d template(s) successfully from "%s" directory. %s.',
                $count,
                $path,
                $this->stop()
            );
        }

        return $this->success(
            $io,
            'Updated %d template(s) successfully from "%s" directory. %s.',
            $count,
            $path,
            $this->stop()
        );
    }

    private function createFinder(string $fullPath): Finder
    {
        return Finder::create()
            ->ignoreUnreadableDirs()
            ->in($fullPath)
            ->files()
            ->name('*.twig');
    }

    private function setContents(SymfonyStyle $io, SplFileInfo $file, string $content): bool
    {
        if (!FileUtils::dumpFile($file, $content)) {
            $io->error(\sprintf('Unable to set content of the template "%s".', $file->getRelativePathname()));

            return false;
        }

        return true;
    }

    private function success(SymfonyStyle $io, string $message, string|int ...$parameters): int
    {
        $io->success(\sprintf($message, ...$parameters));

        return Command::SUCCESS;
    }

    private function updateContent(SymfonyStyle $io, bool $dryRun, string $content): string
    {
        $lines = \explode("\n", $content);
        foreach ($lines as $key => &$line) {
            if (!StringUtils::pregMatch(self::PATTERN, $line, $matches, \PREG_OFFSET_CAPTURE)) {
                continue;
            }
            $io->text(\sprintf('%4d:%-3d: %s', $key, $matches[1][1], \trim($line)));
            if ($dryRun) {
                continue;
            }
            $line = StringUtils::pregReplace(self::PATTERN, self::REPLACEMENT, $line);
        }

        return \implode("\n", $lines);
    }

    private function validateFullPath(SymfonyStyle $io, string $fullPath): bool
    {
        if (!FileUtils::exists($fullPath)) {
            $io->error(\sprintf('Unable to find the template path: "%s".', $fullPath));

            return false;
        }
        if (!FileUtils::isDir($fullPath)) {
            $io->error(\sprintf('The template path "%s" is not a directory.', $fullPath));

            return false;
        }

        return true;
    }

    private function validateInputPath(SymfonyStyle $io, string $path): bool
    {
        if (!StringUtils::isString($path)) {
            $io->error('The templates path can no be empty.');

            return false;
        }

        return true;
    }
}
