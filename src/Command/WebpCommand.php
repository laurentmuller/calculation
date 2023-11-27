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
use App\Utils\FileUtils;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

/**
 * Command to convert images to Webp format.
 */
#[AsCommand(name: 'app:update-images', description: 'Convert images to Webp format.')]
class WebpCommand extends Command
{
    private const OPTION_DEPTH = 'depth';
    private const OPTION_DRY_RUN = 'dry-run';
    private const OPTION_OVERWRITE = 'overwrite';
    private const OPTION_SOURCE = 'source';

    public function __construct(
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(self::OPTION_SOURCE, 's', InputOption::VALUE_REQUIRED, 'The source directory relative to the root project directory.');
        $this->addOption(self::OPTION_DEPTH, 'r', InputOption::VALUE_OPTIONAL, 'The depth to search in directory.', 0);
        $this->addOption(self::OPTION_OVERWRITE, 'o', InputOption::VALUE_NONE, 'Overwrite existing files.');
        $this->addOption(self::OPTION_DRY_RUN, 'd', InputOption::VALUE_NONE, 'Check only without generate files.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $source = (string) $input->getOption(self::OPTION_SOURCE);
        $fullPath = FileUtils::buildPath($this->projectDir, $source);
        if (!$this->validateSource($io, $fullPath)) {
            return Command::INVALID;
        }

        $depth = (int) $input->getOption(self::OPTION_DEPTH);
        if (!$this->validateDepth($io, $depth)) {
            return Command::INVALID;
        }

        $finder = $this->createFinder($io, $fullPath, $depth);
        if (!$finder instanceof Finder) {
            return Command::SUCCESS;
        }

        $skip = 0;
        $error = 0;
        $success = 0;
        /** @var bool $dryRun */
        $dryRun = $input->getOption(self::OPTION_DRY_RUN);
        /** @var bool $overwrite */
        $overwrite = $input->getOption(self::OPTION_OVERWRITE);
        $this->writeVerbose($io, \sprintf('Process images in "%s"', $source));
        foreach ($finder as $file) {
            $path = $file->getRealPath();
            $name = $file->getFilename();
            if (!$this->isImage($path)) {
                continue;
            }

            $this->writeVerbose($io, \sprintf('Process : %s', $name));
            $extension = $this->getImageExtension($file);
            if (!$extension instanceof ImageExtension) {
                $this->writeVerbose($io, \sprintf('Skip    : %s', $name));
                ++$skip;
                continue;
            }

            $image = $extension->createImage($path);
            if (!$image instanceof \GdImage) {
                $this->writeVerbose($io, \sprintf('Skip    : %s - Unable to load image.', $name));
                ++$error;
                continue;
            }

            $targetFile = $this->getTargetFile($file);
            $targetName = \basename($targetFile);
            if (!$overwrite && FileUtils::exists($targetFile)) {
                $this->writeVerbose($io, \sprintf('Skip    : %s - Image already exist.', $targetName));
                \imagedestroy($image);
                ++$skip;
                continue;
            }

            if ($dryRun) {
                if ($this->saveImageTemp($image)) {
                    $this->writeVerbose($io, \sprintf('Save    : %s - Simulate.', $targetName));
                    ++$success;
                } else {
                    $io->writeln(\sprintf('<bg=red>Error   : %s - Unable to convert image.</>', $targetName));
                    ++$error;
                }
                \imagedestroy($image);
                continue;
            }

            $this->writeVerbose($io, \sprintf('Save    : %s.', $targetName));
            if (!$this->saveImage($image, $targetFile)) {
                $io->writeln(\sprintf('<bg=red>Error   : %s - Unable to convert image.</>', $targetName));
                \imagedestroy($image);
                ++$error;
                continue;
            }

            \imagedestroy($image);
            ++$success;
        }
        $message = \sprintf('Conversion: %d, Skip: %d, Error: %d.', $success, $skip, $error);
        if (0 !== $error) {
            $io->error($message);

            return Command::FAILURE;
        }

        $io->success($message);

        return Command::SUCCESS;
    }

    private function createFinder(SymfonyStyle $io, string $path, int $depth): ?Finder
    {
        $callback = static fn (ImageExtension $extension): string => \sprintf('*.%s', $extension->value);
        $name = \array_map($callback, ImageExtension::cases());
        $notName = $callback(ImageExtension::WEBP);

        $finder = new Finder();
        $finder->ignoreUnreadableDirs()
            ->in($path)
            ->depth("<= $depth")
            ->files()
            ->name($name)
            ->notName($notName);
        if ($finder->hasResults()) {
            return $finder;
        }
        $io->warning(\sprintf('No image found in directory "%s".', $path));

        return null;
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
        $name = $info->getFilenameWithoutExtension();
        $extension = ImageExtension::WEBP->value;
        $full_name = \sprintf('%s.%s', $name, $extension);

        return FileUtils::buildPath($info->getPath(), $full_name);
    }

    private function isImage(string $path): bool
    {
        $info = \getimagesize($path);

        return false !== $info && ImageExtension::tryFromType($info[2]) instanceof ImageExtension;
    }

    private function saveImage(\GdImage $image, string $path): bool
    {
        \imagepalettetotruecolor($image);
        \imagealphablending($image, true);
        \imagesavealpha($image, true);

        return \imagewebp($image, $path);
    }

    private function saveImageTemp(\GdImage $image): bool
    {
        $temp = FileUtils::tempFile();
        if (!\is_string($temp)) {
            return false;
        }

        try {
            return $this->saveImage($image, $temp);
        } finally {
            FileUtils::remove($temp);
        }
    }

    private function validateDepth(SymfonyStyle $io, int $depth): bool
    {
        if ($depth < 0) {
            $io->error(\sprintf('Depth argument must be greater than or equal to 0, %d given.', $depth));

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

    private function writeVerbose(SymfonyStyle $io, string $message): void
    {
        if ($io->isVerbose()) {
            $io->writeln($message);
        }
    }
}
