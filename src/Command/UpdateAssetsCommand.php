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
use App\Utils\StringUtils;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Command to update Javascript and CSS dependencies.
 *
 * @psalm-type PluginType = array{
 *     name: string,
 *     version: string,
 *     display?: string,
 *     source?: string,
 *     target?: string,
 *     format?: string,
 *     disabled?: bool,
 *     files: string[]}
 * @psalm-type CopyEntryType = array{
 *     source: string,
 *     target: string,
 *     entries: string[]}
 * @psalm-type ConfigurationType = array{
 *     source: string,
 *     target: string,
 *     format: string,
 *     plugins: PluginType[],
 *     prefixes?: array<string, string>}
 */
#[AsCommand(name: 'app:update-assets', description: 'Update Javascript and CSS dependencies.')]
class UpdateAssetsCommand extends Command
{
    use LoggerTrait;

    /**
     * The distribution directory prefix.
     */
    private const DIST_PREFIX = 'dist/';

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
        private readonly string $projectDir
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(self::DRY_RUN_OPTION, 'd', InputOption::VALUE_NONE, 'Check only for version update without replacing files.');
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
        if (!$this->propertyExists($configuration, ['source', 'target', 'format', 'plugins'], true)) {
            return Command::INVALID;
        }
        $dry_run = $input->getOption(self::DRY_RUN_OPTION);
        if ($dry_run) {
            return $this->executeDryRun($configuration);
        }
        $source = $configuration['source'];
        $target = FileUtils::buildPath($publicDir, $configuration['target']);
        $targetTemp = $this->getTargetTemp($publicDir);
        if (false === $targetTemp) {
            return Command::FAILURE;
        }

        $countFiles = 0;
        $countPlugins = 0;
        $format = $configuration['format'];
        $plugins = $configuration['plugins'];
        $prefixes = $this->getConfigArray($configuration, 'prefixes');

