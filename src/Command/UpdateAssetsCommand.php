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

use App\Service\EnvironmentService;
use App\Utils\FileUtils;
use App\Utils\StringUtils;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Command to update JavaScript and CSS dependencies.
 *
 * @phpstan-type PluginType = array{
 *     name: string,
 *     display?: string,
 *     version: string,
 *     source: string,
 *     target?: string,
 *     disabled?: bool,
 *     update?: bool,
 *     prefix?: string,
 *     files: string[]}
 * @phpstan-type SourceType = array{
 *     source: string,
 *     format: string,
 *     versionUrl?: string,
 *     versionPaths?: string[]}
 * @phpstan-type ConfigurationType = array{
 *     target: string,
 *     plugins: PluginType[],
 *     sources: array<string, SourceType>,
 *     prefixes?: array<string, string>}
 */
#[AsCommand(name: 'app:update-assets', description: 'Update Javascript and CSS dependencies.')]
class UpdateAssetsCommand
{
    use WatchTrait;

    /**
     * The vendor file name to load.
     */
    public const VENDOR_FILE_NAME = 'vendor.json';

    private const VERSION_PATTERN = '/asset-version\s*(\d*\.\d*\.\d*)/m';

    public function __construct(
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,
        private readonly EnvironmentService $service
    ) {
    }

    public function __invoke(
        SymfonyStyle $io,
        #[Option(description: 'The root directory or null to use the project directory.')]
        ?string $directory = null,
        #[Option(description: 'The configuration file.')]
        string $file = self::VENDOR_FILE_NAME,
        #[Option(description: 'Check only for version update without replacing files.', name: 'dry-run', shortcut: 'd')]
        bool $dryRun = false
    ): int {
        $publicDir = $this->getPublicDir($io, $directory ?? $this->projectDir);
        if (null === $publicDir) {
            return Command::INVALID;
        }
        $configuration = $this->loadConfiguration($io, $publicDir, $file);
        if (null === $configuration) {
            return Command::INVALID;
        }
        if (!$this->propertyExists($io, $configuration, ['target', 'plugins', 'sources'], true)) {
            return Command::INVALID;
        }

        $target = FileUtils::buildPath($publicDir, $configuration['target']);
        if ($dryRun) {
            return $this->dryRun($io, $configuration, $target);
        }

        $targetTemp = $this->getTargetTemp($io, $publicDir);
        if (false === $targetTemp) {
            return Command::FAILURE;
        }

        $this->start();
        $countFiles = 0;
        $countPlugins = 0;
        $plugins = $configuration['plugins'];
        $prefixes = $this->getPrefixes($io, $configuration);

        try {
            foreach ($plugins as $plugin) {
                $name = $plugin['name'];
                $version = $plugin['version'];
                $display = $plugin['display'] ?? $name;
                if ($this->isPluginDisabled($plugin)) {
                    $this->writeVerbose($io, \sprintf('Disabled : %s %s', $display, $version), 'fg=gray');
                    continue;
                }
                $files = $plugin['files'];
                if ([] === $files) {
                    $this->writeVerbose(
                        $io,
                        \sprintf('Skip     : %s %s (No file defined)', $display, $version),
                        'fg=gray'
                    );
                    continue;
                }

                $pluginSource = $plugin['source'];
                if (!isset($configuration['sources'][$pluginSource])) {
                    $this->writeError(
                        $io,
                        \sprintf('Unable to get source "%s" for the plugin "%s".', $pluginSource, $display)
                    );

                    return Command::FAILURE;
                }

                $definition = $configuration['sources'][$pluginSource];
                $source = $definition['source'];
                $format = $definition['format'];
                $this->writeVerbose($io, \sprintf('Install  : %s %s', $display, $version));
                foreach ($files as $entry) {
                    $sourceFile = $this->getSourceFile($source, $format, $plugin, $entry);
                    $targetFile = $this->getTargetFile($targetTemp, $plugin, $entry);
                    if (!$this->copyFile($io, $sourceFile, $targetFile, $version, $prefixes)) {
                        return Command::FAILURE;
                    }
                    ++$countFiles;
                }
                ++$countPlugins;

                if (!$this->isPluginUpdate($plugin)) {
                    continue;
                }

                $versionUrl = $definition['versionUrl'] ?? null;
                $versionPaths = $definition['versionPaths'] ?? null;
                if (\is_string($versionUrl) && \is_array($versionPaths)) {
                    $this->checkVersion($io, $versionUrl, $versionPaths, $name, $version, $display);
                    continue;
                }

                $this->writeVerbose(
                    $io,
                    \sprintf('Check    : %s %s - No version information.', $display, $version),
                    'fg=gray'
                );
            }
            $expected = $this->countFiles($plugins);
            if ($expected !== $countFiles) {
                $this->writeError(
                    $io,
                    \sprintf('Not all files has been loaded! Expected: %d, Loaded: %d', $expected, $countFiles)
                );
            }

            if (!$this->copyToTarget($io, $targetTemp, $target)) {
                return Command::FAILURE;
            }

            $io->success(
                \sprintf(
                    'Installed %d plugins and %d files to the directory "%s". %s.',
                    $countPlugins,
                    $countFiles,
                    $target,
                    $this->stop()
                )
            );

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $this->writeError($io, $e->getMessage());

            return Command::FAILURE;
        } finally {
            $this->remove($io, $targetTemp);
        }
    }

