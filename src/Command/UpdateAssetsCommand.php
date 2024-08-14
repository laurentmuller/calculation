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
 * @psalm-type PluginType = array{
 *     name: string,
 *     display?: string,
 *     version: string,
 *     source: string,
 *     target?: string,
 *     disabled?: bool,
 *     prefix?: string,
 *     files: string[]}
 * @psalm-type CopyEntryType = array{
 *     source: string,
 *     target: string,
 *     entries: string[]}
 * @psalm-type SourceType = array{
 *     source: string,
 *     format: string,
 *     versionUrl?: string,
 *     versionPaths?: string[]}
 * @psalm-type ConfigurationType = array{
 *     target: string,
 *     plugins: PluginType[],
 *     sources: array<string, SourceType>,
 *     prefixes?: array<string, string>}
 */
#[AsCommand(name: 'app:update-assets', description: 'Update Javascript and CSS dependencies.')]
class UpdateAssetsCommand extends Command
{
    use LoggerTrait;

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

    protected function configure(): void
    {
        $this->addOption(
            self::DRY_RUN_OPTION,
            'd',
            InputOption::VALUE_NONE,
            'Check only for version update without replacing files.'
        );
    }

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
        if ($this->io->getBoolOption(self::DRY_RUN_OPTION)) {
            return $this->dryRun($configuration);
        }

        $target = FileUtils::buildPath($publicDir, $configuration['target']);
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
                    $this->writeVerbose(\sprintf('Skip   : %s %s', $display, $version), 'fg=gray');
                    continue;
                }
                $files = $plugin['files'];
                if ([] === $files) {
                    $this->writeVerbose(\sprintf('Skip   : %s %s (No file defined)', $display, $version), 'fg=gray');
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

                $this->writeVerbose(\sprintf('Install: %s %s', $display, $version));
                foreach ($files as $file) {
                    $sourceFile = $this->getSourceFile($source, $format, $plugin, $file);
                    $targetFile = $this->getTargetFile($targetTemp, $plugin, $file);
                    if ($this->copyFile($sourceFile, $targetFile, $prefixes)) {
                        ++$countFiles;
                    }
                }
                ++$countPlugins;

                $versionUrl = $definition['versionUrl'] ?? null;
                $versionPaths = $definition['versionPaths'] ?? null;
                if (\is_string($versionUrl) && \is_array($versionPaths)) {
                    $this->checkVersion($versionUrl, $versionPaths, $name, $version, $display);
                } else {
                    $this->writeVerbose(
                        \sprintf('Check  : %s %s - No version information.', $display, $version),
                        'fg=gray'
                    );
                }
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

            // @phpstan-ignore method.nonObject
            $duration = $this->io->formatDuration($startTime);
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
        } catch (\Exception $e) {
            $this->writeError($e->getMessage());

            return Command::FAILURE;
        } finally {
            $this->remove($targetTemp);
        }
    }

    /**
     * @psalm-param string[] $paths
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
     * @psalm-param array<string, string> $prefixes
     */
    private function copyFile(string $sourceFile, string $targetFile, array $prefixes): bool
    {
        $content = $this->readFile($sourceFile);
        if (\is_string($content)) {
            return $this->dumpFile($content, $targetFile, $prefixes);
        }

        return false;
    }

    private function copyToTarget(string $source, string $target): bool
    {
        if ($this->service->isTest()) {
            return true;
        }

        $this->writeVerbose(\sprintf('Rename directory "%s" to "%s".', $source, $target));
        if ($this->rename($source, $target)) {
            return true;
        }

        $this->writeError(\sprintf('Unable rename directory "%s" to "%s".', $source, $target));

        return false;
    }

    /**
     * @psalm-param PluginType[] $plugins
     */
    private function countFiles(array $plugins): int
    {
        return \array_reduce($plugins, function (int $carry, array $plugin): int {
            /** @psalm-var PluginType $plugin */
            if (!$this->isPluginDisabled($plugin)) {
                return $carry + \count($plugin['files']);
            }

            return $carry;
        }, 0);
    }

    /**
     * @psalm-param  ConfigurationType $configuration
     */
    private function dryRun(array $configuration): int
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
     * @psalm-param array<string, string> $prefixes
     */
    private function dumpFile(string $content, string $targetFile, array $prefixes): bool
    {
        // prefix file
        $extension = FileUtils::getExtension($targetFile);
        if ('' !== $extension && \array_key_exists($extension, $prefixes)) {
            $content = $prefixes[$extension] . $content;
        }

        // save
        if (!$this->writeFile($targetFile, $content)) {
            return false;
        }
        FileUtils::chmod($targetFile, 0o644, false);

        return true;
    }

    /**
     * @psalm-return array<string, string>
     */
    private function getConfigArray(array $configuration, string $name): array
    {
        if ($this->propertyExists($configuration, $name)) {
            /** @psalm-var array<string, string> $array */
            $array = $configuration[$name];

            return $array;
        }

        return [];
    }

    /**
     * @psalm-param string[] $paths
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
                /** @psalm-var array|string $content */
                $content = $content[$path];
            }

            /** @psalm-var string */
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
     * @psalm-param PluginType $plugin
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
     * @psalm-param PluginType $plugin
     */
    private function getTargetFile(string $target, array $plugin, string $file): string
    {
        $name = $plugin['target'] ?? $plugin['name'];
        $prefix = $plugin['prefix'] ?? '';
        if ('' !== $prefix && StringUtils::startWith($file, $prefix)) {
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
     * @psalm-param PluginType $plugin
     */
    private function isPluginDisabled(array $plugin): bool
    {
        return isset($plugin['disabled']) && $plugin['disabled'];
    }

    /**
     * @psalm-return ConfigurationType|null
     */
    private function loadConfiguration(string $publicDir): ?array
    {
        $vendorFile = FileUtils::buildPath($publicDir, self::VENDOR_FILE_NAME);
        if (!FileUtils::exists($vendorFile)) {
            $this->writeVerbose(\sprintf('The file "%s" does not exist.', $vendorFile));

            return null;
        }

        /** @psalm-var ConfigurationType|null $configuration */
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
        } catch (\Exception $e) {
            $this->writeError($e->getMessage());
            $this->writeError(\sprintf('Unable to decode file "%s".', $filename));

            return null;
        }
    }

    /**
     * @psalm-param string[]|string $properties
     */
    private function propertyExists(array $var, array|string $properties, bool $log = false): bool
    {
        $properties = (array) $properties;
        foreach ($properties as $property) {
            if (!isset($var[$property])) {
                if ($log) {
                    $this->writeError(\sprintf('Unable to find the property "%s".', $property));
                }

                return false;
            }
        }

        return true;
    }

    private function readFile(string $filename): string|false
    {
        $this->writeVeryVerbose(\sprintf('Load "%s".', $filename));
        $content = FileUtils::readFile($filename);
        if ('' === $content) {
            $this->writeError(\sprintf('Unable to get content of "%s".', $filename));

            return false;
        }

        return $content;
    }

    private function remove(string $file): void
    {
        if (FileUtils::exists($file)) {
            $this->writeVeryVerbose(\sprintf('Remove "%s".', $file));
            FileUtils::remove($file);
        }
    }

    private function rename(string $origin, string $target): bool
    {
        $this->writeVeryVerbose(\sprintf('Rename "%s" to "%s".', $origin, $target));
        if (FileUtils::rename($origin, $target, true)) {
            return true;
        }
        $this->writeError(\sprintf('Unable to rename the file "%s" to "%s".', $origin, $target));

        return false;
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
