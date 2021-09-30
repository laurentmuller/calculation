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

namespace App\Util;

use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Utility class for files.
 *
 * @author Laurent Muller
 */
final class FileUtils
{
    private const SIZES = [
        1_073_741_824 => '%.1f GB',
        1_048_576 => '%.1f MB',
        1024 => '%.0f KB',
        0 => '%.0f B',
    ];

    /**
     * Decode the given file as JSON.
     *
     * @param string $file  the path to the file
     * @param bool   $assoc when true, returned objects will be converted into associative arrays
     *
     * @return mixed the mixed the value encoded in json in appropriate PHP type
     *
     * @throws \InvalidArgumentException if the file can not be decoded
     */
    public static function decodeJson(string $file, bool $assoc = true)
    {
        //file?
        if (!self::isFile($file)) {
            throw new \InvalidArgumentException("The file '$file' can not be found.");
        }

        // get content
        if (false === $json = \file_get_contents($file)) {
            throw new \InvalidArgumentException("Unable to get content of the file '$file'.");
        }

        // decode
        $content = \json_decode($json, $assoc);
        if (\JSON_ERROR_NONE !== \json_last_error()) {
            $message = \json_last_error_msg();
            throw new \InvalidArgumentException("Unable to decode the content of the file '$file' ($message).");
        }

        return $content;
    }

    /**
     * Atomically dumps content into a file.
     *
     * @param string|\SplFileInfo $file      the file to write to
     * @param string|resource     $content   the data to write into the file
     * @param bool                $useNative true to use the native <code>file_put_contents</code> function, false to use the file system
     *
     * @return bool true on success, false on failure
     */
    public static function dumpFile($file, $content, bool $useNative = false): bool
    {
        $file = self::getRealPath($file);

        try {
            if ($useNative) {
                // @phpstan-ignore-next-line
                return false !== \file_put_contents($file, $content);
            }
            self::getFilesystem()->dumpFile($file, $content);

            return true;
        } catch (IOException $e) {
            // ignore
        }

        return false;
    }

    /**
     * Checks the existence of the given file.
     *
     * @param string|\SplFileInfo $file the file to verfiy
     *
     * @return bool true if the file exists, false otherwise
     */
    public static function exists($file): bool
    {
        $file = self::getRealPath($file);

        return self::getFilesystem()->exists($file);
    }

    /**
     * Formats the size of the given path.
     *
     * @param string $path the file or directory path
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

        // must never reached
        return 'unknown';
    }

    /**
     * Gets the shared file system instance.
     */
    public static function getFilesystem(): Filesystem
    {
        static $fs;
        if (!$fs) {
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
    public static function getLines(string $filename, bool $skipEmpty = true): int
    {
        if (!self::isFile($filename)) {
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

            return $file->key() + 1;
        } catch (\Exception $e) {
            return 0;
        } finally {
            $file = null;
        }
    }

    /**
     * Tells whether the given filen is a regular file.
     *
     * @param string|\SplFileInfo $file the path to the file
     *
     * @return bool true if the file exists and is a regular file, false otherwise
     */
    public static function isFile($file): bool
    {
        $file = self::getRealPath($file);

        return \is_file($file);
    }

    /**
     * Deletes a file or a directory.
     *
     * @param string|\SplFileInfo $file the file to delete
     *
     * @return bool true on success, false on failure
     */
    public static function remove($file): bool
    {
        $file = self::getRealPath($file);

        try {
            if (self::exists($file)) {
                self::getFilesystem()->remove($file);

                return true;
            }
        } catch (IOException $e) {
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
        } catch (IOException $e) {
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
        $flags = \RecursiveDirectoryIterator::SKIP_DOTS;
        $innerIterator = new \RecursiveDirectoryIterator($path, $flags);
        $outerIterator = new \RecursiveIteratorIterator($innerIterator);

        /** @var \SplFileInfo $file */
        foreach ($outerIterator as $file) {
            if ($file->isReadable()) {
                $size += $file->getSize() ?: 0;
            }
        }

        return $size;
    }

    /**
     * Create temporary file with an unique name.
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
                \register_shutdown_function(function () use ($file): void {
                    self::remove($file);
                });
            }

            return $file;
        } catch (IOException $e) {
            // ignore
        }

        return null;
    }

    /**
     * Gets the real path of the given file.
     *
     * @param string|\SplFileInfo $file
     */
    private static function getRealPath($file): string
    {
        if ($file instanceof \SplFileInfo) {
            return (string) $file->getRealPath();
        }

        return (string) $file;
    }
}
