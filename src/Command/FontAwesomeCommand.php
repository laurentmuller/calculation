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

use App\Service\FontAwesomeImageService;
use App\Utils\FileUtils;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Command to copy SVG files and aliases from the font-awesome package.
 */
#[AsCommand(name: 'app:fontawesome', description: 'Copy SVG files and aliases from the font-awesome package.')]
class FontAwesomeCommand extends Command
{
    private const DEFAULT_SOURCE = 'vendor/fortawesome/font-awesome/metadata/icons.json';
    private const DEFAULT_TARGET = 'resources/fontawesome';
    private const DRY_RUN_OPTION = 'dry-run';
    private const SOURCE_ARGUMENT = 'source';
    private const TARGET_ARGUMENT = 'target';

    public function __construct(
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument(
            name: self::SOURCE_ARGUMENT,
            mode: InputArgument::OPTIONAL,
            description: 'The JSON source file, relative to the project directory, where to get metadata informations.',
            default: self::DEFAULT_SOURCE
        );
        $this->addArgument(
            name: self::TARGET_ARGUMENT,
            mode: InputArgument::OPTIONAL,
            description: 'The target directory, relative to the project directory, where to generate SVG files.',
            default: self::DEFAULT_TARGET
        );
        $this->addOption(
            name: self::DRY_RUN_OPTION,
            shortcut: 'd',
            mode: InputOption::VALUE_NONE,
            description: 'Run the command without making changes (simulate files generation).'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $source = $this->getSourceFile($io);
        $relativeSource = $this->getRelativePath($source);
        if (!FileUtils::isFile($source)) {
            $io->error(\sprintf('Unable to find JSON source file: "%s".', $relativeSource));

            return Command::INVALID;
        }

        $content = $this->decodeJson($io, $source);
        if (!\is_array($content)) {
            return Command::FAILURE;
        }

        $count = \count($content);
        if (0 === $count) {
            $io->warning(\sprintf('No image found: "%s".', $relativeSource));

            return Command::SUCCESS;
        }

        $tempDir = FileUtils::tempDir();
        if (!\is_string($tempDir)) {
            $io->error('Unable to create the temporary directory.');

            return Command::FAILURE;
        }

        $aliases = [];

        try {
            $files = 0;
            $io->writeln([\sprintf('Generate files from "%s"...', $relativeSource), '']);
            foreach ($io->progressIterate($content, $count) as $key => $item) {
                $names = $this->getAliasNames($item);
                /** @psalm-var string[] $styles */
                $styles = $item['styles'];
                foreach ($styles as $style) {
                    $data = $this->getRawData($style, $item);
                    if (null === $data) {
                        continue;
                    }
                    if (!$this->dumpFile($io, $tempDir, $style, $key, $data)) {
                        return self::FAILURE;
                    }
                    ++$files;

                    foreach ($names as $name) {
                        $aliasKey = $this->getSvgFileName($style, $name);
                        $aliases[$aliasKey] = $this->getSvgFileName($style, $key);
                    }
                }
            }

            $countAliases = \count($aliases);
            if ($this->isDryRun($io, $files, $countAliases, $count)) {
                return Command::SUCCESS;
            }

            $target = $this->getTargetDirectory($io);
            $relativeTarget = $this->getRelativePath($target);

            \ksort($aliases);
            $aliasesPath = FileUtils::buildPath($tempDir, FontAwesomeImageService::ALIAS_FILE_NAME);
            if (!FileUtils::dumpFile($aliasesPath, (string) \json_encode($aliases, \JSON_PRETTY_PRINT))) {
                $io->error(\sprintf('Unable to copy aliases file to the directory: "%s".', $relativeTarget));

                return Command::FAILURE;
            }

            $io->writeln(\sprintf('Copy files to "%s"...', $relativeTarget));
            if (!FileUtils::mirror($tempDir, $target, delete: true)) {
                $io->error(\sprintf('Unable to copy %d files to the directory: "%s".', $count, $relativeTarget));

                return Command::FAILURE;
            }

            $io->success(
                \sprintf(
                    'Generate successfully %d files, %d aliases from %d sources.',
                    $files,
                    $countAliases,
                    $count
                )
            );

            return Command::SUCCESS;
        } finally {
            FileUtils::remove($tempDir);
        }
    }

    /**
     * @psalm-return array<array-key, array>|null
     */
    private function decodeJson(SymfonyStyle $io, string $file): ?array
    {
        try {
            /** @psalm-var array<array-key, array> */
            return FileUtils::decodeJson($file);
        } catch (\InvalidArgumentException $e) {
            $io->error($e->getMessage());

            return null;
        }
    }

    private function dumpFile(SymfonyStyle $io, string $path, string $style, string|int $name, string $content): bool
    {
        $fileName = $this->getSvgFileName($style, $name);
        $target = FileUtils::buildPath($path, $fileName);
        if (FileUtils::dumpFile($target, $content)) {
            return true;
        }

        $io->error(\sprintf('Unable to dump file: "%s".', $fileName));

        return false;
    }

    /**
     * @return string[]
     */
    private function getAliasNames(array $item): array
    {
        /** @psalm-var string[] */
        return $item['aliases']['names'] ?? [];
    }

    private function getRawData(string $style, array $item): ?string
    {
        /** @psalm-var string|null */
        return $item['svg'][$style]['raw'] ?? null;
    }

    private function getRelativePath(string $path): string
    {
        $name = '';
        if (FileUtils::isFile($path)) {
            $name = \basename($path);
            $path = \dirname($path);
        }

        return FileUtils::makePathRelative($path, $this->projectDir) . $name;
    }

    private function getSourceFile(SymfonyStyle $io): string
    {
        $source = $io->getStringArgument(self::SOURCE_ARGUMENT);
        if ('' === $source) {
            $source = self::DEFAULT_SOURCE;
        }

        return FileUtils::buildPath($this->projectDir, $source);
    }

    private function getSvgFileName(string $style, string|int $name): string
    {
        return \sprintf('%s/%s%s', $style, $name, FontAwesomeImageService::SVG_EXTENSION);
    }

    private function getTargetDirectory(SymfonyStyle $io): string
    {
        $target = $io->getStringArgument(self::TARGET_ARGUMENT);
        if ('' === $target) {
            $target = self::DEFAULT_TARGET;
        }

        return FileUtils::buildPath($this->projectDir, $target);
    }

    private function isDryRun(SymfonyStyle $io, int $files, int $aliases, int $count): bool
    {
        if (!$io->getBoolOption(self::DRY_RUN_OPTION)) {
            return false;
        }

        $io->success(\sprintf('Simulate successfully %d files, %d aliases from %d sources.', $files, $aliases, $count));

        return true;
    }
}
