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

namespace App\Utils;

use App\Enums\ImageExtension;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;

/**
 * Utility class for files.
 */
final class FileUtils
{
    private const SIZES = [
        '%.0f B',
        '%.0f KB',
        '%.1f MB',
        '%.1f GB',
        '%.1f TB',
        '%.1f PB',
    ];

    private static ?Filesystem $filesystem = null;

    // prevent instance creation
    private function __construct()
    {
        // no-op
    }

    /**
     *  Joins two or more path strings into a canonical path.
     */
    public static function buildPath(string ...$paths): string
    {
        return Path::join(...$paths);
    }

    /**
     * Changes the extension of the given file.
     */
    public static function changeExtension(string|\SplFileInfo $file, string|ImageExtension $extension): string
    {
        if ($extension instanceof ImageExtension) {
            $extension = $extension->value;
        }

        return Path::changeExtension(self::realPath($file), $extension);
    }

    /**
     * Change mode for a file or a directory.
     *
     * @param string $filename  A file name to change mode
     * @param int    $mode      The new mode (octal)
     * @param bool   $recursive whether change the mod recursively or not
     *
     * @return bool true on success, false on error
     */
    public static function chmod(string $filename, int $mode, bool $recursive = true): bool
    {
        try {
            self::getFilesystem()->chmod($filename, $mode, 0, $recursive);

            return true;
        } catch (IOException) {
            return false;
        }
    }

    /**
     * Copies a file.
     *
     * If the target file is older than the origin file, it is always overwritten.
     * If the target file is newer, it is overwritten only when the
     * $overwriteNewerFiles option is set to true.
     *
     * @return bool true on success, false on failure
     */
    public static function copy(string $originFile, string $targetFile, bool $overwriteNewerFiles = false): bool
    {
        try {
            self::getFilesystem()->copy($originFile, $targetFile, $overwriteNewerFiles);

            return true;
        } catch (IOException) {
            return false;
        }
    }

    /**
     * Decode the given file as JSON.
     *
     * @param string|\SplFileInfo $file  the path or URL to the file
     * @param bool                $assoc when true, returned objects will be converted into associative arrays
     *
     * @return array|\stdClass the decoded file content in the appropriate PHP type
     *
     * @phpstan-return ($assoc is true ? array : \stdClass)
     *
     * @throws \InvalidArgumentException if the file cannot be decoded
     */
    public static function decodeJson(string|\SplFileInfo $file, bool $assoc = true): array|\stdClass
    {
        $file = self::realPath($file);

        // file or url?
        if (!self::isFile($file) && !self::validateURL($file)) {
            throw new \InvalidArgumentException(\sprintf("The file '%s' cannot be found.", $file));
        }
        $content = self::readFile($file);
        if ('' === $content) {
            throw new \InvalidArgumentException(\sprintf("Unable to get content of the file '%s'.", $file));
        }

        return StringUtils::decodeJson($content, $assoc);
    }

    /**
     * Atomically dumps content into a file.
     *
     * @param \SplFileInfo|string $file    the file to write to
     * @param resource|string     $content the data to write into the file
     *
     * @return bool true on success, false on failure
     */
    public static function dumpFile(string|\SplFileInfo $file, mixed $content): bool
    {
        try {
            self::getFilesystem()->dumpFile(self::realPath($file), $content);

            return true;
        } catch (IOException) {
            return false;
        }
    }

    /**
     * Returns if the given file is empty (size = 0).
     *
     * @param string|\SplFileInfo $file the file or directory path
     */
    public static function empty(string|\SplFileInfo $file): bool
    {
        return 0 === self::size($file);
    }

    /**
     * Checks the existence of the given file.
     */
    public static function exists(string|\SplFileInfo $file): bool
    {
        return self::getFilesystem()->exists(self::realPath($file));
    }

    /**
     * Formats the size of the given path.
     */
    public static function formatSize(string|\SplFileInfo|int $path): string
    {
        $size = \is_int($path) ? $path : self::size($path);
        if (0 === $size) {
            return 'Empty';
        }

        $index = (int) \floor(\log($size) / \log(1024));

        return \sprintf(self::SIZES[$index], $size / 1024 ** $index);
    }