        try {
            foreach ($plugins as $plugin) {
                $name = $plugin['name'];
                $version = $plugin['version'];
                $display = $plugin['display'] ?? $name;
                if ($this->isPluginDisabled($plugin)) {
                    $this->writeVerbose(\sprintf('Skip   : %s v%s', $display, $version), 'fg=gray');
                    continue;
                }
                $files = $plugin['files'];
                if ([] === $files) {
                    $this->writeVerbose(\sprintf('Skip   : %s v%s (No file)', $display, $version), 'fg=gray');
                    continue;
                }
                $this->writeVerbose(\sprintf('Install: %s v%s', $display, $version));
                foreach ($files as $file) {
                    $sourceFile = $this->getSourceFile($source, $format, $plugin, $file);
                    $targetFile = $this->getTargetFile($targetTemp, $plugin, $file);
                    if ($this->copyFile($sourceFile, $targetFile, $prefixes)) {
                        ++$countFiles;
                    }
                }
                ++$countPlugins;
                $this->checkVersion($name, $version);
            }
            $expected = $this->countFiles($plugins);
            if ($expected !== $countFiles) {
                $this->writeError("Not all files has been loaded! Expected: $expected, Loaded: $countFiles.");
            }
            $this->writeVerbose("Rename directory '$targetTemp' to '$target'.");
            if (!$this->rename($targetTemp, $target)) {
                $this->writeError("Unable rename directory '$targetTemp' to '$target'.");

                return Command::FAILURE;
            }
            $this->writeSuccess("Installed $countPlugins plugins and $countFiles files to the directory '$target'.");

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->writeError($e->getMessage());

            return Command::FAILURE;
        } finally {
            $this->remove($targetTemp);
        }
    }

    private function checkVersion(string $name, string $version): bool
    {
        $url = "https://data.jsdelivr.com/v1/package/npm/$name";
        $content = $this->loadJson($url);
        if (null === $content) {
            $this->write("Unable to find the URL '$url' for the plugin '$name'.");

            return false;
        }
        foreach (['tags', 'latest'] as $path) {
            if (!isset($content[$path])) {
                $this->write("Unable to find the path '$path' for the plugin '$name'.", 'error');

                return false;
            }
            /** @psalm-var array $content */
            $content = $content[$path];
        }
        /** @var string $newVersion */
        $newVersion = $content;
        if (\version_compare($version, $newVersion, '<')) {
            $this->write("The plugin '$name' version '$version' can be updated to the version '$newVersion'.", 'error');

            return true;
        }

        return false;
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
     * @psalm-param array<string, string> $prefixes
     */
    private function dumpFile(string $content, string $targetFile, array $prefixes): bool
    {
        // prefix file
        $extension = \pathinfo($targetFile, \PATHINFO_EXTENSION);
        if (\array_key_exists($extension, $prefixes)) {
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
     * @psalm-param  ConfigurationType $configuration
     */
    private function executeDryRun(array $configuration): int
    {
        $this->write('Check versions');
        $plugins = $configuration['plugins'];
        foreach ($plugins as $plugin) {
            $name = $plugin['name'];
            $version = $plugin['version'];
            $display = $plugin['display'] ?? $name;
            if ($this->isPluginDisabled($plugin)) {
                $this->write("- $display v$version disabled.", 'fg=gray');
                continue;
            }
            if ($this->checkVersion($name, $version)) {
                continue;
            }
            $this->write("- $display v$version is up to date.");
        }

        return Command::SUCCESS;
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

    private function getPublicDir(): ?string
    {
        $publicDir = FileUtils::buildPath($this->projectDir, 'public');
        if (!FileUtils::exists($publicDir)) {
            $this->writeNote('No public directory found.');

            return null;
        }

        return $publicDir;
    }

    /**
     * @psalm-param PluginType $plugin
     */
    private function getSourceFile(string $source, string $format, array $plugin, string $file): string
    {
        $name = $plugin['name'];
        $version = $plugin['version'];
        $source = $plugin['source'] ?? $source;
        $format = $plugin['format'] ?? $format;
        $search = ['{source}', '{name}', '{version}', '{file}'];
        $replace = [$source, $name, $version, $file];

        return \str_ireplace($search, $replace, $format);
    }

    /**
     * @psalm-param PluginType $plugin
     */
    private function getTargetFile(string $target, array $plugin, string $file): string
    {
        $name = $plugin['target'] ?? $plugin['name'];
        if (StringUtils::startWith($file, self::DIST_PREFIX)) {
            $file = \substr($file, \strlen(self::DIST_PREFIX));
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
            $this->writeVerbose("The file '$vendorFile' does not exist.");

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
        $content = $this->readFile($filename);
        if (false === $content) {
            return null;
        }

        try {
            return StringUtils::decodeJson($content);
        } catch (\InvalidArgumentException $e) {
            $this->writeError($e->getMessage());
            $this->writeError("Unable to decode file '$filename'.");

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
            if (empty($var[$property])) {
                if ($log) {
                    $this->writeError("Unable to find the property '$property'.");
                }

                return false;
            }
        }

        return true;
    }

    private function readFile(string $filename): string|false
    {
        $this->writeVeryVerbose("Load '$filename'");
        $content = \file_get_contents($filename);
        if (!\is_string($content)) {
            $this->writeError("Unable to get content of '$filename'.");

            return false;
        }
        if ('' === $content) {
            $this->writeError("The content of '$filename' is empty.");

            return false;
        }

        return $content;
    }

    private function remove(string $file): void
    {
        if (FileUtils::exists($file)) {
            $this->writeVeryVerbose("Remove '$file'.");
            FileUtils::remove($file);
        }
    }

    private function rename(string $origin, string $target): bool
    {
        $this->writeVeryVerbose("Rename '$origin' to '$target'.");
        if (FileUtils::rename($origin, $target, true)) {
            return true;
        }
        $this->writeError("Unable to rename the file '$origin' to '$target'.");

        return false;
    }

    private function writeFile(string $filename, string $content): bool
    {
        $this->writeVeryVerbose("Save '$filename'");
        if (FileUtils::dumpFile($filename, $content)) {
            return true;
        }
        $this->writeError("Unable to write content to the file '$filename'.");

        return false;
    }
}
