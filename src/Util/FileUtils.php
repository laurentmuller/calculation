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
class FileUtils
{
    /**
     * Decode the given file as JSON.
     *
     * @param string $file    the path to the file
     * @param bool   $assoc   when true, returned objects will be converted into associative arrays
     * @param int    $depth   user specified recursion depth
     * @param int    $options bitmask of JSON_BIGINT_AS_STRING (enabled by default)
     *
     * @return mixed the mixed the value encoded in json in appropriate PHP type
     *
     * @throws \InvalidArgumentException if the file can not be decoded
     */
    public static function decodeJson(string $file, bool $assoc = true, int $depth = null, int $options = null)
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
        if ($file instanceof \SplFileInfo) {
            $file = $file->getRealPath();
        }

        try {
            if ($useNative) {
                return false !== \file_put_contents($file, $content);
            }
            self::getFilesystem()->dumpFile($file, $content);

            return true;
        } catch (IOException $e) {
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
        if ($file instanceof \SplFileInfo) {
            $file = $file->getRealPath();
        }

        try {
            return self::getFilesystem()->exists((string) $file);
        } catch (IOException $e) {
        }

        return false;
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
     * Tells whether the given filen is a regular file.
     *
     * @param string|\SplFileInfo $file the path to the file
     *
     * @return bool true if the file exists and is a regular file, false otherwise
     */
    public static function isFile($file): bool
    {
        if ($file instanceof \SplFileInfo) {
            $file = $file->getRealPath();
        }

        return \is_file((string) $file);
    }

    /**
     * Deletes a file or a directory.
     *
     * @param string|\SplFileInfo|resource $file the file to delete
     *
     * @return bool true on success, false on failure
     */
    public static function remove($file): bool
    {
        if ($file instanceof \SplFileInfo) {
            $file = $file->getRealPath();
        }

        try {
            if (self::exists($file)) {
                self::getFilesystem()->remove($file);

                return true;
            }
        } catch (IOException $e) {
        }

        return false;
    }

    /**
     * Renames a file or a directory.
     *
     * @param string $origin    the source file
     * @param string $target    the target file
     * @param bool   $overwrite true to overwrite the target file
     *
     * @return bool true on success, false on failure
     */
    public static function rename(string $origin, string $target, bool $overwrite = false): bool
    {
        try {
            self::getFilesystem()->rename($origin, $target, $overwrite);

            return true;
        } catch (IOException $e) {
        }

        return false;
    }

    /**
     * Create temporary file with an unique name.
     *
     * @param string $prefix the prefix of the generated temporary file name
     *
     * @return string|null the new temporary file name (with path), or null on failure
     */
    public static function tempfile(string $prefix = 'tmp'): ?string
    {
        try {
            return self::getFilesystem()->tempnam(\sys_get_temp_dir(), $prefix);
        } catch (IOException $e) {
            return null;
        }
    }
}
