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

namespace App\Util;

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
            // ignore
        }

        return false;
    }

    /**
     * Decode the given file as JSON.
     *
     * @param string|\SplFileInfo $file  the path to the file
     * @param bool                $assoc when true, returned objects will be converted into associative arrays
     *
     * @return array|\stdClass the value encoded in json in appropriate PHP type
     *
     * @throws \InvalidArgumentException if the file can not be decoded
     */
    public static function decodeJson(string|\SplFileInfo $file, bool $assoc = true): array|\stdClass
    {
        // file?
        if (!self::isFile($file)) {
            throw new \InvalidArgumentException(\sprintf("The file '%s' can not be found.", self::getRealPath($file)));
        }

        // get content
        $file = self::getRealPath($file);
        if (false === $json = \file_get_contents($file)) {
            throw new \InvalidArgumentException(\sprintf("Unable to get content of the file '%s'.", self::getRealPath($file)));
        }

        // decode
        /** @var array|\stdClass $content */
        $content = \json_decode($json, $assoc);
        if (\JSON_ERROR_NONE !== \json_last_error()) {
            $message = \json_last_error_msg();
            throw new \InvalidArgumentException(\sprintf("Unable to decode the content of the file '%s' (%s).", self::getRealPath($file), $message));
        }

        return $content;
    }

    /**
     * Atomically dumps content into a file.
     *
     * @param \SplFileInfo|string $file    the file to write to
     * @param resource|string     $content the data to write into the file
     *
     * @return bool true on success, false on failure
     */
    public static function dumpFile(string|\SplFileInfo $file, $content): bool
    {
        $file = self::getRealPath($file);

        try {
            self::getFilesystem()->dumpFile($file, $content);

            return true;
        } catch (IOException) {
            // ignore
        }

        return false;
    }

    /**
     * Checks the existence of the given file.
     */
    public static function exists(string|\SplFileInfo $file): bool
    {
        $file = self::getRealPath($file);

        return self::getFilesystem()->exists($file);
    }

    /**
     * Formats the size of the given path.
     */
    public static function formatSize(string $path): string
    {
        $size = self::size($path);
        if (0 === $size) {
            return 'empty';
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
        /** @psalm-var Filesystem|null $fs */
        static $fs;
        if (null === $fs) {
            $fs = new Filesystem();
        }

        return $fs;
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
        if (!self::isFile($filename) || 0 === self::size($filename)) {
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
            // ignore
            return 0;
        }
    }

    /**
     * Tells whether the given file is a regular file.
     */
    public static function isFile(string|\SplFileInfo $file): bool
    {
        $file = self::getRealPath($file);

        return \is_file($file);
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
     * Deletes a file or a directory.
     */
    public static function remove(string|\SplFileInfo $file): bool
    {
        $file = self::getRealPath($file);

        try {
            if (self::exists($file)) {
                self::getFilesystem()->remove($file);

                return true;
            }
        } catch (IOException) {
            // ignore
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
            // ignore
        }

        return false;
    }

    /**
     * Gets the size, in bytes, of the given path.
     *
     * @param string $path the file or directory path
     */
    public static function size(string $path): int
    {
        if (self::isFile($path)) {
            return \filesize($path) ?: 0;
        }

        $size = 0;
        $flags = \FilesystemIterator::SKIP_DOTS;
        $innerIterator = new \RecursiveDirectoryIterator($path, $flags);
        $outerIterator = new \RecursiveIteratorIterator($innerIterator);

        /** @var \SplFileInfo $file */
        foreach ($outerIterator as $file) {
            if ($file->isReadable()) {
                $size += $file->getSize();
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
    public static function tempdir(string $dir, string $prefix = 'tmp', bool $deleteOnExit = true): ?string
    {
        try {
            $base = "$dir/$prefix";
            for ($i = 0; $i < 10; ++$i) {
                $result = $base . \uniqid((string) \mt_rand(), true);
                if (!self::exists($result) && self::mkdir($result)) {
                    if ($deleteOnExit) {
                        \register_shutdown_function(fn () => self::remove($result));
                    }

                    return $result;
                }
            }
        } catch (IOException) {
            // ignore
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
    public static function tempfile(string $prefix = 'tmp', bool $deleteOnExit = true): ?string
    {
        try {
            $file = self::getFilesystem()->tempnam(\sys_get_temp_dir(), $prefix);
            if ($deleteOnExit) {
                \register_shutdown_function(fn () => self::remove($file));
            }

            return $file;
        } catch (IOException) {
            // ignore
        }

        return null;
    }

    /**
     * Gets the real path of the given file.
     */
    private static function getRealPath(string|\SplFileInfo $file): string
    {
        if ($file instanceof \SplFileInfo) {
            return (string) $file->getRealPath();
        }

        return $file;
    }
}
