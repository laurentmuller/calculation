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
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Command to update Javascript and CSS dependencies.
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

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // to output messages
        $this->io = new SymfonyStyle($input, $output);

        // public dir
        if (!$publicDir = $this->getPublicDir()) {
            $this->writeNote('No public directory found.');

            return Command::INVALID;
        }

        // configuration
        if (null === ($configuration = $this->loadConfiguration($publicDir))) {
            $this->writeNote('No configuration found.');

            return Command::INVALID;
        }

        // check values
        if (!$this->propertyExists($configuration, ['source', 'target', 'format', 'plugins'], true)) {
            return Command::INVALID;
        }

        // get values
        /** @var string $source */
        $source = $configuration->source;
        $target = FileUtils::buildPath($publicDir, (string) $configuration->target);
        if (!$targetTemp = $this->getTargetTemp($publicDir)) {
            return Command::FAILURE;
        }

        /** @var string $format */
        $format = $configuration->format;
        /** @var \stdClass[] $plugins */
        $plugins = $configuration->plugins;
        /** @var array<string, string> $prefixes */
        $prefixes = $this->getConfigArray($configuration, 'prefixes');
        /** @var array<string, string> $suffixes */
        $suffixes = $this->getConfigArray($configuration, 'suffixes');
        /** @var array<string, string> $renames */
        $renames = $this->getConfigArray($configuration, 'renames');

        $countFiles = 0;
        $countPlugins = 0;

        try {
            // parse plugins
            foreach ($plugins as $plugin) {
                $name = (string) $plugin->name;
                $version = (string) $plugin->version;
                $display = (string) ($plugin->display ?? $plugin->name);
                if (\property_exists($plugin, 'disabled') && $plugin->disabled) {
                    $this->writeVerbose("Skipping   '$display v$version'.", 'fg=gray');
                    continue;
                }

                $this->writeVerbose("Installing '$display v$version'.");

                // copy files
                /** @var string[] $files */
                $files = $plugin->files;
                foreach ($files as $file) {
                    // get source
                    $sourceFile = $this->getSourceFile($source, $format, $plugin, $file);

                    // get target
                    $targetFile = $this->getTargetFile($targetTemp, $plugin, $file);

                    // copy
                    if ($this->copyFile($sourceFile, $targetFile, $prefixes, $suffixes, $renames)) {
                        ++$countFiles;
                    }
                }
                ++$countPlugins;

                // check version
                $versionSource = (string) ($plugin->source ?? $source);
                if (false !== \stripos($versionSource, 'cdnjs')) {
                    $this->checkVersionCdnjs($name, $version);
                } elseif (false !== \stripos($versionSource, 'jsdelivr')) {
                    $this->checkVersionJsDelivr($name, $version);
                }
            }

            // check loaded files
            $expected = \array_reduce($plugins, function (int $carry, \stdClass $plugin) {
                if (\property_exists($plugin, 'disabled') && $plugin->disabled) {
                    return $carry;
                }

                return $carry + \count((array) $plugin->files);
            }, 0);
            if ($expected !== $countFiles) {
                $this->writeError("Not all files has been loaded! Expected: $expected, Loaded: $countFiles.");
            }

            // rename directory
            $this->writeVerbose("Rename directory '$targetTemp' to '$target'.");
            if (!$this->rename($targetTemp, $target)) {
                $this->writeError("Unable rename directory '$targetTemp' to '$target'.");

                return Command::FAILURE;
            }

            // result
            $this->writeSuccess("Installed $countPlugins plugins and $countFiles files to the directory '$target'.");

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->writeError($e->getMessage());

            return Command::FAILURE;
        } finally {
            // remove temp directory
            $this->remove($targetTemp);
        }
    }

    private function checkVersion(string $url, string $name, string $version, string ...$paths): void
    {
        $content = $this->loadJson($url);
        if (false === $content) {
            $this->write("Unable to find the URL '$url' for the plugin '$name'.");

            return;
        }

        foreach ($paths as $path) {
            if (!isset($content->$path)) {
                $this->write("Unable to find the path '$path' for the plugin '$name'.");

                return;
            }
            /** @var \stdClass $content */
            $content = $content->$path;
        }

        /** @var string $newVersion */
        $newVersion = $content;
        if (\version_compare($version, $newVersion, '<')) {
            $this->write("The plugin '$name' version '$version' can be updated to the version '$newVersion'.");
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
     * @param array<string, string> $prefixes
     * @param array<string, string> $suffixes
     * @param array<string, string> $renames
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
     * @param string[] $entries the style entries to copy
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
     * @param array<string, string> $prefixes
     * @param array<string, string> $suffixes
     * @param array<string, string> $renames
     */
    private function dumpFile(string $content, string $targetFile, array $prefixes = [], array $suffixes = [], array $renames = []): bool
    {
        // get extension
        $ext = \pathinfo($targetFile, \PATHINFO_EXTENSION);

        // add prefix
        if (isset($prefixes[$ext])) {
            $content = $prefixes[$ext] . $content;
        }

        // add suffix
        if (isset($suffixes[$ext])) {
            $content .= $suffixes[$ext];
        }

        // rename
        foreach ($renames as $reg => $replace) {
            $pattern = "/$reg/";
            $targetFile = (string) \preg_replace($pattern, $replace, $targetFile);
        }

        // css?
        if ('css' === \pathinfo($targetFile, \PATHINFO_EXTENSION)) {
            $content = \str_replace('/*!', '/*', $content);
        }

        // bootstrap.css?
        $name = \pathinfo($targetFile, \PATHINFO_BASENAME);
        if (\in_array($name, self::BOOTSTRAP_FILES_STYLE, true)) {
            $content = $this->updateStyle($content);
        }

        // write target
        $this->writeFile($targetFile, $content);

        // set read-only
        FileUtils::chmod($targetFile, 0o644, false);

        return true;
    }

    /**
     * @param string[] $entries
     *
     * @return string[]|false
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

        return empty($result) ? false : $result;
    }

    /**
     * @return string[]|false
     */
    private function findStyles(string $content, string $style): array|false
    {
        $matches = [];
        $pattern = '/^\s{0,2}' . \preg_quote($style, '/') . '\s+\{([^}]+)\}/m';
        if (!empty(\preg_match_all($pattern, $content, $matches, \PREG_SET_ORDER))) {
            return \array_map(fn (array $value): string => \ltrim($value[0]), $matches);
        }

        return false;
    }

    private function getConfigArray(\stdClass $configuration, string $name): array
    {
        if ($this->propertyExists($configuration, $name)) {
            return (array) $configuration->{$name};
        }

        return [];
    }

    private function getProjectDir(): ?string
    {
        $application = $this->getApplication();
        if (!$application instanceof Application) {
            $this->writeError('The Application is not defined.');

            return null;
        }

        return $application->getKernel()->getProjectDir();
    }

    private function getPublicDir(): ?string
    {
        if ($projectDir = $this->getProjectDir()) {
            return FileUtils::buildPath($projectDir, 'public');
        }

        return null;
    }

    private function getSourceFile(string $source, string $format, \stdClass $plugin, string $file): string
    {
        $name = (string) $plugin->name;
        $version = (string) $plugin->version;

        // source
        if (\property_exists($plugin, 'source') && null !== $plugin->source) {
            $source = (string) $plugin->source;
        }

        // format
        if (\property_exists($plugin, 'format') && null !== $plugin->format) {
            $format = (string) $plugin->format;
        }

        // replace
        $search = ['{source}', '{name}', '{version}', '{file}'];
        $replace = [$source, $name, $version, $file];

        return \str_ireplace($search, $replace, $format);
    }

    private function getTargetFile(string $target, \stdClass $plugin, string $file): string
    {
        $name = (string) ($plugin->target ?? $plugin->name);

        return FileUtils::buildPath($target, $name, $file);
    }

    private function getTargetTemp(string $publicDir): string|false
    {
        if (!$dir = FileUtils::tempdir($publicDir)) {
            $this->writeError('Unable to create a temporary directory.');

            return false;
        }

        return $dir . \DIRECTORY_SEPARATOR;
    }

    private function loadConfiguration(string $publicDir): ?\stdClass
    {
        // check file
        $vendorFile = FileUtils::buildPath($publicDir, self::VENDOR_FILE_NAME);
        if (!FileUtils::exists($publicDir) || !FileUtils::exists($vendorFile)) {
            $this->writeVerbose("The file '$vendorFile' does not exist.");

            return null;
        }

        $configuration = $this->loadJson($vendorFile);
        if (!$configuration instanceof \stdClass) {
            return null;
        }

        return $configuration;
    }

    private function loadJson(string $filename): \stdClass|false
    {
        if (!$content = $this->readFile($filename)) {
            return false;
        }

        $data = \json_decode($content, false);
        if (\JSON_ERROR_NONE !== \json_last_error()) {
            $this->writeError(\json_last_error_msg());
            $this->writeError("Unable to decode file '$filename'.");

            return false;
        }
        if (!($data instanceof \stdClass)) {
            $this->writeError("Unable to decode file '$filename'.");

            return false;
        }

        return $data;
    }

    /**
     * @param string[]|string $properties
     */
    private function propertyExists(\stdClass $var, array|string $properties, bool $log = false): bool
    {
        $properties = (array) $properties;
        foreach ($properties as $property) {
            if (!\property_exists($var, $property) || empty($var->{$property})) {
                if ($log) {
                    $this->writeError("Unable to find the '$property' property.");
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
        if (empty($content)) {
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

        try {
            (new Filesystem())->rename($origin, $target, true);

            return true;
        } catch (IOException $e) {
            $this->writeError($e->getMessage());

            return false;
        }
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

        // copy styles
        $toAppend = '';
        foreach ($styles as $searchStyle => $newStyle) {
            $toAppend .= $this->copyStyle($content, $searchStyle, $newStyle);
        }

        // context menu
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

        // simple editor
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

    private function writeFile(string $filename, string $content): void
    {
        $this->writeVeryVerbose("Save '$filename'");
        FileUtils::dumpFile($filename, $content);
    }
}
