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
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

/**
 * Command to add the relative path, as comment, to the first line of files.
 */
#[AsCommand(name: 'app:header:name', description: 'Add the relative path, as comment, to the first line of files.')]
class HeaderNameCommand extends Command
{
    private const ARGUMENT_PATH = 'path';
    private const HEADERS_MAPPING = [
        'css' => '/* %path% */',
        'js' => '/* %path% */',
        'twig' => '{# %path% #}',
    ];
    private const NEW_LINE = "\n";
    private const OPTION_DEPTH = 'depth';
    private const OPTION_DRY_RUN = 'dry-run';
    private const OPTION_PATTERN = 'pattern';

    public function __construct(
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument(
            self::ARGUMENT_PATH,
            InputOption::VALUE_REQUIRED,
            'The path, relative to the project directory, where to search in.',
            '/'
        );
        $this->addOption(
            self::OPTION_PATTERN,
            null,
            InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
            'The file extensions to search for.',
            \array_keys(self::HEADERS_MAPPING)
        );
        $this->addOption(
            self::OPTION_DEPTH,
            null,
            InputOption::VALUE_REQUIRED,
            'The depth search in the directory or -1 for all.',
            -1
        );
        $this->addOption(
            self::OPTION_DRY_RUN,
            'd',
            InputOption::VALUE_NONE,
            'Simulate update without modify files.'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $patterns = $io->getArrayOption(self::OPTION_PATTERN);
        if (!$this->validatePatterns($io, $patterns)) {
            return Command::INVALID;
        }

        $depth = $io->getIntOption(self::OPTION_DEPTH);
        $dryRun = $io->getBoolOption(self::OPTION_DRY_RUN);
        $path = $io->getStringArgument(self::ARGUMENT_PATH);
        $fullPath = FileUtils::buildPath($this->projectDir, $path);

        $finder = $this->createFinder($fullPath, $patterns, $depth);
        if (!$this->hasResults($io, $finder, $path)) {
            return Command::SUCCESS;
        }

        $skip = 0;
        $update = 0;
        $files = [];
        $io->writeln(\sprintf('Finding files in directory "%s":', $path));
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

        if ($dryRun) {
            $io->success(\sprintf('Simulate: Skipped %d, Updated %d.', $skip, $update));
        } else {
            $io->success(\sprintf('Skipped %d, Updated %d.', $skip, $update));
        }

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
        $root = FileUtils::normalize($this->projectDir);
        $path = FileUtils::normalize($file->getRealPath());

        return Path::makeRelative($path, $root);
    }

    /**
     * @param string[] $patterns
     *
     * @return string[]
     */
    private function getPatterns(array $patterns): array
    {
        return \array_map(fn (string $value): string => \sprintf('*.%s', $value), $patterns);
    }

    private function hasResults(SymfonyStyle $io, Finder $finder, string $path): bool
    {
        if ($finder->hasResults()) {
            return true;
        }

        $io->comment(\sprintf('No file found in directory "%s".', $path));

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
        if ([] === $patterns) {
            $io->error(\sprintf(
                'No pattern defined. Allowed values: "%s".',
                \implode('", "', $keys)
            ));

            return false;
        }

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
