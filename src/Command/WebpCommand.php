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
#[AsCommand(name: 'app:update-images', description: 'Convert images, from the given directory, to Webp format.')]
class WebpCommand extends Command
{
    use LoggerTrait;
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
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new SymfonyStyle($input, $output);

        $source = \trim((string) $input->getArgument(self::SOURCE_ARGUMENT));
        if ('' === $source) {
            $this->writeError('The "--source" argument requires a non-empty value.');

            return Command::INVALID;
        }
        $fullPath = FileUtils::buildPath($this->projectDir, $source);
        if (!$this->validateSource($fullPath)) {
            return Command::INVALID;
        }

        /** @psalm-var mixed $level */
        $level = $input->getOption(self::OPTION_LEVEL);
        if (!$this->validateLevel($level)) {
            return Command::INVALID;
        }

        $finder = $this->createFinder($fullPath, (int) $level);
        if (!$finder->hasResults()) {
            $this->writeWarning(\sprintf('No image found in directory "%s".', $source));

            return Command::SUCCESS;
        }

        $skip = 0;
        $error = 0;
        $success = 0;
        $oldSize = 0;
        $newSize = 0;
        $dry_run = $input->getOption(self::OPTION_DRY_RUN);
        $overwrite = $input->getOption(self::OPTION_OVERWRITE);
        $this->writeVerbose(\sprintf('Process images in "%s"', $source));

        foreach ($finder as $file) {
            $path = $file->getRealPath();
            $name = $file->getFilename();
            if (!$this->isImage($path)) {
                continue;
            }
            $this->writeVerbose(\sprintf('Load : %s', $name));
            $extension = $this->getImageExtension($file);
            if (!$extension instanceof ImageExtension) {
                $this->writeVerbose(\sprintf('Skip : %s', $name));
                ++$skip;
                continue;
            }

            $image = $extension->createImage($path);
            if (!$image instanceof \GdImage) {
                $this->writeln(\sprintf('Skip : %s - Unable to load image.', $name), 'error');
                ++$error;
                continue;
            }

            $targetFile = $this->getTargetFile($file);
            $targetName = \basename($targetFile);
            if (!$overwrite && FileUtils::exists($targetFile)) { // @phpstan-ignore-line
                $this->writeVerbose(\sprintf('Skip : %s - Image already exist.', $targetName));
                \imagedestroy($image);
                ++$skip;
                continue;
            }

            if ($dry_run) { // @phpstan-ignore-line
                $this->writeVerbose(\sprintf('Save : %s (Simulate)', $targetName));
                [$result, $size] = $this->saveImage($image);
            } else {
                $this->writeVerbose(\sprintf('Save : %s', $targetName));
                [$result, $size] = $this->saveImage($image, $targetFile);
            }
            if ($result) {
                $oldSize += FileUtils::size($path);
                $newSize += $size;
                ++$success;
            } else {
                $this->writeln(\sprintf('Error: %s - Unable to convert image.', $targetName), 'error');
                ++$error;
            }
            \imagedestroy($image);
        }

        $percent = $this->safeDivide($newSize - $oldSize, $oldSize);
        $message = \sprintf(
            'Conversion: %s, Skip: %s, Error: %s, Old Size: %s, New Size: %s, Size reduction: %s.',
            FormatUtils::formatInt($success),
            FormatUtils::formatInt($skip),
            FormatUtils::formatInt($error),
            FormatUtils::formatInt($oldSize),
            FormatUtils::formatInt($newSize),
            FormatUtils::formatPercent($percent, decimals: 1)
        );
        if (0 !== $error) {
            $this->writeError($message);

            return Command::FAILURE;
        }
        if ($percent > 0) {
            $this->writeWarning($message);
        } else {
            $this->writeSuccess($message);
        }

        return Command::SUCCESS;
    }

    private function createFinder(string $path, int $level): Finder
    {
        $filtered = \array_filter(ImageExtension::cases(), fn (ImageExtension $e): bool => ImageExtension::WEBP !== $e);
        $extensions = \array_map(fn (ImageExtension $e): string => $e->getFilter(), $filtered);
        $depth = "<= $level";

        return (new Finder())->ignoreUnreadableDirs()
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

    private function validateLevel(mixed $level): bool
    {
        if (!\is_numeric($level)) {
            $this->writeError(\sprintf('The level argument must be of type int, %s given.', \get_debug_type($level)));

            return false;
        }
        if ((int) $level < 0) {
            $this->writeError(\sprintf('The level argument must be greater than or equal to 0, %d given.', $level));

            return false;
        }

        return true;
    }

    private function validateSource(string $path): bool
    {
        if (!FileUtils::exists($path)) {
            $this->writeError(\sprintf('Unable to find the source directory: "%s".', $path));

            return false;
        }
        if (!FileUtils::isDir($path)) {
            $this->writeError(\sprintf('The source "%s" is not a directory.', $path));

            return false;
        }

        return true;
    }
}
