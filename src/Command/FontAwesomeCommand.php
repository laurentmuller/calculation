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

    private const string DEFAULT_SOURCE = 'vendor/fortawesome/font-awesome/metadata/icons.json';
    private const string DEFAULT_TARGET = 'resources/fontawesome';
    private const string SVG_SOURCE = 'vendor/fortawesome/font-awesome/svgs-full';

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
        #[Option(description: 'Run the command without making changes (simulate copying SVG files).', name: 'dry-run', shortcut: 'd')]
        bool $dryRun = false
    ): int {
        $source = FileUtils::buildPath($this->projectDir, $source ?? self::DEFAULT_SOURCE);
        $relativeSource = $this->getRelativePath($source);
        if (!FileUtils::isFile($source)) {
            return $this->error($io, 'Unable to find JSON source file: "%s".', $relativeSource);
        }

        try {
            /** @phpstan-var array<array-key, array> $content */
            $content = FileUtils::decodeJson($source);
        } catch (\InvalidArgumentException) {
            return $this->error($io, 'Unable to get content of the JSON source file: "%s".', $relativeSource);
        }

        $count = \count($content);
        if (0 === $count) {
            return $this->error($io, 'No image found: "%s".', $relativeSource);
        }

        $tempDir = FileUtils::tempDir();
        if (!\is_string($tempDir)) {
            return $this->error($io, 'Unable to create the temporary directory.');
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
                    $svgFilePath = $this->getSvgFilePath($svgFileName);
                    $svgTargetFile = FileUtils::buildPath($tempDir, $svgFileName);
                    if (!FileUtils::copy($svgFilePath, $svgTargetFile)) {
                        return $this->error($io, 'Unable to copy file: "%s".', $svgFileName);
                    }
                    ++$files;
                }
            }

            if ($dryRun) {
                return $this->success(
                    $io,
                    'Simulate command successfully: %d files from %d sources. %s.',
                    $files,
                    $count,
                    $this->stop()
                );
            }

            $target = FileUtils::buildPath($this->projectDir, $target ?? self::DEFAULT_TARGET);
            $relativeTarget = $this->getRelativePath($target);
            $io->writeln(\sprintf('Copy files to "%s"...', $relativeTarget));
            if (!FileUtils::mirror(origin: $tempDir, target: $target, delete: true)) {
                return $this->error($io, 'Unable to copy %d files to the directory: "%s".', $count, $relativeTarget);
            }

            return $this->success(
                $io,
                'Generate images successfully: %d files from %d sources. %s.',
                $files,
                $count,
                $this->stop()
            );
        } finally {
            FileUtils::remove($tempDir);
        }
    }

    private function error(SymfonyStyle $io, string $message, string|int ...$parameters): int
    {
        $io->error(\sprintf($message, ...$parameters));

        return Command::FAILURE;
    }

    private function getRelativePath(string $path): string
    {
        return FileUtils::makePathRelative($path, $this->projectDir);
    }

    private function getSvgFileName(string $style, string|int $name): string
    {
        return \sprintf('%s/%s%s', $style, $name, FontAwesomeImageService::SVG_EXTENSION);
    }

    private function getSvgFilePath(string $svgFileName): string
    {
        return FileUtils::buildPath($this->projectDir, self::SVG_SOURCE, $svgFileName);
    }

    private function success(SymfonyStyle $io, string $message, string|int ...$parameters): int
    {
        $io->success(\sprintf($message, ...$parameters));

        return Command::SUCCESS;
    }
}