    /**
     * Returns the extension from a file path (without the leading dot).
     *
     * @param bool $forceLowerCase forces the extension to be lower-case
     */
    public static function getExtension(string|\SplFileInfo $file, bool $forceLowerCase = false): string
    {
        return Path::getExtension(self::realPath($file), $forceLowerCase);
    }

    /**
     * Gets the shared file system instance.
     */
    public static function getFilesystem(): Filesystem
    {
        return self::$filesystem ??= new Filesystem();
    }

    /**
     * Gets the number of lines for the given file name.
     *
     * @param string $filename  the file name to get count for
     * @param bool   $skipEmpty true to skip empty lines
     *
     * @return int the number of lines; 0 if an error occurs
     */
    public static function getLinesCount(string $filename, bool $skipEmpty = true): int
    {
        if (!self::isFile($filename) || self::empty($filename)) {
            return 0;
        }
        $flags = \SplFileObject::DROP_NEW_LINE;
        if ($skipEmpty) {
            $flags |= \SplFileObject::READ_AHEAD | \SplFileObject::SKIP_EMPTY;
        }
        $file = new \SplFileObject($filename, 'r');
        $file->setFlags($flags);
        $file->seek(\PHP_INT_MAX);

        return $file->key();
    }

    /**
     * Tells whether the given file is a directory.
     */
    public static function isDir(string|\SplFileInfo $file): bool
    {
        return \is_dir(self::realPath($file));
    }

    /**
     * Tells whether the given file is a regular file.
     */
    public static function isFile(string|\SplFileInfo $file): bool
    {
        return \is_file(self::realPath($file));
    }

    /**
     * Given an existing end path, convert it to a path relative to a given starting path.
     *
     * @throws \InvalidArgumentException if the end path or the start path is not absolute
     */
    public static function makePathRelative(string $endPath, string $startPath, bool $normalize = false): string
    {
        $result = self::getFilesystem()->makePathRelative($endPath, $startPath);
        if (\is_file($endPath)) {
            $result = \rtrim($result, '/');
        }

        return $normalize ? self::normalizeDirectory($result) : $result;
    }

    /**
     * Mirrors a directory to another.
     *
     * Copies files and directories from the origin directory into the target directory.
     *
     * @param string $origin   the origin directory to copy from
     * @param string $target   the target directory to copy to
     * @param bool   $override if true, target files newer than origin files are overwritten
     * @param bool   $delete   if true, delete files that are not in the source directory
     */
    public static function mirror(string $origin, string $target, bool $override = false, bool $delete = false): bool
    {
        try {
            $options = [
                'override' => $override,
                'delete' => $delete,
            ];
            self::getFilesystem()->mirror($origin, $target, options: $options);

            return true;
        } catch (IOException) {
            return false;
        }
    }

    /**
     * Creates a directory recursively.
     */
    public static function mkdir(string|iterable $dirs, int $mode = 0o777): bool
    {
        try {
            self::getFilesystem()->mkdir($dirs, $mode);

            return true;
        } catch (IOException) {
            return false;
        }
    }

    /**
     * Normalizes the given file.
     *
     * During normalization, all slashes are replaced by forward slashes ('/').
     * This method does not remove invalid or dot path segments. Consequently, it is much
     * more efficient and should be used whenever the given path is known to be a valid,
     * absolute system path.
     */
    public static function normalize(string|\SplFileInfo $file): string
    {
        return Path::normalize(self::realPath($file));
    }

    /**
     * Replace all slashes and backslashes by the directory separator.
     *
     * This method does not remove invalid or dot path segments. Consequently, it is much
     * more efficient and should be used whenever the given path is known to be a valid,
     * absolute system path.
     */
    public static function normalizeDirectory(string $path): string
    {
        return \str_replace(['\\', '/'], \DIRECTORY_SEPARATOR, $path);
    }

    /**
     * Returns the content of a file as a string.
     *
     * @return string the content of the file; an empty string ("") on error
     */
    public static function readFile(string|\SplFileInfo $file): string
    {
        $file = self::realPath($file);
        if (!self::isFile($file) && !self::validateURL($file)) {
            return '';
        }

        try {
            return self::getFilesystem()->readFile($file);
        } catch (IOException) {
            return '';
        }
    }

