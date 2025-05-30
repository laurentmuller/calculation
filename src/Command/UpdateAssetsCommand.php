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
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
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
class UpdateAssetsCommand extends Command
{
    use LoggerTrait;

    private const ASSET_VERSION_PATTERN = '/asset-version\s*(\d*\.\d*\.\d*)/m';

    /**
     * The dry-run option.
     */
    private const DRY_RUN_OPTION = 'dry-run';

    /**
     * The configuration file name.
     */
    private const VENDOR_FILE_NAME = 'vendor.json';

    public function __construct(
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,
        private readonly EnvironmentService $service
    ) {
        parent::__construct();
    }

    #[\Override]
    protected function configure(): void
    {
        $this->addOption(
            self::DRY_RUN_OPTION,
            'd',
            InputOption::VALUE_NONE,
            'Check only for version update without replacing files.'
        );
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new SymfonyStyle($input, $output);

        $publicDir = $this->getPublicDir();
        if (null === $publicDir) {
            return Command::INVALID;
        }
        $configuration = $this->loadConfiguration($publicDir);
        if (null === $configuration) {
            return Command::INVALID;
        }
        if (!$this->propertyExists($configuration, ['target', 'plugins', 'sources'], true)) {
            return Command::INVALID;
        }

        $target = FileUtils::buildPath($publicDir, $configuration['target']);
        if ($this->io->getBoolOption(self::DRY_RUN_OPTION)) {
            return $this->dryRun($configuration, $target);
        }

        $targetTemp = $this->getTargetTemp($publicDir);
        if (false === $targetTemp) {
            return Command::FAILURE;
        }

        $countFiles = 0;
        $countPlugins = 0;
        $startTime = \time();
        $plugins = $configuration['plugins'];
        $prefixes = $this->getConfigArray($configuration, 'prefixes');

        try {
            foreach ($plugins as $plugin) {
                $name = $plugin['name'];
                $version = $plugin['version'];
                $display = $plugin['display'] ?? $name;
                if ($this->isPluginDisabled($plugin)) {
                    $this->writeVerbose(\sprintf('Disabled : %s %s', $display, $version), 'fg=gray');
                    continue;
                }
                $files = $plugin['files'];
                if ([] === $files) {
                    $this->writeVerbose(\sprintf('Skip     : %s %s (No file defined)', $display, $version), 'fg=gray');
                    continue;
                }

                $pluginSource = $plugin['source'];
                if (!isset($configuration['sources'][$pluginSource])) {
                    $this->writeError(
                        \sprintf('Unable to get source "%s" for the plugin "%s".', $pluginSource, $display)
                    );

                    return Command::FAILURE;
                }

                $definition = $configuration['sources'][$pluginSource];
                $source = $definition['source'];
                $format = $definition['format'];
                $this->writeVerbose(\sprintf('Install  : %s %s', $display, $version));
                foreach ($files as $file) {
                    $sourceFile = $this->getSourceFile($source, $format, $plugin, $file);
                    $targetFile = $this->getTargetFile($targetTemp, $plugin, $file);
                    if (!$this->copyFile($sourceFile, $targetFile, $version, $prefixes)) {
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
                    $this->checkVersion($versionUrl, $versionPaths, $name, $version, $display);
                    continue;
                }

                $this->writeVerbose(
                    \sprintf('Check    : %s %s - No version information.', $display, $version),
                    'fg=gray'
                );
            }
            $expected = $this->countFiles($plugins);
            if ($expected !== $countFiles) {
                $this->writeError(
                    \sprintf('Not all files has been loaded! Expected: %d, Loaded: %d', $expected, $countFiles)
                );
            }

            if (!$this->copyToTarget($targetTemp, $target)) {
                return Command::FAILURE;
            }

            $duration = $this->formatDuration($startTime);
            $this->writeSuccess(
                \sprintf(
                    'Installed %d plugins and %d files to the directory "%s". Duration: %s.',
                    $countPlugins,
                    $countFiles,
                    $target,
                    $duration
                )
            );

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $this->writeError($e->getMessage());

            return Command::FAILURE;
        } finally {
            $this->remove($targetTemp);
        }
    }

    private function checkAssetVersion(string $filename, string $version): bool
    {
        if (!FileUtils::exists($filename)) {
            return false;
        }
        $content = FileUtils::readFile($filename);
        if ('' === $content) {
            return false;
        }
        if (!StringUtils::pregMatch(self::ASSET_VERSION_PATTERN, $content, $matches, \PREG_OFFSET_CAPTURE)) {
            return false;
        }

        $assetVersion = $matches[1][0];
        if (\version_compare($assetVersion, $version, '>=')) {
            return false;
        }

        $this->writeln(\sprintf('✗ File %-25s %-12s Version %s available.', \basename($filename), $assetVersion, $version), 'fg=red');

        return true;
    }

    /**
     * @param string[] $paths
     */
    private function checkVersion(string $url, array $paths, string $name, string $version, string $display): void
    {
        $newVersion = $this->getLastVersion($url, $paths, $name);
        if (null === $newVersion) {
            $this->writeln("Unable to find last version for the plugin '$display'.", 'error');
        } elseif (\version_compare($version, $newVersion, '<')) {
            $this->writeln(\sprintf(
                'The plugin "%s" version "%s" can be update to the new version "%s".',
                $display,
                $version,
                $newVersion
            ), 'bg=red');
        }
    }

    /**
     * @param array<string, string> $prefixes
     */
    private function copyFile(string $sourceFile, string $targetFile, string $version, array $prefixes): bool
    {
        $content = $this->readFile($sourceFile);
        if (\is_string($content)) {
            return $this->dumpFile($content, $targetFile, $version, $prefixes);
        }

        return false;
    }

    private function copyToTarget(string $source, string $target): bool
    {
        if ($this->service->isTest()) {
            return true;
        }

        $this->writeVerbose(\sprintf('Rename directory "%s" to "%s".', $source, $target));
        if ($this->mirror($source, $target)) {
            return true;
        }

        $this->writeError(\sprintf('Unable rename directory "%s" to "%s".', $source, $target));

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
    private function dryRun(array $configuration, string $target): int
    {
        $startTime = \time();
        $this->writeln('Check versions:');
        $pattern = '%s %-30s %-12s %s';

        $plugins = $configuration['plugins'];
        foreach ($plugins as $plugin) {
            $name = $plugin['name'];
            $version = $plugin['version'];
            $display = $plugin['display'] ?? $name;
            if ($this->isPluginDisabled($plugin)) {
                $this->writeln(\sprintf($pattern, '✗', $display, $version, 'Disabled.'), 'fg=gray');
                continue;
            }
            if (!$this->isPluginUpdate($plugin)) {
                $this->writeln(\sprintf($pattern, '✗', $display, $version, 'Skip Update.'), 'fg=gray');
                continue;
            }

            $source = $plugin['source'];
            $definition = $configuration['sources'][$source];
            $versionUrl = $definition['versionUrl'] ?? null;
            $versionPaths = $definition['versionPaths'] ?? null;
            if (\is_string($versionUrl) && \is_array($versionPaths)) {
                $newVersion = $this->getLastVersion($versionUrl, $versionPaths, $name);
                if (null === $newVersion) {
                    $this->writeln(\sprintf($pattern, '✗', $display, $version, 'Unable to find version.'), 'fg=red');
                } elseif (\version_compare($version, $newVersion, '<')) {
                    $this->writeln(\sprintf('✗ %-30s %-12s Version %s available.', $display, $version, $newVersion), 'fg=red');
                } else {
                    $this->writeln(\sprintf('✓ %-30s %-12s', $display, $version));
                    foreach ($plugin['files'] as $file) {
                        $existingFile = $this->getTargetFile($target, $plugin, $file);
                        $this->checkAssetVersion($existingFile, $version);
                    }
                }
            } else {
                $this->writeln(\sprintf($pattern, '✗', $display, $version, 'No version information.'), 'fg=gray');
            }
        }

        $duration = $this->io?->formatDuration($startTime) ?? 'Unknown';
        $this->writeSuccess(\sprintf('Checked versions successfully. Duration: %s.', $duration));

        return Command::SUCCESS;
    }

    /**
     * @param array<string, string> $prefixes
     */
    private function dumpFile(string $content, string $targetFile, string $version, array $prefixes): bool
    {
        // prefix file
        $extension = FileUtils::getExtension($targetFile);
        if ('' !== $extension && \array_key_exists($extension, $prefixes)) {
            $prefix = \str_replace('$version', $version, $prefixes[$extension]);
            $content = $prefix . $content;
        }

        // save
        if (!$this->writeFile($targetFile, $content)) {
            return false;
        }
        FileUtils::chmod($targetFile, 0o644, false);

        return true;
    }

    private function formatDuration(int $startTime): string
    {
        return (string) $this->io?->formatDuration($startTime);
    }

    /**
     * @return array<string, string>
     */
    private function getConfigArray(array $configuration, string $name): array
    {
        if ($this->propertyExists($configuration, $name)) {
            /** @var array<string, string> $array */
            $array = $configuration[$name];

            return $array;
        }

        return [];
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

    private function getPublicDir(): ?string
    {
        $publicDir = FileUtils::buildPath($this->projectDir, 'public');
        if (FileUtils::exists($publicDir)) {
            return $publicDir;
        }
        $this->writeNote('No public directory found.');

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

    private function getTargetTemp(string $publicDir): string|false
    {
        $dir = FileUtils::tempDir($publicDir);
        if (null === $dir) {
            $this->writeError('Unable to create a temporary directory.');

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
    private function loadConfiguration(string $publicDir): ?array
    {
        $vendorFile = FileUtils::buildPath($publicDir, self::VENDOR_FILE_NAME);
        if (!FileUtils::exists($vendorFile)) {
            $this->writeVerbose(\sprintf('The file "%s" does not exist.', $vendorFile));

            return null;
        }

        /** @phpstan-var ConfigurationType|null $configuration */
        $configuration = $this->loadJson($vendorFile);

        if (!\is_array($configuration)) {
            $this->writeNote('No configuration found.');

            return null;
        }

        return $configuration;
    }

    private function loadJson(string $filename): ?array
    {
        try {
            return FileUtils::decodeJson($filename);
        } catch (\Throwable $e) {
            $this->writeError($e->getMessage());
            $this->writeError(\sprintf('Unable to decode file "%s".', $filename));

            return null;
        }
    }

    private function mirror(string $origin, string $target): bool
    {
        $this->writeVeryVerbose(\sprintf('Rename "%s" to "%s".', $origin, $target));
        if (FileUtils::mirror($origin, $target, delete: true)) {
            return true;
        }
        $this->writeError(\sprintf('Unable to rename the file "%s" to "%s".', $origin, $target));

        return false;
    }

    /**
     * @param string[]|string $properties
     */
    private function propertyExists(array $var, array|string $properties, bool $log = false): bool
    {
        $properties = (array) $properties;
        foreach ($properties as $property) {
            if (isset($var[$property])) {
                continue;
            }
            if ($log) {
                $this->writeError(\sprintf('Unable to find the property "%s".', $property));
            }

            return false;
        }

        return true;
    }

    private function readFile(string $filename): string|false
    {
        $this->writeVeryVerbose(\sprintf('Load "%s".', $filename));
        $content = FileUtils::readFile($filename);
        if ('' !== $content) {
            return $content;
        }
        $this->writeError(\sprintf('Unable to get content of "%s".', $filename));

        return false;
    }

    private function remove(string $file): void
    {
        if (FileUtils::exists($file)) {
            $this->writeVeryVerbose(\sprintf('Remove "%s".', $file));
            FileUtils::remove($file);
        }
    }

    private function writeFile(string $filename, string $content): bool
    {
        $this->writeVeryVerbose(\sprintf('Save "%s"', $filename));
        if (FileUtils::dumpFile($filename, $content)) {
            return true;
        }
        $this->writeError(\sprintf('Unable to write content to the file "%s".', $filename));

        return false;
    }
}
