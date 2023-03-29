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

use App\Util\FileUtils;
use App\Util\StringUtils;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
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
 *     files: array<string>}
 * @psalm-type ConfigurationType = array{
 *     source: string,
 *     target: string,
 *     format: string,
 *     prefixes?: array<string, string>,
 *     suffixes?: array<string, string>,
 *     renames?: array<string, string>,
 *     plugins: array<PluginType>}
 */
#[AsCommand(name: 'app:update-assets', description: 'Update Javascript and CSS dependencies.')]
class UpdateAssetsCommand extends Command
{
    use LoggerTrait;

    /**
     * The boostrap CSS file name to update.
     */
    private const BOOTSTRAP_FILES_STYLE = [
        'bootstrap-dark.css',
        'bootstrap-light.css',
    ];

    /**
     * The CSS custom style comments.
     */
    private const CSS_COMMENTS = <<<'TEXT'
        /*
         * -----------------------------
         *         Custom styles
         * -----------------------------
         */
        TEXT;

    /**
     * The vendor configuration file name.
     */
    private const VENDOR_FILE_NAME = 'vendor.json';

    public function __construct(
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir
    ) {
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new SymfonyStyle($input, $output);
        if (!$publicDir = $this->getPublicDir()) {
            $this->writeNote('No public directory found.');

            return Command::INVALID;
        }

        $configuration = $this->loadConfiguration($publicDir);
        if (null === $configuration) {
            $this->writeNote('No configuration found.');

            return Command::INVALID;
        }
        if (!$this->propertyExists($configuration, ['source', 'target', 'format', 'plugins'], true)) {
            return Command::INVALID;
        }

        $source = $configuration['source'];
        $target = FileUtils::buildPath($publicDir, $configuration['target']);
        if (!$targetTemp = $this->getTargetTemp($publicDir)) {
            return Command::FAILURE;
        }

        $format = $configuration['format'];
        $plugins = $configuration['plugins'];
        $prefixes = $this->getConfigArray($configuration, 'prefixes');
        $suffixes = $this->getConfigArray($configuration, 'suffixes');
        $renames = $this->getConfigArray($configuration, 'renames');

        $countFiles = 0;
        $countPlugins = 0;

        try {
            foreach ($plugins as $plugin) {
                $name = $plugin['name'];
                $version = $plugin['version'];
                $display = $plugin['display'] ?? $name;
                if ($this->isPluginDisabled($plugin)) {
                    $this->writeVerbose("Skipping   '$display v$version'.", 'fg=gray');
                    continue;
                }
                $this->writeVerbose("Installing '$display v$version'.");
                $files = $plugin['files'];
                foreach ($files as $file) {
                    $sourceFile = $this->getSourceFile($source, $format, $plugin, $file);
                    $targetFile = $this->getTargetFile($targetTemp, $plugin, $file);
                    if ($this->copyFile($sourceFile, $targetFile, $prefixes, $suffixes, $renames)) {
                        ++$countFiles;
                    }
                }
                ++$countPlugins;
                $versionSource = $plugin['source'] ?? $source;
                if (StringUtils::contains($versionSource, 'cdnjs', true)) {
                    $this->checkVersionCdnjs($name, $version);
                } elseif (StringUtils::contains($versionSource, 'jsdelivr', true)) {
                    $this->checkVersionJsDelivr($name, $version);
                }
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

    private function checkVersion(string $url, string $name, string $version, string ...$paths): void
    {
        $content = $this->loadJson($url);
        if (null === $content) {
            $this->write("Unable to find the URL '$url' for the plugin '$name'.");

            return;
        }
        foreach ($paths as $path) {
            if (!isset($content[$path])) {
                $this->write("Unable to find the path '$path' for the plugin '$name'.");

                return;
            }
            /** @psalm-var array $content */
            $content = $content[$path];
        }
        /** @var string $newVersion */
        $newVersion = $content;
        if (\version_compare($version, $newVersion, '<')) {
            $this->writeWarning("The plugin '$name' version '$version' can be updated to the version '$newVersion'.");
        }
    }

    private function checkVersionCdnjs(string $name, string $version): void
    {
        $url = "https://api.cdnjs.com/libraries/$name?fields=version";
        $this->checkVersion($url, $name, $version, 'version');
    }

    private function checkVersionJsDelivr(string $name, string $version): void
    {
        $url = "https://data.jsdelivr.com/v1/package/npm/$name";
        $this->checkVersion($url, $name, $version, 'tags', 'latest');
    }

    /**
     * @psalm-param array<string, string> $prefixes
     * @psalm-param array<string, string> $suffixes
     * @psalm-param array<string, string> $renames
     */
    private function copyFile(string $sourceFile, string $targetFile, array $prefixes = [], array $suffixes = [], array $renames = []): bool
    {
        if (false !== ($content = $this->readFile($sourceFile))) {
            return $this->dumpFile($content, $targetFile, $prefixes, $suffixes, $renames);
        }

        return false;
    }

    private function copyStyle(string $content, string $searchStyle, string $newStyle): string
    {
        $styles = $this->findStyles($content, $searchStyle);
        if (\is_array($styles)) {
            $result = "\n/*\n * Copied from '$searchStyle'  \n */";
            foreach ($styles as $style) {
                $style = \str_replace(';', ' !important;', $style);
                $result .= "\n" . \str_replace($searchStyle, $newStyle, $style) . "\n";
            }

            return $result;
        }

        return '';
    }

    /**
     * @psalm-param string[] $entries the style entries to copy
     */
    private function copyStyleEntries(string $content, string $searchStyle, string $newStyle, array $entries): string
    {
        $styles = $this->findStyles($content, $searchStyle);
        if (\is_array($styles)) {
            $result = '';
            foreach ($styles as $style) {
                $styleEntries = $this->findStyleEntries($style, $entries);
                if (\is_array($styleEntries)) {
                    $result .= "$newStyle {\n";
                    foreach ($styleEntries as $styleEntry) {
                        $styleEntry = \str_replace(';', ' !important;', $styleEntry);
                        $result .= "  $styleEntry\n";
                    }
                    $result .= "}\n";
                }
            }
            if (!empty($result)) {
                return "\n/*\n * '$newStyle' (copied from '$searchStyle')  \n */\n" . $result;
            }
        }

        return '';
    }

    /**
     * @psalm-param PluginType[] $plugins
     */
    private function countFiles(array $plugins): int
    {
        return \array_reduce($plugins, function (int $carry, array $plugin) {
            /** @psalm-var PluginType $plugin */
            if (!$this->isPluginDisabled($plugin)) {
                return $carry + \count($plugin['files']);
            }

            return $carry;
        }, 0);
    }

    /**
     * @psalm-param array<string, string> $prefixes
     * @psalm-param array<string, string> $suffixes
     * @psalm-param array<string, string> $renames
     */
    private function dumpFile(string $content, string $targetFile, array $prefixes = [], array $suffixes = [], array $renames = []): bool
    {
        $ext = \pathinfo($targetFile, \PATHINFO_EXTENSION);
        if (isset($prefixes[$ext])) {
            $content = $prefixes[$ext] . $content;
        }
        if (isset($suffixes[$ext])) {
            $content .= $suffixes[$ext];
        }
        $targetFile = StringUtils::pregReplace($renames, $targetFile);
        if ('css' === \pathinfo($targetFile, \PATHINFO_EXTENSION)) {
            $content = \str_replace('/*!', '/*', $content);
        }
        $name = \pathinfo($targetFile, \PATHINFO_BASENAME);
        if (\in_array($name, self::BOOTSTRAP_FILES_STYLE, true)) {
            $content = $this->updateStyle($content);
        }
        if (!$this->writeFile($targetFile, $content)) {
            return false;
        }
        FileUtils::chmod($targetFile, 0o644, false);

        return true;
    }

    /**
     * @psalm-param string[] $entries
     *
     * @psalm-return string[]|false
     */
    private function findStyleEntries(string $style, array $entries): array|false
    {
        $result = [];
        $matches = [];
        foreach ($entries as $entry) {
            $pattern = '/^\s*' . \preg_quote($entry, '/') . '\s*:\s*.*;/m';
            if (!empty(\preg_match_all($pattern, $style, $matches, \PREG_SET_ORDER))) {
                /** @var string $match */
                foreach ($matches as $match) {
                    $result[] = $match[0];
                }
            }
        }

        return [] === $result ? false : $result;
    }

    /**
     * @psalm-return string[]|false
     */
    private function findStyles(string $content, string $style): array|false
    {
        $matches = [];
        $pattern = '/^\s{0,2}' . \preg_quote($style, '/') . '\s+\{([^}]+)}/m';
        if (!empty(\preg_match_all($pattern, $content, $matches, \PREG_SET_ORDER))) {
            return \array_map(fn (array $value): string => \ltrim($value[0]), $matches);
        }

        return false;
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

        return FileUtils::exists($publicDir) ? $publicDir : null;
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

        return FileUtils::buildPath($target, $name, $file);
    }

    private function getTargetTemp(string $publicDir): string|false
    {
        if (null === $dir = FileUtils::tempDir($publicDir)) {
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

        return \is_array($configuration) ? $configuration : null;
    }

    private function loadJson(string $filename): ?array
    {
        if (!$content = $this->readFile($filename)) {
            return null;
        }

        try {
            /** @psalm-var mixed $data */
            $data = StringUtils::decodeJson($content);

            return \is_array($data) ? $data : null;
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

    private function updateStyle(string $content): string
    {
        $styles = [
            // field
            '.form-control:focus' => '.field-valid',
            '.was-validated .form-control:invalid:focus, .form-control.is-invalid:focus' => '.field-invalid',

            // toast
            '.btn-success' => '.toast-header-success',
            '.btn-warning' => '.toast-header-warning',
            '.btn-danger' => '.toast-header-danger',
            '.btn-info' => '.toast-header-info',
            '.btn-primary' => '.toast-header-primary',
            '.btn-secondary' => '.toast-header-secondary',
            '.btn-dark' => '.toast-header-dark',
        ];
        $toAppend = '';
        foreach ($styles as $searchStyle => $newStyle) {
            $toAppend .= $this->copyStyle($content, $searchStyle, $newStyle);
        }
        $toAppend .= $this->copyStyleEntries(
            $content,
            '.dropdown-menu',
            '.context-menu-list',
            ['background', 'background-color', 'border-radius', 'color', 'font-size']
        );
        $toAppend .= $this->copyStyleEntries(
            $content,
            '.dropdown-item',
            '.context-menu-item',
            ['background-color', 'color', 'font-size', 'font-weight', 'padding', 'padding-bottom', 'padding-left', 'padding-right', 'padding-top', 'margin']
        );
        $toAppend .= $this->copyStyleEntries(
            $content,
            '.dropdown-item:hover, .dropdown-item:focus',
            '.context-menu-hover',
            ['background', 'background-color', 'color', 'text-decoration']
        );
        $toAppend .= $this->copyStyleEntries(
            $content,
            '.dropdown-divider',
            '.context-menu-separator',
            ['border-top', 'margin']
        );
        $toAppend .= $this->copyStyleEntries(
            $content,
            '.dropdown-header',
            '.context-menu-header',
            ['color', 'display', 'font-size', 'margin-bottom', 'white-space']
        );
        $toAppend .= $this->copyStyleEntries(
            $content,
            '.form-control',
            '.simple-editor',
            ['color', 'background-color']
        );
        if (empty($toAppend)) {
            return $content;
        }

        return $content . self::CSS_COMMENTS . $toAppend;
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
