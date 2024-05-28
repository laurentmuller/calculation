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
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

/**
 * Command to add relative path header to Twig templates.
 */
#[AsCommand(name: 'app:template:name', description: 'Add the relative path header to Twig templates.')]
class TemplateNameCommand extends Command
{
    private const FILES_PATTERN = '*.html.twig';
    private const NEW_LINE = "\n";
    private const OPTION_DEPTH = 'depth';

    private const OPTION_DRY_RUN = 'dry-run';
    private const OPTION_PATH = 'path';

    public function __construct(
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument(
            self::OPTION_PATH,
            InputOption::VALUE_REQUIRED,
            'The templates path relative to the project directory.',
            '/templates'
        );
        $this->addOption(
            self::OPTION_DEPTH,
            null,
            InputOption::VALUE_REQUIRED,
            'How depth search in the directory (-1 = all).',
            -1
        );
        $this->addOption(
            self::OPTION_DRY_RUN,
            'd',
            InputOption::VALUE_NONE,
            'Simulate update without modify templates.'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $depth = $io->getIntOption(self::OPTION_DEPTH);
        $dryRun = $io->getBoolOption(self::OPTION_DRY_RUN);
        $root_path = $io->getStringArgument(self::OPTION_PATH);
        $full_path = FileUtils::buildPath($this->projectDir, $root_path);

        $finder = Finder::create()
            ->in($full_path)
            ->name(self::FILES_PATTERN)
            ->files();
        if ($depth >= 0) {
            $finder->depth(['>= 0', \sprintf('<= %d', $depth)]);
        }
        if (!$finder->hasResults()) {
            $io->note(\sprintf('No template found in directory "%s".', $root_path));

            return Command::SUCCESS;
        }

        $io->writeln(\sprintf('Finding files in directory "%s":', $root_path));

        $skip = 0;
        $update = 0;
        $files = [];
        foreach ($io->progressIterate($finder) as $file) {
            $path = $this->getTemplatePath($file);
            $contents = $this->updateContent($path, $file->getContents());
            if (false === $contents) {
                ++$skip;
                continue;
            }
            if (!$dryRun) {
                $this->setContents($file, $contents);
            }
            $files[] = $path;
            ++$update;
        }

        if ([] !== $files) {
            $io->writeln('Templates updated:');
            $io->listing($files);
        }
        
        if ($dryRun) {
            $io->success(\sprintf('Simulate: Skipped %d, Updated %d.', $skip, $update));
        } else {
            $io->success(\sprintf('Skipped %d, Updated %d.', $skip, $update));
        }

        return Command::SUCCESS;
    }

    private function getTemplatePath(SplFileInfo $file): string
    {
        $root = FileUtils::normalize($this->projectDir);
        $path = FileUtils::normalize($file->getRealPath());
        $relative = \substr($path, \strlen($root));

        return \ltrim($relative, '/');
    }

    private function setContents(SplFileInfo $file, string $contents): void
    {
        \file_put_contents($file->getRealPath(), $contents);
    }

    private function updateContent(string $path, string $content): string|false
    {
        /** @psalm-var string[] $lines */
        $lines = \explode(self::NEW_LINE, $content);
        if ([] === $lines) {
            return false;
        }

        $line = \sprintf('{# %s #}', $path);
        if ($lines[0] === $line) {
            return false;
        }

        $baseName = \basename($path);
        if (\str_contains($lines[0], $baseName)) {
            $lines[0] = $line;
        } else {
            \array_unshift($lines, $line);
        }

        return \implode(self::NEW_LINE, $lines);
    }
}