    private function checkAssetVersion(SymfonyStyle $io, string $filename, string $version): void
    {
        if (!FileUtils::exists($filename)) {
            return;
        }
        $content = FileUtils::readFile($filename);
        if ('' === $content) {
            return;
        }
        if (!StringUtils::pregMatch(self::VERSION_PATTERN, $content, $matches, \PREG_OFFSET_CAPTURE)) {
            return;
        }

        $assetVersion = $matches[1][0];
        if (\version_compare($assetVersion, $version, '>=')) {
            return;
        }

        $this->writeln(
            $io,
            \sprintf('✗ File %-25s %-12s Version %s available.', \basename($filename), $assetVersion, $version),
            'fg=red'
        );
    }

    /**
     * @param string[] $paths
     */
    private function checkVersion(
        SymfonyStyle $io,
        string $url,
        array $paths,
        string $name,
        string $version,
        string $display
    ): void {
        $newVersion = $this->getLastVersion($url, $paths, $name);
        if (null === $newVersion) {
            $this->writeln(
                $io,
                "Unable to find last version for the plugin '$display'.",
                'error'
            );
        } elseif (\version_compare($version, $newVersion, '<')) {
            $this->writeln(
                $io,
                \sprintf(
                    'The plugin "%s" version "%s" can be updated to the new version "%s".',
                    $display,
                    $version,
                    $newVersion
                ),
                'bg=red'
            );
        }
    }

    /**
     * @param array<string, string> $prefixes
     */
    private function copyFile(
        SymfonyStyle $io,
        string $sourceFile,
        string $targetFile,
        string $version,
        array $prefixes
    ): bool {
        $content = $this->readFile($io, $sourceFile);
        if (\is_string($content)) {
            return $this->dumpFile($io, $content, $targetFile, $version, $prefixes);
        }

        return false;
    }

    private function copyToTarget(SymfonyStyle $io, string $source, string $target): bool
    {
        if ($this->service->isTest()) {
            return true;
        }

        $this->writeVerbose($io, \sprintf('Rename directory "%s" to "%s".', $source, $target));
        if ($this->mirror($io, $source, $target)) {
            return true;
        }

        $this->writeError($io, \sprintf('Unable rename directory "%s" to "%s".', $source, $target));

        return false;
    }

    /**
     * @phpstan-param PluginType[] $plugins
     */
    private function countFiles(array $plugins): int
    {
        $plugins = \array_filter(
            $plugins,
            /** @phpstan-param PluginType $plugin */
            fn (array $plugin): bool => !$this->isPluginDisabled($plugin)
        );

        return \array_reduce(
            $plugins,
            /** @phpstan-param PluginType $plugin */
            fn (int $carry, array $plugin): int => $carry + \count($plugin['files']),
            0
        );
    }

