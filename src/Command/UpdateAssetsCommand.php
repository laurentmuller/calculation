<?php
/*
 * This file is part of the Calculation package.
 *
 * (c) bibi.nu. <bibi@bibi.nu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace App\Command;

use App\Model\Theme;
use App\Service\ThemeService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command to update Javascript and CSS dependencies.
 *
 * @author Laurent Muller
 */
class UpdateAssetsCommand extends AbstractAssetsCommand
{
    /**
     * The boostrap CSS file name.
     */
    private const BOOTSTRAP_FILE_NAME = 'bootstrap.css';

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
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct('app:update-assets');
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this->setDescription('Update Javascript and CSS dependencies.');
    }

    /**
     * {@inheritdoc}
     */
    protected function doExecute(InputInterface $input, OutputInterface $output): int
    {
        // get file
        if (!$publicDir = $this->getPublicDir()) {
            return Command::SUCCESS;
        }
        $vendorFile = $publicDir . '/' . self::VENDOR_FILE_NAME;

        // check file
        if (!$this->exists($publicDir) || !$this->exists($vendorFile)) {
            $this->writeVerbose("The file '{$vendorFile}' does not exist.");

            return Command::SUCCESS;
        }

        // decode
        $configuration = $this->loadJson($vendorFile);
        if (!$configuration instanceof \stdClass) {
            return Command::SUCCESS;
        }

        // check values
        if (!$this->propertyExists($configuration, ['source', 'target', 'format', 'plugins'], true)) {
            return Command::SUCCESS;
        }

        // get values
        $source = $configuration->source;
        $target = $publicDir . '/' . $configuration->target;
        $targetTemp = $this->tempDir($publicDir) . '/';
        $format = $configuration->format;
        $plugins = $configuration->plugins;
        $prefixes = $this->getConfigArray($configuration, 'prefixes');
        $suffixes = $this->getConfigArray($configuration, 'suffixes');
        $renames = $this->getConfigArray($configuration, 'renames');

        $countPlugins = 0;
        $countFiles = 0;

        try {
            // parse plugins
            foreach ($plugins as $plugin) {
                // disabled?
                if (\property_exists($plugin, 'disabled') && null !== $plugin->disabled && $plugin->disabled) {
                    continue;
                }

                $name = $plugin->name;
                $version = $plugin->version;
                $display = $plugin->display ?? $plugin->name;
                $this->writeVerbose("Installing '{$display} v{$version}'.");

                // copy files
                foreach ($plugin->files as $file) {
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
                $versionSource = $plugin->source ?? $source;
                if (false !== \stripos($versionSource, 'cdnjs')) {
                    $this->checkApiCdnjsLastVersion($name, $version);
                } elseif (false !== \stripos($versionSource, 'jsdelivr')) {
                    $this->checkJsDelivrLastVersion($name, $version);
                }
            }

            //check loaded files
            $expected = \array_reduce($plugins, function (int $carry, \stdClass $plugin) {
                if (\property_exists($plugin, 'disabled') && null !== $plugin->disabled && $plugin->disabled) {
                    return $carry;
                }

                return $carry + \count($plugin->files);
            }, 0);
            if ($expected !== $countFiles) {
                $this->writeError("Not all files has been loaded! Expected: {$expected}, Loaded: {$countFiles}.");
            }

            // bootswatch
            if (0 !== $bootswatchCount = $this->installBootswatch($configuration, $targetTemp, $prefixes, $suffixes)) {
                $countFiles += $bootswatchCount;
                ++$countPlugins;
            }

            // rename directory
            $this->rename($targetTemp, $target);

            // result
            $this->writeVerbose("Installed {$countPlugins} plugins and {$countFiles} files to the directory '{$target}'.");
        } finally {
            // remove temp directory
            $this->remove($targetTemp);
        }

        return Command::SUCCESS;
    }

    /**
     * Checks if the plugin installed is the last version.
     *
     * This works only for 'https://api.cdnjs.com' server.
     *
     * @param string $name    the plugin name
     * @param string $version the actual version
     */
    private function checkApiCdnjsLastVersion(string $name, string $version): void
    {
        $url = "https://api.cdnjs.com/libraries/{$name}?fields=version";
        $this->checkVersion($url, $name, $version, ['version']);
    }

    /**
     * Checks if the plugin installed is the last version.
     *
     * This works only for 'https://data.jsdelivr.com' server.
     *
     * @param string $name    the plugin name
     * @param string $version the actual version
     */
    private function checkJsDelivrLastVersion(string $name, string $version): void
    {
        $url = "https://data.jsdelivr.com/v1/package/npm/{$name}";
        $this->checkVersion($url, $name, $version, ['tags', 'latest']);
    }

    /**
     * Checks the plugin version.
     *
     * @param string   $url     the URL content to download
     * @param string   $name    the plugin name
     * @param string   $version the actual version
     * @param string[] $paths   the paths to the version
     */
    private function checkVersion(string $url, string $name, string $version, array $paths): void
    {
        if (false === $content = $this->loadJson($url)) {
            $this->write("Unable to find the URL '$url' for the plugin '$name'.");

            return;
        }

        foreach ($paths as $path) {
            if (!isset($content->$path)) {
                $this->write("Unable to find the path '$path' for the plugin '$name'.");

                return;
            }
            $content = $content->$path;
        }

        if (\version_compare($version, $content, '<')) {
            $this->write("The plugin '{$name}' version '{$version}' can be updated to the version '{$content}'.");
        }
    }

    /**
     * Copy a file.
     *
     * @param string $sourceFile the source file
     * @param string $targetFile the target file
     * @param array  $prefixes   the prefixes where each key is the file extension and the value is the text to preprend
     * @param array  $suffixes   the suffixes where each key is the file extension and the value is the text to append
     * @param array  $renames    the regular expression to renames the target file where each key is the pattern and the value is the text to replace with
     *
     * @return bool true if success
     */
    private function copyFile(string $sourceFile, string $targetFile, array $prefixes = [], array $suffixes = [], array $renames = []): bool
    {
        if (false !== ($content = $this->readFile($sourceFile))) {
            return $this->dumpFile((string) $content, $targetFile, $prefixes, $suffixes, $renames);
        }

        return false;
    }

    /**
     * Create a copy of a style.
     *
     * @param string $content     the style sheet content to search in
     * @param string $searchStyle the style name to copy
     * @param string $newStyle    the new style name
     * @param bool   $important   true to add <code>!important</code> to each style entries
     *
     * @return string the new style, if applicable; an empty string otherwise
     */
    private function copyStyle(string $content, string $searchStyle, string $newStyle, bool $important = true): string
    {
        $styles = $this->findStyles($content, $searchStyle);
        if (\is_array($styles)) {
            $result = "\n/*\n * Copied from '$searchStyle'  \n */";
            foreach ($styles as $style) {
                if ($important) {
                    $style = \str_replace(';', ' !important;', $style);
                }
                $result .= "\n" . \str_replace($searchStyle, $newStyle, $style) . "\n";
            }

            return $result;
        }

        return '';
    }

    /**
     * Copy entries of a style.
     *
     * @param string   $content     the style sheet content to search in
     * @param string   $searchStyle the style name to copy
     * @param string   $newStyle    the new style name
     * @param string[] $entries     the style entries to copy
     *
     * @return string the new style, if applicable; an empty string otherwise
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
     * Writes the given content to the target file.
     *
     * @param string $content    the content of the file
     * @param string $targetFile the file to write to
     * @param array  $prefixes   the prefixes where each key is the file extension and the value is the text to preprend
     * @param array  $suffixes   the suffixes where each key is the file extension and the value is the text to append
     * @param array  $renames    the regular expression to renames the target file where each key is the pattern and the value is the text to replace with
     *
     * @return bool true if success
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
            $pattern = "/{$reg}/";
            $targetFile = (string) \preg_replace($pattern, $replace, $targetFile);
        }

        // css?
        if ('css' === (string) \pathinfo($targetFile, \PATHINFO_EXTENSION)) {
            $content = \str_replace('/*!', '/*', $content);
        }

        // bootstrap.css?
        $name = (string) \pathinfo($targetFile, \PATHINFO_BASENAME);
        if (self::BOOTSTRAP_FILE_NAME === $name) {
            $content = $this->updateStyle($content);
        }

        // write target
        $this->writeFile($targetFile, $content);

        // set read-only
        $this->chmod($targetFile, 0644, false);

        return true;
    }

    /**
     * Find style entries.
     *
     * @param string   $style   the style to search in
     * @param string[] $entries the style entries to search for
     *
     * @return string[]|bool the style entries, if found; false otherwise
     */
    private function findStyleEntries(string $style, array $entries)
    {
        $result = [];
        $matches = [];
        foreach ($entries as $entry) {
            $pattern = '/^\s*' . \preg_quote($entry, '/') . '\s*:\s*.*;/m';
            if (!empty(\preg_match_all($pattern, $style, $matches, \PREG_SET_ORDER, 0))) {
                foreach ($matches as $matche) {
                    $result[] = (string) $matche[0];
                }
            }
        }

        return empty($result) ? false : $result;
    }

    /**
     * Find styles.
     *
     * @param string $content the style sheet content to search in
     * @param string $style   the style name to search for
     *
     * @return string[]|bool the styles, if found; false otherwise
     */
    private function findStyles(string $content, string $style)
    {
        $matches = [];
        $pattern = '/^' . \preg_quote($style, '/') . '\s+\{([^}]+)\}/m';
        if (!empty(\preg_match_all($pattern, $content, $matches, \PREG_SET_ORDER, 0))) {
            $result = [];
            foreach ($matches as $matche) {
                $result[] = $matche[0];
            }

            return $result;
        }

        return false;
    }

    /**
     * Gets an array from the configuration for the given name.
     *
     * @param \stdClass $configuration the configuration
     * @param string    $name          the entry name to search for
     *
     * @return array the array, maybe empty if not found
     */
    private function getConfigArray(\stdClass $configuration, string $name): array
    {
        if ($this->propertyExists($configuration, $name)) {
            return (array) $configuration->{$name};
        }

        return [];
    }

    /**
     * Gets the plugin source file to copy.
     *
     * @param string    $source the base URL
     * @param string    $format the URL format
     * @param \stdClass $plugin the plugin definition
     * @param string    $file   the file name
     *
     * @return string the source file
     */
    private function getSourceFile(string $source, string $format, \stdClass $plugin, string $file): string
    {
        $name = $plugin->name;
        $version = $plugin->version;

        // source
        if (\property_exists($plugin, 'source') && null !== $plugin->source) {
            $source = (string) $plugin->source;
        }

        // format
        if (\property_exists($plugin, 'format') && null !== $plugin->format) {
            $format = (string) $plugin->format;
        }

        // build
        $format = \str_ireplace('{source}', $source, $format);
        $format = \str_ireplace('{name}', $name, $format);
        $format = \str_ireplace('{version}', $version, $format);

        return \str_ireplace('{file}', $file, $format);
    }

    /**
     * Gets the plugin target file to write to.
     *
     * @param string    $target the target directory
     * @param \stdClass $plugin the plugin definition
     * @param string    $file   the file name
     *
     * @return string the target file
     */
    private function getTargetFile(string $target, \stdClass $plugin, string $file): string
    {
        $name = $plugin->target ?? $plugin->name;

        return $target . $name . '/' . $file;
    }

    /**
     * Install the Bootswatch themes.
     *
     * @param \stdClass $configuration the vendor configuration
     * @param string    $targetDir     the target directory
     * @param array     $prefixes      the prefixes where each key is the file extension and the value is the text to preprend
     * @param array     $suffixes      the suffixes where each key is the file extension and the value is the text to append
     * @param array     $renames       the regular expression to renames the target file where each key is the pattern and the value is the text to replace with
     *
     * @return int the number of downloaded themes
     */
    private function installBootswatch(\stdClass $configuration, string $targetDir, array $prefixes = [], array $suffixes = [], array $renames = []): int
    {
        // check if the default boostrap theme is present
        $target = $configuration->target;
        $relative = \rtrim($this->makePathRelative('/' . ThemeService::DEFAULT_CSS, '/' . $target), '/');
        $targetFile = $targetDir . $relative;
        if (!$this->exists($targetFile)) {
            $this->writeError("The file '{$targetFile}' for default theme does not exist.");

            return 0;
        }

        $count = 0;
        $result = [ThemeService::getDefaultTheme()];
        $themesDir = ThemeService::getThemesDirectory();

        // check bootswatch entry
        if ($this->propertyExists($configuration, 'bootswatch')) {
            // load file
            $source = $this->loadJson($configuration->bootswatch);
            if ($source instanceof \stdClass) {
                // message
                $version = $source->version;
                $this->writeVerbose("Installing 'bootswatch v{$version}'.");

                // themes
                foreach ($source->themes as $theme) {
                    $name = $theme->name;
                    $description = $theme->description . '.';
                    $relativePath = $themesDir . \strtolower($name) . '/' . self::BOOTSTRAP_FILE_NAME;

                    // copy file
                    $targetFile = $targetDir . $relativePath;
                    if ($this->copyFile($theme->css, $targetFile, $prefixes, $suffixes, $renames)) {
                        $result[] = new Theme([
                            'name' => $name,
                            'description' => $description,
                            'css' => $target . $relativePath,
                        ]);
                        ++$count;
                    }
                }
            }
        }

        // save
        $targetFile = $targetDir . $themesDir . ThemeService::getFileName();
        $content = \json_encode($result, \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES);
        if ($this->dumpFile((string) $content, $targetFile, $prefixes, $suffixes, $renames)) {
            ++$count;
        }

        return $count;
    }

    /**
     * Update style sheet.
     *
     * @param string $content the style sheet content to update
     *
     * @return string the updated style sheet content
     */
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
            ['background-color', 'border', 'border-radius', 'color', 'font-size']
        );

        $toAppend .= $this->copyStyleEntries(
            $content,
            '.dropdown-item',
            '.context-menu-item',
            ['background-color', 'color', 'font-size', 'font-weight', 'padding', 'padding-bottom', 'padding-left', 'padding-right', 'padding-top']
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
            ['border-top']
        );

        $toAppend .= $this->copyStyleEntries(
            $content,
            '.dropdown-header',
            '.context-menu-header',
            ['color', 'display', 'font-size', 'margin-bottom', 'white-space']
        );

        if (empty($toAppend)) {
            return $content;
        }

        return $content . self::CSS_COMMENTS . $toAppend;
    }
}
