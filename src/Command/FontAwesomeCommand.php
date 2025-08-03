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
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsCommand(name: 'app:fontawesome', description: 'Copy SVG files and aliases from the font-awesome package.')]
class FontAwesomeCommand
{
    use WatchTrait;

    private const DEFAULT_SOURCE = 'vendor/fortawesome/font-awesome/metadata/icons.json';
    private const DEFAULT_TARGET = 'resources/fontawesome';
    private const SVG_SOURCE = 'vendor/fortawesome/font-awesome/svgs-full';

    public function __construct(
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir
    ) {
    }

    public function __invoke(
        SymfonyStyle $io,
        #[Argument(description: 'The JSON source file, relative to the project directory, where to get metadata informations.', )]
        ?string $source = null,
        #[Argument(description: 'The target directory, relative to the project directory, where to copy SVG files.', )]
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
            $this->start();
            $io->writeln([\sprintf('Generate files from "%s"...', $relativeSource), '']);
            foreach ($io->progressIterate($content, $count) as $key => $item) {
                /** @phpstan-var string[] $styles */
                $styles = $item['styles'];
                foreach ($styles as $style) {
                    $svgFileName = $this->getSvgFileName($style, $key);
                    $svgFullPath = $this->getSvgFullPath($svgFileName);
                    if (!FileUtils::isFile($svgFullPath)) {
                        $io->error(\sprintf('Unable to find SVG file: "%s".', $svgFileName));

                        return Command::FAILURE;
                    }

                    $svgTargetFile = FileUtils::buildPath($tempDir, $svgFileName);
                    if (!FileUtils::copy($svgFullPath, $svgTargetFile)) {
                        $io->error(\sprintf('Unable to copy file: "%s".', $svgFileName));

                        return Command::FAILURE;
                    }

                    ++$files;
                }
            }

            if ($dryRun) {
                $io->success(
                    \sprintf(
                        'Simulate command successfully: %d files from %d sources. %s.',
                        $files,
                        $count,
                        $this->stop()
                    )
                );

                return Command::SUCCESS;
            }

            $target = FileUtils::buildPath($this->projectDir, $target ?? self::DEFAULT_TARGET);
            $relativeTarget = $this->getRelativePath($target);

            $io->writeln(\sprintf('Copy files to "%s"...', $relativeTarget));
            if (!FileUtils::mirror($tempDir, $target, delete: true)) {
                $io->error(\sprintf('Unable to copy %d files to the directory: "%s".', $count, $relativeTarget));

                return Command::FAILURE;
            }

            $io->success(
                \sprintf(
                    'Generate images successfully: %d files from %d sources. %s.',
                    $files,
                    $count,
                    $this->stop()
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

    private function getSvgFullPath(string $name): string
    {
        return FileUtils::buildPath($this->projectDir, self::SVG_SOURCE, $name);
    }
}