    /**
     * Gets the real path of the given file.
     */
    public static function realPath(string|\SplFileInfo $file): string
    {
        if ($file instanceof \SplFileInfo) {
            $file = $file->getPathname();
        }
        $path = \realpath($file);

        return \is_string($path) ? $path : $file;
    }

    /**
     * Deletes a file or a directory.
     */
    public static function remove(string|\SplFileInfo $file): bool
    {
        try {
            $file = self::realPath($file);
            if (self::exists($file)) {
                self::getFilesystem()->remove($file);

                return true;
            }
        } catch (IOException) {
        }

        return false;
    }

    /**
     * Renames a file or a directory.
     *
     * @param string $origin    the source file or directory
     * @param string $target    the target file or directory
     * @param bool   $overwrite true to overwrite the target
     *
     * @return bool true on success, false on failure
     */
    public static function rename(string $origin, string $target, bool $overwrite = false): bool
    {
        try {
            self::getFilesystem()->rename($origin, $target, $overwrite);

            return true;
        } catch (IOException) {
            return false;
        }
    }

    /**
     * Gets the size, in bytes, of the given path.
     *
     * @param string|\SplFileInfo $file the file or directory path
     */
    public static function size(string|\SplFileInfo $file): int
    {
        $file = self::realPath($file);
        if (!self::exists($file)) {
            return 0;
        }
        if (self::isFile($file)) {
            return (int) \filesize($file);
        }

        $size = 0;
        $flags = \FilesystemIterator::SKIP_DOTS;
        $innerIterator = new \RecursiveDirectoryIterator($file, $flags);
        $outerIterator = new \RecursiveIteratorIterator($innerIterator);
        /** @var \SplFileInfo $child */
        foreach ($outerIterator as $child) {
            if ($child->isReadable()) {
                $size += $child->getSize();
            }
        }

        return $size;
    }

    /**
     * Create the temporary directory in the given directory with a unique name.
     *
     * @param ?string $dir          the directory where the temporary directory will be created or null to use
     *                              the directory path used for temporary files
     * @param string  $prefix       The prefix of the generated temporary directory
     * @param bool    $deleteOnExit if true, the directory is deleted at the end of the script
     *
     * @return ?string the new temporary directory; null on failure
     */
    public static function tempDir(?string $dir = null, string $prefix = 'tmp', bool $deleteOnExit = true): ?string
    {
        $dir ??= \sys_get_temp_dir();
        $base = self::buildPath($dir, $prefix);
        for ($i = 0; $i < 10; ++$i) {
            $file = $base . \uniqid((string) \mt_rand(), true);
            if (!self::exists($file) && self::mkdir($file)) {
                if ($deleteOnExit) {
                    \register_shutdown_function(fn (): bool => self::remove($file));
                }

                return $file;
            }
        }

        return null;
    }

    /**
     * Create the temporary file in the given directory with a unique name.
     *
     * @param string  $prefix       the prefix of the generated temporary file name. Note: Windows uses only the first
     *                              three characters of prefix.
     * @param bool    $deleteOnExit if true, the file is deleted at the end of the script
     * @param string  $suffix       The suffix of the generated temporary filename
     * @param ?string $dir          the directory where the temporary file will be created or null to use
     *                              the directory path used for temporary files
     *
     * @return ?string the new temporary file name (with the path); null on failure
     */
    public static function tempFile(
        string $prefix = 'tmp',
        bool $deleteOnExit = true,
        string $suffix = '',
        ?string $dir = null
    ): ?string {
        try {
            $dir ??= \sys_get_temp_dir();
            $file = self::getFilesystem()->tempnam($dir, $prefix, $suffix);
            if ($deleteOnExit) {
                \register_shutdown_function(fn (): bool => self::remove($file));
            }

            return $file;
        } catch (IOException) {
            return null;
        }
    }

    private static function validateURL(string $file): bool
    {
        return false !== \filter_var($file, \FILTER_VALIDATE_URL);
    }
}
