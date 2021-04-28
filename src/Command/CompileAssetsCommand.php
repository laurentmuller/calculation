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

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

/**
 * Command to combine and minify CSS and JS files.
 *
 * @author Laurent Muller
 */
class CompileAssetsCommand extends AbstractAssetsCommand
{
    /**
     * The assets configuration file name.
     */
    private const ASSETS_FILE_NAME = 'assets.json';

    /**
     * The cleancss arguments.
     *
     * @var string
     */
    private static $cleanCssArgs = '--compatibility ie9 -O2';

    /**
     * Path to clean-css binary.
     *
     * @var string|null
     */
    private static $cleanCssBinary = 'cleancss';

    /**
     * The uglifyjs arguments.
     *
     * @var string
     */
    private static $uglifyJsArgs = '--compress --mangle';

    /**
     * Path to UglifyJS binary.
     *
     * @var string|null
     */
    private static $uglifyJsBinary = 'uglifyjs';

    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct('app:compile-assets');
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this->setDescription('Combine and minify CSS and JS files.');
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
        $assetFile = $publicDir . '/' . self::ASSETS_FILE_NAME;

        // check file
        if (!$this->exists($publicDir) || !$this->exists($assetFile)) {
            $this->writeVerbose("The file '{$assetFile}' does not exist.");

            return Command::SUCCESS;
        }

        // decode
        $configuration = $this->loadJson($assetFile);
        if (!$configuration instanceof \stdClass) {
            return Command::SUCCESS;
        }

        // source
        $source = $publicDir;
        if ($this->propertyExists($configuration, 'source')) {
            $source .= '/' . $configuration->source;
        }
        if (!$this->exists($source)) {
            $this->writeError("The source directory '{$source}' does not exist.");

            return Command::SUCCESS;
        }

        // target
        if (!$this->propertyExists($configuration, 'target')) {
            return Command::SUCCESS;
        }
        $target = $publicDir . '/' . $configuration->target;
        $targetTemp = $this->tempDir($publicDir) . '/';

        // uglifyjs arguments
        if ($this->propertyExists($configuration, 'js_args') && !empty($uglifyJsArgs = \trim($configuration->js_args))) {
            $this->uglifyJsArgs = $uglifyJsArgs;
            $this->writeVerbose("UglifyJs arguments: {$uglifyJsArgs}");
        }

        // cleancss arguments
        if ($this->propertyExists($configuration, 'css_args') && !empty($cleanCssArgs = \trim($configuration->css_args))) {
            $this->cleanCssArgs = $cleanCssArgs;
            $this->writeVerbose("CleanCss arguments: {$cleanCssArgs}");
        }

        // check uglifyjs
        if ($error = $this->checkUglifyJs()) {
            $this->writeError($error);
            $this->uglifyJsBinary = null;
        }

        // check cssclean
        if ($error = $this->checkCssClean()) {
            $this->writeError($error);
            $this->cleanCssBinary = null;
        }

        try {
            // create finder
            if (!$finder = $this->createFinder($source, $configuration)) {
                $this->writeVerbose("No file to process in '{$source}' directory.");

                return Command::SUCCESS;
            }

            $countJs = 0;
            $countCss = 0;

            // run over files
            foreach ($finder->getIterator() as $file) {
                $ext = $file->getExtension();
                switch ($ext) {
                    case 'js':
                        $this->processJs($file, $source, $targetTemp);
                        ++$countJs;
                        break;
                    case 'css':
                        $this->processCss($file, $source, $targetTemp);
                        ++$countCss;
                        break;
                }
            }

            // rename directory
            $this->rename($targetTemp, $target);

            // result
            $this->writeVerbose("Installed and compressed {$countJs} javascripts, {$countCss} style sheets to '{$target}' directory.");
        } finally {
            // remove temp directory
            $this->remove($targetTemp);
        }