    /**
     * @phpstan-param  ConfigurationType $configuration
     */
    private function dryRun(SymfonyStyle $io, array $configuration, string $target): int
    {
        $this->start();
        $this->writeln($io, 'Check versions:');
        $pattern = '%s %-30s %-12s %s';

        $plugins = $configuration['plugins'];
        foreach ($plugins as $plugin) {
            $name = $plugin['name'];
            $version = $plugin['version'];
            $display = $plugin['display'] ?? $name;
            if ($this->isPluginDisabled($plugin)) {
                $this->writeln($io, \sprintf($pattern, '✗', $display, $version, 'Disabled.'), 'fg=gray');
                continue;
            }
            if (!$this->isPluginUpdate($plugin)) {
                $this->writeln($io, \sprintf($pattern, '✗', $display, $version, 'Skip Update.'), 'fg=gray');
                continue;
            }

            $source = $plugin['source'];
            $definition = $configuration['sources'][$source];
            $versionUrl = $definition['versionUrl'] ?? null;
            $versionPaths = $definition['versionPaths'] ?? null;
            if (\is_string($versionUrl) && \is_array($versionPaths)) {
                $newVersion = $this->getLastVersion($versionUrl, $versionPaths, $name);
                if (null === $newVersion) {
                    $this->writeln(
                        $io,
                        \sprintf($pattern, '✗', $display, $version, 'Unable to find version.'),
                        'fg=red'
                    );
                } elseif (\version_compare($version, $newVersion, '<')) {
                    $this->writeln(
                        $io,
                        \sprintf('✗ %-30s %-12s Version %s available.', $display, $version, $newVersion),
                        'fg=red'
                    );
                } else {
                    $this->writeln($io, \sprintf('✓ %-30s %-12s', $display, $version));
                    foreach ($plugin['files'] as $file) {
                        $existingFile = $this->getTargetFile($target, $plugin, $file);
                        $this->checkAssetVersion($io, $existingFile, $version);
                    }
                }
            } else {
                $this->writeln(
                    $io,
                    \sprintf($pattern, '✗', $display, $version, 'No version information.'),
                    'fg=gray'
                );
            }
        }

        $io->success(\sprintf('Checked versions successfully. %s.', $this->stop()));

        return Command::SUCCESS;
    }

    /**
     * @param array<string, string> $prefixes
     */
    private function dumpFile(
        SymfonyStyle $io,
        string $content,
        string $targetFile,
        string $version,
        array $prefixes
    ): bool {
        // prefix file
        $extension = FileUtils::getExtension($targetFile);
        if ('' !== $extension && \array_key_exists($extension, $prefixes)) {
            $prefix = \str_replace('$version', $version, $prefixes[$extension]);
            $content = $prefix . $content;
        }

        // save
        if (!$this->writeFile($io, $targetFile, $content)) {
            return false;
        }
        FileUtils::chmod($targetFile, 0o644, false);

        return true;
    }

    /**
     * @param string[] $paths
     */
    private function getLastVersion(string $url, array $paths, string $name): ?string
    {
        try {
            $url = \str_ireplace('{name}', $name, $url);
            $content = FileUtils::decodeJson($url);
            foreach ($paths as $path) {
                if (!isset($content[$path])) {
                    return null;
                }
                /** @var array|string $content */
                $content = $content[$path];
            }

            /** @phpstan-var string */
            return $content;
        } catch (\Exception) {
            return null;
        }
    }

    /**
     * @return array<string, string>
     */
    private function getPrefixes(SymfonyStyle $io, array $configuration): array
    {
        if ($this->propertyExists($io, $configuration, 'prefixes')) {
            /** @phpstan-var array<string, string> */
            return $configuration['prefixes'];
        }

        return [];
    }

    private function getPublicDir(SymfonyStyle $io, string $projectDir): ?string
    {
        $publicDir = FileUtils::buildPath($projectDir, 'public');
        if (FileUtils::exists($publicDir)) {
            return $publicDir;
        }
        $this->writeNote($io, 'No public directory found.');

        return null;
    }

    /**
     * @phpstan-param PluginType $plugin
     */
    private function getSourceFile(string $source, string $format, array $plugin, string $file): string
    {
        $name = $plugin['name'];
        $version = $plugin['version'];

        return \str_ireplace(
            ['{source}', '{name}', '{version}', '{file}'],
            [$source, $name, $version, $file],
            $format
        );
    }

    /**
     * @phpstan-param PluginType $plugin
     */
    private function getTargetFile(string $target, array $plugin, string $file): string
    {
        $name = $plugin['target'] ?? $plugin['name'];
        $prefix = $plugin['prefix'] ?? '';
        if (StringUtils::startWith($file, $prefix)) {
            $file = \substr($file, \strlen($prefix));
        }

        return FileUtils::buildPath($target, $name, $file);
    }

