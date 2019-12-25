<?php
/*
 * This file is part of the Calculation package.
 *
 * Copyright (c) 2019 bibi.nu. All rights reserved.
 *
 * This computer code is protected by copyright law and international
 * treaties. Unauthorised reproduction or distribution of this code, or
 * any portion of it, may result in severe civil and criminal penalties,
 * and will be prosecuted to the maximum extent possible under the law.
 */

declare(strict_types=1);

namespace App\Command;

use App\Entity\Theme;
use App\Service\ThemeService;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command to update Javascript and CSS dependencies.
 *
 * @author Laurent Muller
 */
class UpdateAssetsCommand extends AssetsCommand
{
    /**
     * The boostrap CSS file name.
     */
    private const BOOTSTRAP_FILE_NAME = 'bootstrap.css';

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
            return 0;
        }
        $vendorFile = $publicDir . '/' . self::VENDOR_FILE_NAME;

        // check file
        if (!$this->exists($publicDir) || !$this->exists($vendorFile)) {
            $this->writeVerbose("The file '{$vendorFile}' does not exist.");

            return 0;
        }

        // decode
        if (false === ($configuration = $this->loadJson($vendorFile))) {
            return 0;
        }

        // check values
        if (!$this->propertyExists($configuration, ['source', 'target', 'plugins'], true)) {
            return 0;
        }

        // get values
        $source = $configuration->source;
        $target = $publicDir . '/' . $configuration->target;
        $targetTemp = $this->tempDir($publicDir) . '/';

        $plugins = $configuration->plugins;
        $prefixes = $this->getConfigArray($configuration, 'prefixes');
        $suffixes = $this->getConfigArray($configuration, 'suffixes');
        $renames = $this->getConfigArray($configuration, 'renames');

        $countPlugins = 0;
        $countFiles = 0;

        try {
            // parse plugins
            foreach ($plugins as $plugin) {
                $name = $plugin->name;
                $version = $plugin->version;
                $this->writeVerbose("Installing '{$name} v{$version}'.");

                // copy files
                foreach ($plugin->files as $file) {
                    // get source
                    $src = $source;
                    if (isset($plugin->source)) {
                        $src = $plugin->source;
                    }

                    // source file
                    if (isset($plugin->passthrough) && (bool) ($plugin->passthrough)) {
                        $sourceFile = $src . '/' . $file;
                    } else {
                        $sourceFile = $src . $name . '/' . $version . '/' . $file;
                    }

                    // target file
                    $dst = $targetTemp;
                    if (isset($plugin->target)) {
                        $targetFile = $dst . $plugin->target . '/' . $file;
                    } else {
                        $targetFile = $dst . $name . '/' . $file;
                    }

                    // copy
                    if ($this->copyFile($sourceFile, $targetFile, $prefixes, $suffixes, $renames)) {
                        ++$countFiles;
                    }
                }
                ++$countPlugins;

                // check version
                if (!isset($plugin->passthrough)) {
                    $this->checkLastVersion($name, $version);
                }
            }

            //check loaded files
            $expected = \array_reduce($plugins, function (int $carry, $plugin) {
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
            $this->writeVerbose("Installed {$countPlugins} plugins, {$countFiles} files to '{$target}' directory.");
        } finally {
            // remove temp directory
            $this->remove($targetTemp);
        }

        return 0;
    }

    /**
     * Checks if the plugin installed is the last version.
     *
     * This works only for 'https://api.cdnjs.com' server.
     *
     * @param string $name    the plugin name
     * @param string $version the actual version
     */
    private function checkLastVersion(string $name, string $version): void
    {
        // get content
        $url = "https://api.cdnjs.com/libraries/{$name}?fields=version";
        if (false !== $content = $this->loadJson($url)) {
            // compare version
            if (isset($content->version)) {
                $lastVersion = $content->version;
                if (\version_compare($version, $lastVersion, '<')) {
                    $this->write("The plugin '{$name}' version '{$version}' can be replaced with the version '{$lastVersion}'.");
                }
            }
        }
    }

    /**
     * Copy a file,.
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
            return $this->dumpFile($content, $targetFile, $prefixes, $suffixes, $renames);
        }

        return false;
    }

    /**
     * Create a copy of the given style.
     *
     * @param string $content     the style sheet content
     * @param string $searchStyle the style name to copy
     * @param string $newStyle    the new style name
     *
     * @return string the new style
     */
    private function copyStyle(string $content, string $searchStyle, string $newStyle): string
    {
        $matches = [];
        $pattern = '/' . \preg_quote($searchStyle) . '\s+\{([^}]+)\}/m';
        $found = \preg_match_all($pattern, $content, $matches, PREG_SET_ORDER, 0);
        if (!empty($found)) {
            $result = '';
            foreach ($matches as $matche) {
                $result .= "\n" . \str_replace($searchStyle, $newStyle, $matche[0]) . "\n";
            }

            return $result;
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
        $ext = \pathinfo($targetFile, PATHINFO_EXTENSION);

        // add prefix
        if (isset($prefixes[$ext])) {
            $content = $prefixes[$ext] . $content;
        }

        // add suffix
        if (isset($suffixes[$ext])) {
            $content = $content . $suffixes[$ext];
        }

        // rename
        foreach ($renames as $reg => $replace) {
            $pattern = "/{$reg}/";
            $targetFile = \preg_replace($pattern, $replace, $targetFile);
        }

        // css?
        if ('css' === \pathinfo($targetFile, PATHINFO_EXTENSION)) {
            $content = \str_replace('/*!', '/*', $content);
        }

        // bootstrap.css?
        $name = \pathinfo($targetFile, PATHINFO_BASENAME);
        if (self::BOOTSTRAP_FILE_NAME === $name) {
            $content = $this->updateStyle($content);
        }

        // write target
        $this->writeFile($targetFile, $content);

        return true;
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
            if (false !== $source = $this->loadJson($configuration->bootswatch)) {
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
        $content = \json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if ($this->dumpFile($content, $targetFile, $prefixes, $suffixes, $renames)) {
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

            // toastDatabaseLogService
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
        if (empty($toAppend)) {
            return $content;
        }

        // add !important to border color style
        $pattern = '/border-color\s*\:\s*(.*);/m';
        $toAppend = \preg_replace_callback($pattern, function ($matches) {
            return \trim($matches[0], ';') . ' !important;';
        }, $toAppend);

        $comments = <<<'EOT'
            
/*
 * -----------------------------
 *         Custom styles
 * -----------------------------
 */
 
EOT;

        return $content . $comments . $toAppend;
    }
}
