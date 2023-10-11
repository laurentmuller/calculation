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

use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;

/**
 * Utility class for files.
 */
final class FileUtils
{
    private const SIZES = [
        1_073_741_824 => '%.1f GB',
        1_048_576 => '%.1f MB',
        1024 => '%.0f KB',
        0 => '%.0f B',
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
     * Decode the given file as JSON.
     *
     * @param string|\SplFileInfo $file  the path or URL to the file
     * @param bool                $assoc when true, returned objects will be converted into associative arrays
     *
     * @return array|\stdClass the decoded file content in appropriate PHP type
     *
     * @throws \InvalidArgumentException if the file can not be decoded
     *
     * @psalm-return ($assoc is true ? array : \stdClass)
     */
    public static function decodeJson(string|\SplFileInfo $file, bool $assoc = true): array|\stdClass
    {
        $file = self::realPath($file);

        // file or url?
        if (!self::isFile($file) && false === \filter_var($file, \FILTER_VALIDATE_URL)) {
            throw new \InvalidArgumentException(\sprintf("The file '%s' can not be found.", $file));
        }
        $content = \file_get_contents($file);
        if (false === $content) {
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
        foreach (self::SIZES as $minSize => $format) {
            if ($size >= $minSize) {
                $value = 0 !== $minSize ? $size / $minSize : $size;

                return \sprintf($format, $value);
            }
        }

        // must never reach
        return 'unknown';
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

        try {
            $file = new \SplFileObject($filename, 'r');
            $file->setFlags($flags);
            $file->seek(\PHP_INT_MAX);

            return $file->key();
        } catch (\Exception) {
            return 0;
        }
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

        return $normalize ? self::normalizeDirectory($result) : $result;
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
        $file = self::realPath($file);

        return Path::normalize($file);
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
            return \filesize($file) ?: 0;
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
     * Create temporary directory in the given directory with a unique name.
     *
     * @param string $dir          The directory where the temporary directory will be created
     * @param string $prefix       The prefix of the generated temporary directory
     * @param bool   $deleteOnExit if true, the directory is deleted at the end of the script
     *
     * @return string|null the new temporary directory; null on failure
     */
    public static function tempDir(string $dir, string $prefix = 'tmp', bool $deleteOnExit = true): ?string
    {
        try {
            $base = "$dir/$prefix";
            for ($i = 0; $i < 10; ++$i) {
                $result = $base . \uniqid((string) \mt_rand(), true);
                if (!self::exists($result) && self::mkdir($result)) {
                    if ($deleteOnExit) {
                        \register_shutdown_function(fn (): bool => self::remove($result));
                    }

                    return $result;
                }
            }
        } catch (IOException) {
        }

        return null;
    }

    /**
     * Create temporary file in the given directory with a unique name.
     *
     * @param string $prefix       the prefix of the generated temporary file name. Note: Windows uses only the first three characters of prefix.
     * @param bool   $deleteOnExit if true, the file is deleted at the end of the script
     *
     * @return string|null the new temporary file name (with path); null on failure
     */
    public static function tempFile(string $prefix = 'tmp', bool $deleteOnExit = true): ?string
    {
        try {
            $file = self::getFilesystem()->tempnam(\sys_get_temp_dir(), $prefix);
            if ($deleteOnExit) {
                \register_shutdown_function(fn (): bool => self::remove($file));
            }

            return $file;
        } catch (IOException) {
            return null;
        }
    }
}
