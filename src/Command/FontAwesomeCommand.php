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
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

/**
 * Command to copy SVG files from the font-awesome package.
 */
#[AsCommand(name: 'app:fontawesome', description: 'Copy SVG files from the font-awesome package.')]
class FontAwesomeCommand extends Command
{
    private const DEFAULT_SOURCE = 'vendor/fortawesome/font-awesome/svgs';
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
            self::SOURCE_ARGUMENT,
            InputArgument::OPTIONAL,
            'The source directory, relative to the project directory, where copy the SVG files from.',
            default: self::DEFAULT_SOURCE
        );
        $this->addArgument(
            self::TARGET_ARGUMENT,
            InputArgument::OPTIONAL,
            'The target directory, relative to the project directory, where copy SVG files to.',
            default: self::DEFAULT_TARGET
        );
        $this->addOption(
            self::DRY_RUN_OPTION,
            'd',
            InputOption::VALUE_NONE,
            'Run the command without making changes to existing files (simulate copy).'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $source = $this->getSourceDirectory($io);
        if (!FileUtils::isDir($source)) {
            $io->error(\sprintf('Unable to find the SVG directory: "%s".', $source));

            return Command::INVALID;
        }

        $finder = $this->createFinder($source);
        $count = $finder->count();
        if (0 === $count) {
            $io->warning(\sprintf('No image found: "%s".', $source));

            return Command::SUCCESS;
        }

        $tempDir = FileUtils::tempDir();
        if (!\is_string($tempDir)) {
            $io->error('Unable to create the temporary directory.');

            return Command::FAILURE;
        }

        try {
            $io->writeln([\sprintf('Copy files from "%s"...', $this->getRelativePath($source)), '']);
            foreach ($io->progressIterate($finder, $count) as $file) {
                $originFile = $file->getRealPath();
                $targetFile = $this->getTargetFile($tempDir, $file);
                if (!FileUtils::copy($originFile, $targetFile)) {
                    $io->error(\sprintf('Unable to copy the file: "%s".', $file->getFilename()));

                    return Command::FAILURE;
                }
            }

            if ($io->getBoolOption(self::DRY_RUN_OPTION)) {
                $io->success(\sprintf('Simulate copied %d files successfully.', $count));

                return Command::SUCCESS;
            }

            $target = $this->getTargetDirectory($io);
            $relativeTarget = $this->getRelativePath($target);
            if (FileUtils::isDir($target) && !FileUtils::remove($target)) {
                $io->error(\sprintf('Unable to remove directory: "%s".', $relativeTarget));

                return Command::FAILURE;
            }

            $io->writeln(\sprintf('Move %d files to "%s"...', $count, $relativeTarget));
            if (!FileUtils::rename($tempDir, $target)) {
                $io->error(\sprintf('Unable to copy %d files to the directory: "%s".', $count, $relativeTarget));

                return Command::FAILURE;
            }

            $io->success(\sprintf('Copied %d files successfully.', $count));

            return Command::SUCCESS;
        } finally {
            FileUtils::remove($tempDir);
        }
    }

    private function createFinder(string $path): Finder
    {
        return Finder::create()
            ->ignoreUnreadableDirs()
            ->in($path)
            ->files()
            ->name('*.svg');
    }

    private function getRelativePath(string $path): string
    {
        return FileUtils::makePathRelative($path, $this->projectDir);
    }

    private function getSourceDirectory(SymfonyStyle $io): string
    {
        $source = $io->getStringArgument(self::SOURCE_ARGUMENT);
        if ('' === $source) {
            $source = self::DEFAULT_SOURCE;
        }

        return FileUtils::buildPath($this->projectDir, $source);
    }

    private function getTargetDirectory(SymfonyStyle $io): string
    {
        $target = $io->getStringArgument(self::TARGET_ARGUMENT);
        if ('' === $target) {
            $target = self::DEFAULT_TARGET;
        }

        return FileUtils::buildPath($this->projectDir, $target);
    }

    private function getTargetFile(string $target, SplFileInfo $file): string
    {
        return FileUtils::buildPath(
            $target,
            $file->getRelativePath(),
            $file->getFilename()
        );
    }
}
