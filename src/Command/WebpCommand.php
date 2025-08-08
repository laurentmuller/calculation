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

use App\Enums\ImageExtension;
use App\Traits\MathTrait;
use App\Utils\FileUtils;
use App\Utils\FormatUtils;
use App\Utils\StringUtils;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

#[AsCommand(
    name: 'app:update-images',
    description: 'Convert images, from the given directory, to the WebP format.',
    help: 'The <info>%command.name%</info> command convert images, from the given directory, to the <href=https://en.wikipedia.org/wiki/WebP>WebP</> format.'
)]
class WebpCommand
{
    use MathTrait;
    use WatchTrait;

    public function __construct(
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir
    ) {
    }

    public function __invoke(
        SymfonyStyle $io,
        #[Argument(description: 'The source directory relative to the project directory.')]
        ?string $source = null,
        #[Option(description: 'The level (depth) to search in directory.', shortcut: 'l')]
        int $level = 0,
        #[Option(description: 'Overwrite existing files.', shortcut: 'o')]
        bool $overwrite = false,
        #[Option(description: 'Simulate conversion without generate images.', name: 'dry-run', shortcut: 'd')]
        bool $dryRun = false
    ): int {
        if (!$this->validateSource($io, $source)) {
            return Command::INVALID;
        }

        $fullPath = FileUtils::buildPath($this->projectDir, $source);
        if (!$this->validateFullPath($io, $fullPath)) {
            return Command::INVALID;
        }

        if (!$this->validateLevel($io, $level)) {
            return Command::INVALID;
        }

        $finder = $this->createFinder($fullPath, $level);
        if (!$finder->hasResults()) {
            $io->warning(\sprintf('No image found in directory "%s".', $source));

            return Command::SUCCESS;
        }

        $skip = 0;
        $error = 0;
        $success = 0;
        $oldSize = 0;
        $newSize = 0;

        $this->start();
        $this->writeVerbose($io, \sprintf('Process images in "%s"', $source));
        foreach ($finder as $file) {
            $path = $file->getRealPath();
            $name = $file->getFilename();
            if (!$this->isImage($path)) {
                continue;
            }
            $this->writeVerbose($io, \sprintf('Load : %s', $name));
            $extension = $this->getImageExtension($file);
            if (!$extension instanceof ImageExtension) {
                $this->writeVerbose($io, \sprintf('Skip : %s', $name));
                ++$skip;
                continue;
            }

            $image = $extension->createImage($path);
            if (!$image instanceof \GdImage) {
                $this->writeln($io, \sprintf('Skip : %s - Unable to load image.', $name), 'error');
                ++$error;
                continue;
            }

            $targetFile = $this->getTargetFile($file);
            $targetName = \basename($targetFile);
            if (!$overwrite && FileUtils::exists($targetFile)) {
                $this->writeVerbose($io, \sprintf('Skip : %s - Image already exist.', $targetName));
                \imagedestroy($image);
                ++$skip;
                continue;
            }

            if ($dryRun) {
                $this->writeVerbose($io, \sprintf('Save : %s (Simulate)', $targetName));
                [$result, $size] = $this->saveImage($image);
            } else {
                $this->writeVerbose($io, \sprintf('Save : %s', $targetName));
                [$result, $size] = $this->saveImage($image, $targetFile);
            }
            if ($result) {
                $oldSize += FileUtils::size($path);
                $newSize += $size;
                ++$success;
            } else {
                $this->writeln($io, \sprintf('Error: %s - Unable to convert image.', $targetName), 'error');
                ++$error;
            }
            \imagedestroy($image);
        }

        $percent = $this->safeDivide($newSize - $oldSize, $oldSize);
        $title = $percent > 0 ? 'extension!' : 'reduction';
        $message = \sprintf(
            'Conversion: %s, Skip: %s, Error: %s, Old Size: %s, New Size: %s, Size %s: %s, %s.',
            FormatUtils::formatInt($success),
            FormatUtils::formatInt($skip),
            FormatUtils::formatInt($error),
            FileUtils::formatSize($oldSize),
            FileUtils::formatSize($newSize),
            $title,
            FormatUtils::formatPercent($percent, decimals: 1),
            $this->stop()
        );
        if (0 !== $error) {
            $io->error($message);

            return Command::FAILURE;
        }
        if ($percent > 0) {
            $io->warning($message);
        } else {
            $io->success($message);
        }

        return Command::SUCCESS;
    }

    private function createFinder(string $path, int $level): Finder
    {
        $filtered = \array_filter(ImageExtension::cases(), static fn (ImageExtension $e): bool => ImageExtension::WEBP !== $e);
        $extensions = \array_map(static fn (ImageExtension $e): string => $e->getFilter(), $filtered);
        $depth = "<= $level";

        return Finder::create()
            ->ignoreUnreadableDirs()
            ->in($path)
            ->depth($depth)
            ->files()
            ->name($extensions);
    }

    private function getImageExtension(SplFileInfo $file): ?ImageExtension
    {
        $extension = ImageExtension::tryFrom(\strtolower($file->getExtension()));
        if (!$extension instanceof ImageExtension || ImageExtension::WEBP === $extension) {
            return null;
        }

        return $extension;
    }

    private function getTargetFile(SplFileInfo $info): string
    {
        return FileUtils::changeExtension($info, ImageExtension::WEBP);
    }

    private function isImage(string $path): bool
    {
        $info = \getimagesize($path);

        return false !== $info && ImageExtension::tryFromType($info[2]) instanceof ImageExtension;
    }

    /**
     * @return array{0: bool, 1: non-negative-int}
     */
    private function saveImage(\GdImage $image, ?string $path = null): array
    {
        $temp = null;
        if (null === $path) {
            $temp = FileUtils::tempFile();
            if (null === $temp) {
                return [false, 0];
            }
            $path = $temp;
        }

        try {
            \imagepalettetotruecolor($image);
            \imagealphablending($image, true);
            \imagesavealpha($image, true);

            $result = \imagewebp($image, $path);
            $size = FileUtils::size($path);

            return [$result, $size];
        } finally {
            if (null !== $temp) {
                FileUtils::remove($temp);
            }
        }
    }

    private function validateFullPath(SymfonyStyle $io, string $path): bool
    {
        if (!FileUtils::exists($path)) {
            $io->error(\sprintf('Unable to find the source directory: "%s".', $path));

            return false;
        }
        if (!FileUtils::isDir($path)) {
            $io->error(\sprintf('The source "%s" is not a directory.', $path));

            return false;
        }

        return true;
    }

    /**
     * @phpstan-assert-if-true non-negative-int $level
     */
    private function validateLevel(SymfonyStyle $io, int $level): bool
    {
        if ($level < 0) {
            $io->error(\sprintf('The level argument must be greater than or equal to 0, %d given.', $level));

            return false;
        }

        return true;
    }

    /**
     * @phpstan-assert-if-true non-empty-string $source
     */
    private function validateSource(SymfonyStyle $io, ?string $source): bool
    {
        if (!StringUtils::isString($source)) {
            $io->error('The "--source" argument requires a non-empty value.');

            return false;
        }

        return true;
    }

    private function writeln(SymfonyStyle $io, string $message, string $style = 'info'): void
    {
        $io->writeln("<$style>$message</>");
    }

    private function writeVerbose(SymfonyStyle $io, string $message, string $style = 'info'): void
    {
        if ($io->isVerbose()) {
            $this->writeln($io, $message, $style);
        }
    }
}
