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
     * Atomically dumps content into a file.
     *
     * @param string|\SplFileInfo|resource $file    the file to write to
     * @param string                       $content the data to write into the file
     *
     * @return bool true on success, false on failure
     */
    public static function dumpFile($file, string $content): bool
    {
        if ($file instanceof \SplFileInfo) {
            $file = $file->getRealPath();
        }

        try {
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
