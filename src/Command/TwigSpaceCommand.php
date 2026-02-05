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
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

#[AsCommand(name: 'app:twig-space', description: 'Trim consecutive spaces in Twig templates.')]
class TwigSpaceCommand
{
    use WatchTrait;

    private const string PATTERN = '/(\S)( {2,})(\S)/';
    private const string REPLACEMENT = '$1 $3';

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
        $fullPath = Path::join($this->projectDir, $path);
        if (!$this->validateFullPath($io, $fullPath)) {
            return Command::INVALID;
        }

        if ($dryRun) {
            $io->title(\sprintf('Find consecutive spaces: "%s"', $fullPath));
        } else {
            $io->title(\sprintf('Trim consecutive spaces: "%s"', $fullPath));
        }

        $count = 0;
        $this->start();
        $finder = $this->createFinder($fullPath);
        foreach ($finder as $file) {
            $content = $file->getContents();
            if (!StringUtils::pregMatch(self::PATTERN, $content)) {
                continue;
            }
            ++$count;
            $io->text($file->getRelativePathname());
            $this->outputContent($io, $content);
            if ($dryRun) {
                continue;
            }
            if (!$this->updateContent($io, $file, $content)) {
                return Command::FAILURE;
            }
        }

        if (0 === $count) {
            $message = 'No template updated from "%1$s" directory. %3$s.';
        } elseif ($dryRun) {
            $message = 'Simulate updated %2$d template(s) successfully from "%1$s" directory. %3$s.';
        } else {
            $message = 'Updated %2$d template(s) successfully from "%1$s" directory. %3$s.';
        }
        $io->success(\sprintf($message, $path, $count, $this->stop()));

        return Command::SUCCESS;
    }

    private function createFinder(string $path): Finder
    {
        return Finder::create()
            ->ignoreUnreadableDirs()
            ->in($path)
            ->files()
            ->name('*.twig');
    }

    private function error(SymfonyStyle $io, string $message, string ...$values): false
    {
        $io->error(\sprintf($message, ...$values));

        return false;
    }

    private function formatLine(int $key, string $line, array $matches): string
    {
        /** @phpstan-var array{string, int} $match */
        foreach (\array_reverse($matches) as $match) {
            $offset = $match[1] + 1;
            $length = \strlen($match[0]) - 2;
            $replace = \sprintf('<bg=red>%s</>', \str_repeat('·', $length));
            $line = \substr_replace($line, $replace, $offset, $length);
        }

        return \sprintf('  - Line %3d, Count %d: %s', $key + 1, \count($matches), \trim($line));
    }

    private function outputContent(SymfonyStyle $io, string $content): void
    {
        $lines = StringUtils::splitLines($content);
        foreach ($lines as $key => $line) {
            if ('' !== $line && StringUtils::pregMatchAll(self::PATTERN, $line, $matches, \PREG_OFFSET_CAPTURE)) {
                $io->text($this->formatLine($key, $line, $matches[0]));
            }
        }
    }

    private function updateContent(SymfonyStyle $io, SplFileInfo $file, string $content): bool
    {
        $content = StringUtils::pregReplace(self::PATTERN, self::REPLACEMENT, $content);
        if (FileUtils::dumpFile($file->getPathname(), $content)) {
            return true;
        }

        return $this->error($io, 'Unable to set content of the template "%s".', $file->getRelativePathname());
    }

    private function validateFullPath(SymfonyStyle $io, string $fullPath): bool
    {
        if (!\file_exists($fullPath)) {
            return $this->error($io, 'Unable to find the template path: "%s".', $fullPath);
        }
        if (!\is_dir($fullPath)) {
            return $this->error($io, 'The template path "%s" is not a directory.', $fullPath);
        }

        return true;
    }

    private function validateInputPath(SymfonyStyle $io, string $path): bool
    {
        if (!StringUtils::isString($path)) {
            return $this->error($io, 'The templates path can no be empty.');
        }

        return true;
    }
}