        return Command::SUCCESS;
    }

    /**
     * Checks if the given content must be compressed.
     *
     * @param string $content the file content
     *
     * @return bool true to compress
     */
    private function checkCompressMark(string $content): bool
    {
        return 1 === \preg_match('#/\*+!#', $content); // must contain /**!
    }

    /**
     * Checks if the clean CSS binary exists.
     */
    private function checkCssClean(): ?string
    {
        [$ok, $output] = $this->executeCommand(\escapeshellarg($this->cleanCssBinary) . ' --version', '', false);
        if (!$ok) {
            return 'Error while executing ' . $this->cleanCssBinary . ', install Node.js and clean-css.';
        }
        if (\version_compare($output, '4.2') < 0) {
            return "Update to clean-css 4.2 or newer. Actual version: {$output}.";
        }

        return null;
    }

    /**
     * Checks if the uglify JS binary exists.
     */
    private function checkUglifyJs(): ?string
    {
        [$ok, $output] = $this->executeCommand(\escapeshellarg($this->uglifyJsBinary) . ' --version', '', false);
        if (!$ok) {
            return 'Error while executing ' . $this->uglifyJsBinary . ', install Node.js and uglify-es.';
        }

        // version is set as 'uglify-es 3.3.9'
        if ($pos = \strripos($output, ' ')) {
            $output = \substr($output, $pos + 1);
        }
        if (\version_compare($output, '3.3') < 0) {
            return "Update to uglify-es 3.3 or newer. Actual version: {$output}.";
        }

        return null;
    }

    /**
     * Compress CSS file.
     *
     * @param string $content  the file content
     * @param string $origFile the original file name
     *
     * @return string the compressed CSS file, if applicable; the content otherwise
     */
    private function compressCss(string $content, string $origFile): string
    {
        if (!$this->cleanCssBinary || !$this->checkCompressMark($content)) {
            return $content;
        }

        $this->writeVeryVerbose("Compressing {$origFile}");
        $cmd = \escapeshellarg($this->cleanCssBinary) . ' ' . $this->cleanCssArgs;
        [$ok, $output] = $this->executeCommand($cmd, $content, false);
        if (!$ok) {
            $this->writeError("Error while executing {$cmd}");
            $this->writeError($output);

            return $content;
        }

        return $output;
    }

    /**
     * Compress JS file.
     *
     * @param string $content  the file content
     * @param string $origFile the original file name
     *
     * @return string the compressed JS file, if applicable; the content otherwise
     */
    private function compressJs(string $content, string $origFile): string
    {
        if (!$this->uglifyJsBinary || !$this->checkCompressMark($content)) {
            return $content;
        }

        $this->writeVeryVerbose("Compressing {$origFile}");
        $cmd = \escapeshellarg($this->uglifyJsBinary) . ' ' . $this->uglifyJsArgs;
        [$ok, $output] = $this->executeCommand($cmd, $content, false);
        if (!$ok) {
            $this->writeError("Error while executing {$cmd}");
            $this->writeError($output);

            return $content;
        }

        return $output;
    }

    /**
     * Creates the finder.
     *
     * @param string    $source        the source directory
     * @param \stdClass $configuration the configuration
     *
     * @return Finder|null the finder, if any files to parse; null otherwise
     */
    private function createFinder(string $source, \stdClass $configuration): ?Finder
    {
        $finder = new Finder();
        $finder->in($source)
            ->exclude($configuration->target)
            ->name('*.css')
            ->name('*.js')
            ->files();

        // files?
        if (0 === $finder->count()) {
            return null;
        }

        return $finder;
    }

    /**
     * Executes a command.
     *
     * @return array [success, output]
     *
     * @throws \ErrorException
     */
    private function executeCommand(string $command, string $input, bool $bypassShell = true): array
    {
        $pipes = [];
        $process = \proc_open(
            $command,
            [['pipe', 'r'], ['pipe', 'w'], ['pipe', 'w']],
            $pipes,
            null, null, ['bypass_shell' => $bypassShell]
            );

        \fwrite($pipes[0], $input);
        \fclose($pipes[0]);

        $output = \stream_get_contents($pipes[1]);
        if (!$output) {
            $output = \stream_get_contents($pipes[2]);
        }

        $output = \str_replace("\r\n", "\n", (string) $output);
        $output = \trim($output);

        return [
            0 === \proc_close($process),
            $output,
        ];
    }

    /**
     * Expands @import(file) in CSS.
     *
     * @param string $content  the file content to parse
     * @param string $origFile the original file name
     *
     * @return string the expanded file content, if applicable; the content otherwise
     */
    private function expandCssImports(string $content, string $origFile): string
    {
        $dir = \dirname($origFile);

        return \preg_replace_callback('#@import\s+(?:url)?[(\'"]+(.+)[)\'"]+;#U', function (array $matches) use ($dir) {
            $file = $dir . '/' . $matches[1];
            if (!\is_file($file)) {
                $this->writeError("Expanding file {$file} not found!");

                return $matches[0];
            }

            $this->writeVeryVerbose("Including {$file}");
            $content = (string) \file_get_contents($file);
            $newDir = \dirname($file);
            $content = $this->expandCssImports($content, $file);
            if ($newDir !== $dir) {
                $tmp = $dir . '/';
                if (\substr($newDir, 0, \strlen($tmp)) === $tmp) {
                    $content = \preg_replace('#\burl\(["\']?(?=[.\w])(?!\w+:)#', '$0' . \substr($newDir, \strlen($tmp)) . '/', $content);
                } elseif (false !== \strpos($content, 'url(')) {
                    return $matches[0];
                }
            }

            return $content;
        }, $content);
    }

    /**
     * Expands Apache includes <!--#include file="..." -->.
     *
     * @param string $content  the file content to parse
     * @param string $origFile the original file name
     *
     * @return string the expanded file content, if applicable; the content otherwise
     */
    private function expandJsImports(string $content, string $origFile): string
    {
        $dir = \dirname($origFile);

        return \preg_replace_callback('~<!--#include\s+file="(.+)"\s+-->~U', function (array $matches) use ($dir) {
            $file = $dir . '/' . $matches[1];
            if (!\is_file($file)) {
                $this->writeError("Expanding file {$file} not found!");

                return $matches[0];
            }
            $this->writeVeryVerbose("Including {$file}");

            return $this->expandJsImports((string) \file_get_contents($file), $file);
        }, $content);
    }

    /**
     * Process a CSS file.
     *
     * @param SplFileInfo $file   the file to process
     * @param string      $source the source directory
     * @param string      $target the target directory
     */
    private function processCss(SplFileInfo $file, string $source, string $target): void
    {
        // load, expand and compress
        $path = (string) $file->getRealPath();
        $content = (string) $this->readFile($path);
        $content = $this->expandCssImports($content, $path);
        $content = $this->compressCss($content, $path);

        // save
        $targetFile = $target . $this->makePathRelative($file->getPathname(), $source);
        $this->writeFile($targetFile, $content);
    }

    /**
     * Process a JS file.
     *
     * @param SplFileInfo $file   the file to process
     * @param string      $source the source directory
     * @param string      $target the target directory
     */
    private function processJs(SplFileInfo $file, string $source, string $target): void
    {
        // load, expand and compress
        $path = (string) $file->getRealPath();
        $content = (string) $this->readFile($path);
        $content = $this->expandJsImports($content, $path);
        $content = $this->compressJs($content, $path);

        // save
        $targetFile = $target . $this->makePathRelative($file->getPathname(), $source);
        $this->writeFile($targetFile, $content);
    }
}