    private function getTargetTemp(SymfonyStyle $io, string $publicDir): string|false
    {
        $dir = FileUtils::tempDir($publicDir);
        if (null === $dir) {
            $this->writeError($io, 'Unable to create a temporary directory.');

            return false;
        }

        return $dir . \DIRECTORY_SEPARATOR;
    }

    /**
     * @phpstan-param PluginType $plugin
     */
    private function isPluginDisabled(array $plugin): bool
    {
        return $plugin['disabled'] ?? false;
    }

    /**
     * @phpstan-param PluginType $plugin
     */
    private function isPluginUpdate(array $plugin): bool
    {
        return $plugin['update'] ?? true;
    }

    /**
     * @phpstan-return ConfigurationType|null
     */
    private function loadConfiguration(SymfonyStyle $io, string $publicDir, string $file): ?array
    {
        $vendorFile = FileUtils::buildPath($publicDir, $file);
        if (!FileUtils::exists($vendorFile)) {
            $this->writeVerbose($io, \sprintf('The file "%s" does not exist.', $vendorFile));

            return null;
        }

        /** @phpstan-var ConfigurationType|null $configuration */
        $configuration = $this->loadJson($io, $vendorFile);

        if (!\is_array($configuration)) {
            $this->writeNote($io, 'No configuration found.');

            return null;
        }

        return $configuration;
    }

    private function loadJson(SymfonyStyle $io, string $filename): ?array
    {
        try {
            return FileUtils::decodeJson($filename);
        } catch (\Throwable $e) {
            $this->writeError($io, $e->getMessage());
            $this->writeError($io, \sprintf('Unable to decode file "%s".', $filename));

            return null;
        }
    }

    private function mirror(SymfonyStyle $io, string $origin, string $target): bool
    {
        $this->writeVeryVerbose($io, \sprintf('Rename "%s" to "%s".', $origin, $target));
        if (FileUtils::mirror($origin, $target, delete: true)) {
            return true;
        }
        $this->writeError($io, \sprintf('Unable to rename the file "%s" to "%s".', $origin, $target));

        return false;
    }

    /**
     * @param string[]|string $properties
     */
    private function propertyExists(SymfonyStyle $io, array $var, array|string $properties, bool $log = false): bool
    {
        $properties = (array) $properties;
        foreach ($properties as $property) {
            if (isset($var[$property])) {
                continue;
            }
            if ($log) {
                $this->writeError($io, \sprintf('Unable to find the property "%s".', $property));
            }

            return false;
        }

        return true;
    }

    private function readFile(SymfonyStyle $io, string $filename): string|false
    {
        $this->writeVeryVerbose($io, \sprintf('Load "%s".', $filename));
        $content = FileUtils::readFile($filename);
        if ('' !== $content) {
            return $content;
        }
        $this->writeError($io, \sprintf('Unable to get content of "%s".', $filename));

        return false;
    }

    private function remove(SymfonyStyle $io, string $file): void
    {
        if (FileUtils::exists($file)) {
            $this->writeVeryVerbose($io, \sprintf('Remove "%s".', $file));
            FileUtils::remove($file);
        }
    }

    private function writeError(SymfonyStyle $io, string $message): void
    {
        $io->error($message);
    }

    private function writeFile(SymfonyStyle $io, string $filename, string $content): bool
    {
        $this->writeVeryVerbose($io, \sprintf('Save "%s"', $filename));
        if (FileUtils::dumpFile($filename, $content)) {
            return true;
        }
        $this->writeError($io, \sprintf('Unable to write content to the file "%s".', $filename));

        return false;
    }

    private function writeln(SymfonyStyle $io, string $message, string $style = 'info'): void
    {
        $io->writeln("<$style>$message</>");
    }

    private function writeNote(SymfonyStyle $io, string $message): void
    {
        $io->note($message);
    }

    private function writeVerbose(SymfonyStyle $io, string $message, string $style = 'info'): void
    {
        if ($io->isVerbose()) {
            $this->writeln($io, $message, $style);
        }
    }

    private function writeVeryVerbose(SymfonyStyle $io, string $message): void
    {
        if ($io->isVeryVerbose()) {
            $this->writeln($io, $message);
        }
    }
}
