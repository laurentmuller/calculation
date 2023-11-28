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
    use MathTrait;

    private const OPTION_DEPTH = 'depth';
    private const OPTION_DRY_RUN = 'dry-run';
    private const OPTION_OVERWRITE = 'overwrite';
    private const OPTION_SOURCE = 'source';

    private ?SymfonyStyle $io = null;

    public function __construct(
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(self::OPTION_SOURCE, null, InputOption::VALUE_REQUIRED, 'The source directory relative to the project directory.');
        $this->addOption(self::OPTION_DEPTH, null, InputOption::VALUE_REQUIRED, 'The depth to search in directory.', 0);
        $this->addOption(self::OPTION_OVERWRITE, null, InputOption::VALUE_NONE, 'Overwrite existing files.');
        $this->addOption(self::OPTION_DRY_RUN, null, InputOption::VALUE_NONE, 'Simulate conversion without generate images.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new SymfonyStyle($input, $output);

        $source = \trim((string) $input->getOption(self::OPTION_SOURCE));
        if ('' === $source) {
            $this->io->error('The "--source" option requires a non-empty value.');

            return Command::INVALID;
        }
        $fullPath = FileUtils::buildPath($this->projectDir, $source);
        if (!$this->validateSource($fullPath)) {
            return Command::INVALID;
        }

        $depth = $input->getOption(self::OPTION_DEPTH);
        if (!$this->validateDepth($depth)) {
            return Command::INVALID;
        }

        $finder = $this->createFinder($fullPath, (int) $depth);
        if (!$finder instanceof Finder) {
            return Command::SUCCESS;
        }

        $skip = 0;
        $error = 0;
        $success = 0;
        $oldSize = 0;
        $newSize = 0;
        $dryRun = $input->getOption(self::OPTION_DRY_RUN);
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
                $this->writeError(\sprintf('Skip : %s - Unable to load image.', $name));
                ++$error;
                continue;
            }

            $targetFile = $this->getTargetFile($file);
            $targetName = \basename($targetFile);
            if (!$overwrite && FileUtils::exists($targetFile)) {
                $this->writeVerbose(\sprintf('Skip : %s - Image already exist.', $targetName));
                \imagedestroy($image);
                ++$skip;
                continue;
            }

            if ($dryRun) {
                [$result, $size] = $this->saveImageTemp($image);
                if ($result) {
                    $this->writeVerbose(\sprintf('Save : %s (Simulate)', $targetName));
                    $oldSize += FileUtils::size($path);
                    $newSize += $size;
                    ++$success;
                } else {
                    $this->writeError(\sprintf('Error: %s - Unable to convert image.', $targetName));
                    ++$error;
                }
                \imagedestroy($image);
                continue;
            }

            $this->writeVerbose(\sprintf('Save    : %s.', $targetName));
            if (!$this->saveImage($image, $targetFile)) {
                $this->writeError(\sprintf('Error: %s - Unable to convert image.', $targetName));
                \imagedestroy($image);
                ++$error;
                continue;
            }

            $oldSize += FileUtils::size($path);
            $newSize += FileUtils::size($targetFile);
            \imagedestroy($image);
            ++$success;
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
            // @phpstan-ignore-next-line
            $this->io->error($message);

            return Command::FAILURE;
        }
        // @phpstan-ignore-next-line
        $this->io->success($message);

        return Command::SUCCESS;
    }

    private function createFinder(string $path, int $depth): ?Finder
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
        $this->io?->warning(\sprintf('No image found in directory "%s".', $path));

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

    /**
     * @return array{0: bool, 1: int}
     */
    private function saveImageTemp(\GdImage $image): array
    {
        $temp = FileUtils::tempFile();
        if (!\is_string($temp)) {
            return [false, 0];
        }

        try {
            $result = $this->saveImage($image, $temp);
            $size = FileUtils::size($temp);

            return [$result, $size];
        } finally {
            FileUtils::remove($temp);
        }
    }

    private function validateDepth(mixed $depth): bool
    {
        if (!\is_numeric($depth)) {
            $this->io?->error(\sprintf('Depth argument must be of type int, %s given.', \get_debug_type($depth)));

            return false;
        }
        if ((int) $depth < 0) {
            $this->io?->error(\sprintf('Depth argument must be greater than or equal to 0, %d given.', $depth));

            return false;
        }

        return true;
    }

    private function validateSource(string $path): bool
    {
        if (!FileUtils::exists($path)) {
            $this->io?->error(\sprintf('Unable to find the source directory: "%s".', $path));

            return false;
        }
        if (!FileUtils::isDir($path)) {
            $this->io?->error(\sprintf('The source "%s" is not a directory.', $path));

            return false;
        }

        return true;
    }

    private function writeError(string $message): void
    {
        $this->io?->writeln('<error>' . $message . '</error>');
    }

    private function writeVerbose(string $message): void
    {
        if ($this->io?->isVerbose()) {
            $this->io->writeln($message);
        }
    }
}
