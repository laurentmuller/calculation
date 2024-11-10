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
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

/**
 * Command to convert images to Webp format.
 */
#[AsCommand(name: 'app:update-images', description: 'Convert images, from the given directory, to the WebP format.')]
class WebpCommand extends Command
{
    use MathTrait;

    private const OPTION_DRY_RUN = 'dry-run';
    private const OPTION_LEVEL = 'level';
    private const OPTION_OVERWRITE = 'overwrite';
    private const SOURCE_ARGUMENT = 'source';

    public function __construct(
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument(self::SOURCE_ARGUMENT, InputOption::VALUE_REQUIRED, 'The source directory relative to the project directory.');
        $this->addOption(self::OPTION_LEVEL, 'l', InputOption::VALUE_REQUIRED, 'The level (depth) to search in directory.', 0);
        $this->addOption(self::OPTION_OVERWRITE, 'o', InputOption::VALUE_NONE, 'Overwrite existing files.');
        $this->addOption(self::OPTION_DRY_RUN, 'd', InputOption::VALUE_NONE, 'Simulate conversion without generate images.');
        $this->setHelp('The <info>%command.name%</info> command convert images, from the given directory, to the <href=https://en.wikipedia.org/wiki/WebP>WebP</> format.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $source = StringUtils::trim($io->getStringArgument(self::SOURCE_ARGUMENT));
        if (null === $source) {
            $io->error('The "--source" argument requires a non-empty value.');

            return Command::INVALID;
        }

        $fullPath = FileUtils::buildPath($this->projectDir, $source);
        if (!$this->validateSource($io, $fullPath)) {
            return Command::INVALID;
        }

        /** @psalm-var mixed $level */
        $level = $io->getOption(self::OPTION_LEVEL);
        if (!$this->validateLevel($io, $level)) {
            return Command::INVALID;
        }

        $finder = $this->createFinder($fullPath, (int) $level);
        if (!$finder->hasResults()) {
            $io->warning(\sprintf('No image found in directory "%s".', $source));

            return Command::SUCCESS;
        }

        $skip = 0;
        $error = 0;
        $success = 0;
        $oldSize = 0;
        $newSize = 0;
        $startTime = \time();
        $dry_run = $io->getBoolOption(self::OPTION_DRY_RUN);
        $overwrite = $io->getBoolOption(self::OPTION_OVERWRITE);
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

            if ($dry_run) {
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
        $message = \sprintf(
            'Conversion: %s, Skip: %s, Error: %s, Old Size: %s, New Size: %s, Size reduction: %s, Duration: %s.',
            FormatUtils::formatInt($success),
            FormatUtils::formatInt($skip),
            FormatUtils::formatInt($error),
            FormatUtils::formatInt($oldSize),
            FormatUtils::formatInt($newSize),
            FormatUtils::formatPercent($percent, decimals: 1),
            $io->formatDuration($startTime)
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
        $filtered = \array_filter(ImageExtension::cases(), fn (ImageExtension $e): bool => ImageExtension::WEBP !== $e);
        $extensions = \array_map(fn (ImageExtension $e): string => $e->getFilter(), $filtered);
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
     * @return array{0: bool, 1: int}
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

    /**
     * @psalm-assert-if-true numeric-string $level
     */
    private function validateLevel(SymfonyStyle $io, mixed $level): bool
    {
        if (!\is_numeric($level)) {
            $io->error(\sprintf('The level argument must be of type int, %s given.', \get_debug_type($level)));

            return false;
        }
        if ((int) $level < 0) {
            $io->error(\sprintf('The level argument must be greater than or equal to 0, %d given.', $level));

            return false;
        }

        return true;
    }

    private function validateSource(SymfonyStyle $io, string $path): bool
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
