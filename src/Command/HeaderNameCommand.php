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
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

#[AsCommand(name: 'app:header:name', description: 'Add the relative path, as comment, to the first line of files.')]
class HeaderNameCommand
{
    use WatchTrait;

    private const HEADERS_MAPPING = [
        'css' => '/* %path% */',
        'js' => '/* %path% */',
        'twig' => '{# %path% #}',
    ];

    private const NEW_LINE = "\n";

    private readonly string $projectDir;

    public function __construct(
        #[Autowire('%kernel.project_dir%')]
        string $projectDir
    ) {
        $this->projectDir = FileUtils::normalize($projectDir);
    }

    /**
     * @param string[]|null $patterns
     */
    public function __invoke(
        SymfonyStyle $io,
        #[Argument(description: 'The path, relative to the project directory, where to search in.')]
        string $path = '/',
        #[Option(description: 'The file extensions to search for or null for all css, js and twig files.')]
        ?array $patterns = null,
        #[Option(description: 'The depth search in the directory or -1 for all.')]
        int $depth = -1,
        #[Option(description: 'Simulate update without modify files.', name: 'dry-run', shortcut: 'd')]
        bool $dryRun = false
    ): int {
        $patterns ??= \array_keys(self::HEADERS_MAPPING);
        if (!$this->validatePatterns($io, $patterns)) {
            return Command::INVALID;
        }

        $fullPath = FileUtils::buildPath($this->projectDir, $path);
        $finder = $this->createFinder($fullPath, $patterns, $depth);
        if (!$this->hasResults($io, $finder, $path)) {
            return Command::SUCCESS;
        }

        $skip = 0;
        $update = 0;
        $files = [];
        $this->start();
        $io->block(\sprintf('Update files in directory: "%s"', $path));
        foreach ($io->progressIterate($finder) as $file) {
            $modelPath = $this->getModelPath($file);
            $modelHeader = $this->getModelHeader($file);
            $content = $this->updateContent($modelPath, $modelHeader, $file->getContents());
            if (false === $content) {
                ++$skip;
                continue;
            }
            if (!$dryRun) {
                $this->setContent($file, $content);
            }
            $files[] = $modelPath;
            ++$update;
        }

        if ([] !== $files) {
            $io->writeln('Files updated:');
            $io->listing($files);
        }

        $message = \sprintf('Updated: %d, Skipped: %d, %s.', $update, $skip, $this->stop());
        if ($dryRun) {
            $message .= "\nThe update was simulated without changing the content of the files.";
        }
        $io->success($message);

        return Command::SUCCESS;
    }

    /**
     * @param string[] $patterns
     */
    private function createFinder(string $path, array $patterns, int $depth): Finder
    {
        $finder = Finder::create()
            ->in($path)
            ->exclude(['vendor/', 'var/'])
            ->name($this->getPatterns($patterns))
            ->files();
        if ($depth >= 0) {
            $finder->depth(\sprintf('<= %d', $depth));
        }

        return $finder;
    }

    private function getModelHeader(SplFileInfo $file): string
    {
        return self::HEADERS_MAPPING[$file->getExtension()];
    }

    private function getModelPath(SplFileInfo $file): string
    {
        return Path::makeRelative($file->getPathname(), $this->projectDir);
    }

    /**
     * @param string[] $patterns
     *
     * @return string[]
     */
    private function getPatterns(array $patterns): array
    {
        return \array_map(static fn (string $value): string => \sprintf('*.%s', $value), $patterns);
    }

    private function hasResults(SymfonyStyle $io, Finder $finder, string $path): bool
    {
        if ($finder->hasResults()) {
            return true;
        }

        $io->note(\sprintf('No file found in directory "%s".', $path));

        return false;
    }

    private function setContent(SplFileInfo $file, string $content): void
    {
        \file_put_contents($file->getRealPath(), $content);
    }

    private function updateContent(string $path, string $header, string $content): string|false
    {
        if ('' === $content) {
            return false;
        }

        $lines = \explode(self::NEW_LINE, $content);
        $line = \str_replace('%path%', $path, $header);
        if ($lines[0] === $line) {
            return false;
        }

        $baseName = \basename($path);
        if (\str_contains($lines[0], $baseName)) {
            $lines[0] = $line;
        } else {
            \array_unshift($lines, $line);
        }

        return \implode(self::NEW_LINE, $lines);
    }

    /**
     * @param string[] $patterns
     */
    private function validatePatterns(SymfonyStyle $io, array $patterns): bool
    {
        $keys = \array_keys(self::HEADERS_MAPPING);
        $diff = \array_diff($patterns, $keys);
        if ([] !== $diff) {
            $io->error(\sprintf(
                'Invalid patterns: "%s". Allowed values: "%s".',
                \implode('", "', $diff),
                \implode('", "', $keys)
            ));

            return false;
        }

        return true;
    }
}
