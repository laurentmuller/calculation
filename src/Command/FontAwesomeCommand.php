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
use App\Utils\StringUtils;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsCommand(name: 'app:fontawesome', description: 'Copy SVG files and aliases from the font-awesome package.')]
class FontAwesomeCommand
{
    private const DEFAULT_SOURCE = 'vendor/fortawesome/font-awesome/metadata/icons.json';
    private const DEFAULT_TARGET = 'resources/fontawesome';

    public function __construct(
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir
    ) {
    }

    public function __invoke(
        SymfonyStyle $io,
        #[Argument(description: 'The JSON source file, relative to the project directory, where to get metadata informations.', )]
        ?string $source = null,
        #[Argument(description: 'The target directory, relative to the project directory, where to generate SVG files.', )]
        ?string $target = null,
        #[Option(description: 'Run the command without making changes (simulate files generation).', name: 'dry-run', shortcut: 'd')]
        bool $dryRun = false
    ): int {
        $source = FileUtils::buildPath($this->projectDir, $source ?? self::DEFAULT_SOURCE);
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

        try {
            $files = 0;
            $aliases = [];
            $io->writeln([\sprintf('Generate files from "%s"...', $relativeSource), '']);
            foreach ($io->progressIterate($content, $count) as $key => $item) {
                $names = $this->getAliasNames($item);
                /** @phpstan-var string[] $styles */
                $styles = $item['styles'];
                foreach ($styles as $style) {
                    $data = $this->getRawData($style, $item);
                    if (null === $data) {
                        continue;
                    }
                    if (!$this->dumpFile($io, $tempDir, $style, $key, $data)) {
                        return Command::FAILURE;
                    }
                    ++$files;

                    foreach ($names as $name) {
                        $aliasKey = $this->getSvgFileName($style, $name);
                        $aliases[$aliasKey] = $this->getSvgFileName($style, $key);
                    }
                }
            }

            $countAliases = \count($aliases);
            if ($dryRun) {
                $io->success(\sprintf('Simulate successfully %d files, %d aliases from %d sources.', $files, $countAliases, $count));

                return Command::SUCCESS;
            }

            $target = FileUtils::buildPath($this->projectDir, $target ?? self::DEFAULT_TARGET);
            $relativeTarget = $this->getRelativePath($target);

            \ksort($aliases);
            $aliasesContent = $this->encodeJson($aliases);
            $aliasesPath = FileUtils::buildPath($tempDir, FontAwesomeImageService::ALIAS_FILE_NAME);
            if (!FileUtils::dumpFile($aliasesPath, $aliasesContent)) {
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
     * @return array<array-key, array>|null
     */
    private function decodeJson(SymfonyStyle $io, string $file): ?array
    {
        try {
            /** @phpstan-var array<array-key, array> */
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

    private function encodeJson(array $data): string
    {
        return StringUtils::encodeJson($data, \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES);
    }

    /**
     * @return string[]
     */
    private function getAliasNames(array $item): array
    {
        /** @phpstan-var string[] */
        return $item['aliases']['names'] ?? [];
    }

    private function getRawData(string $style, array $item): ?string
    {
        /** @phpstan-var string|null */
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

    private function getSvgFileName(string $style, string|int $name): string
    {
        return \sprintf('%s/%s%s', $style, $name, FontAwesomeImageService::SVG_EXTENSION);
    }
}
