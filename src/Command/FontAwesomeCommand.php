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
use Symfony\Component\Filesystem\Path;

/**
 * @phpstan-type RawType = array{raw: string}
 * @phpstan-type SvgType = array<string, RawType>
 * @phpstan-type ContentType = array<array-key, array{styles: string[], svg: SvgType}>
 */
#[AsCommand(name: 'app:fontawesome', description: 'Copy SVG files and aliases from the font-awesome package.')]
class FontAwesomeCommand
{
    use WatchTrait;

    private const string DEFAULT_SOURCE = 'vendor/fortawesome/font-awesome/metadata/icons.json';
    private const string DEFAULT_TARGET = 'resources/fontawesome';
    private const string VIEW_BOX_PATTERN = '/(viewBox="\d+\s+\d+\s+\d+\s+\d+")/i';
    private const string VIEW_BOX_REPLACE = 'viewBox="0 0 640 640"';

    public function __construct(
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir
    ) {
    }

    public function __invoke(
        SymfonyStyle $io,
        #[Argument('The absolute path to the JSON source file where to get metadata information; null to use the default file.', )]
        ?string $source = null,
        #[Argument('The absolute path to the target directory where to copy SVG files; null to use the default directory.', )]
        ?string $target = null,
        #[Option(description: 'Run the command without making changes (simulate copying SVG files).', name: 'dry-run', shortcut: 'd')]
        bool $dryRun = false
    ): int {
        $source ??= Path::join($this->projectDir, self::DEFAULT_SOURCE);
        $relativeSource = \basename($source);
        if (!\is_file($source)) {
            return $this->error($io, 'Unable to find JSON source file: "%s".', $relativeSource);
        }

        try {
            /** @phpstan-var ContentType $content */
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
            $io->title(\sprintf('Generate files from "%s"', $source));
            foreach ($io->progressIterate($content, $count) as $key => $item) {
                $styles = $item['styles'];
                foreach ($styles as $style) {
                    $svg = $item['svg'][$style]['raw'] ?? '';
                    if ('' === $svg) {
                        continue;
                    }
                    $svg = $this->replaceViewBox($svg);
                    $svgFileName = $this->getSvgFileName($style, $key);
                    $svgTargetFile = Path::join($tempDir, $svgFileName);
                    if (!FileUtils::dumpFile($svgTargetFile, $svg)) {
                        return $this->error($io, 'Unable to copy file: "%s".', $svgFileName);
                    }
                    ++$files;
                }
            }

            if ($dryRun) {
                return $this->success(
                    $io,
                    'Simulate command successfully: %d file(s) from %d source(s). %s.',
                    $files,
                    $count,
                    $this->stop()
                );
            }

            $target ??= Path::join($this->projectDir, self::DEFAULT_TARGET);
            $relativeTarget = \basename($target);
            $io->writeln(\sprintf('Copy files to "%s"...', $relativeTarget));
            if (!FileUtils::mirror(origin: $tempDir, target: $target, delete: true)) {
                return $this->error($io, 'Unable to copy %d file(s) to the directory: "%s".', $count, $relativeTarget);
            }

            return $this->success(
                $io,
                'Generate images successfully: %d file(s) from %d source(s). %s.',
                $files,
                $count,
                $this->stop()
            );
        } finally {
            FileUtils::remove($tempDir);
        }
    }

    private function error(SymfonyStyle $io, string $format, string|int ...$parameters): int
    {
        $io->error(\sprintf($format, ...$parameters));

        return Command::FAILURE;
    }

    private function getSvgFileName(string $style, string|int $name): string
    {
        return \sprintf('%s/%s%s', $style, $name, FontAwesomeImageService::SVG_EXTENSION);
    }

    private function replaceViewBox(string $svg): string
    {
        return StringUtils::pregReplace(self::VIEW_BOX_PATTERN, self::VIEW_BOX_REPLACE, $svg);
    }

    private function success(SymfonyStyle $io, string $format, string|int ...$parameters): int
    {
        $io->success(\sprintf($format, ...$parameters));

        return Command::SUCCESS;
    }
}
