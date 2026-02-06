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

use Symfony\Component\Console\Helper\Helper;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Finder\Finder;

/**
 * Utility class for files.
 */
final class FileUtils
{
    private static ?Filesystem $filesystem = null;

    // prevent instance creation
    private function __construct()
    {
        // no-op
    }

    /**
     * Copies a file.
     *
     * If the target file is older than the origin file, it is always overwritten.
     * If the target file is newer, it is overwritten only when the
     * $overwrite option is set to true.
     *
     * @return bool true on success, false on failure
     */
    public static function copy(string $originFile, string $targetFile, bool $overwrite = false): bool
    {
        try {
            self::getFilesystem()->copy($originFile, $targetFile, $overwrite);

            return true;
        } catch (IOException) {
            return false;
        }
    }

    /**
     * Decode the given file as JSON.
     *
     * @param string $file  the path or URL to the file
     * @param bool   $assoc when true, returned objects will be converted into associative arrays
     *
     * @return array|\stdClass the decoded file content in the appropriate PHP type
     *
     * @phpstan-return ($assoc is true ? array : \stdClass)
     *
     * @throws \InvalidArgumentException if the file cannot be decoded
     */
    public static function decodeJson(string $file, bool $assoc = true): array|\stdClass
    {
        // file or url?
        if (!\is_file($file) && !self::validateURL($file)) {
            throw new \InvalidArgumentException(\sprintf("The file '%s' cannot be found.", $file));
        }
        $content = self::readFile($file);
        if (null === $content) {
            throw new \InvalidArgumentException(\sprintf("Unable to get content of the file '%s'.", $file));
        }

        return StringUtils::decodeJson($content, $assoc);
    }

    /**
     * Atomically dumps content into a file.
     *
     * @param string          $file    the file to write to
     * @param resource|string $content the data to write into the file
     *
     * @return bool true on success, false on failure
     */
    public static function dumpFile(string $file, mixed $content): bool
    {
        try {
            self::getFilesystem()->dumpFile($file, $content);

            return true;
        } catch (IOException) {
            return false;
        }
    }

    /**
     * Returns if the given file is empty (size = 0).
     *
     * If the file does not exist, return 0.
     *
     * @param string $file the file or directory path
     */
    public static function empty(string $file): bool
    {
        return 0 === self::size($file);
    }

    /**
     * Formats the size of the given path.
     *
     * @phpstan-param string|int $path
     */
    public static function formatSize(string|int $path): string
    {
        $size = \is_int($path) ? $path : self::size($path);

        return Helper::formatMemory($size);
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
        if (!\is_file($filename) || self::empty($filename)) {
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
     * Given an existing end path, convert it to a path relative to a given starting path.
     *
     * @throws \InvalidArgumentException if the end path or the start path are not absolute
     */
    public static function makePathRelative(string $endPath, string $startPath): string
    {
        return \rtrim(self::getFilesystem()->makePathRelative($endPath, $startPath), '/');
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
     * Normalizes the given path.
     *
     * During normalization, all slashes are replaced by forward slashes ("/").
     * This method does not remove invalid or dot path segments. Consequently, it is much more efficient and should
     * be used whenever the given path is known to be a valid, absolute system
     * path.
     * This method is able to deal with both UNIX and Windows paths.
     */
    public static function normalize(string $path): string
    {
        $realPath = \realpath($path);

        return Path::normalize(\is_string($realPath) ? $realPath : $path);
    }

    /**
     * Returns the content of a file.
     *
     * @return ?string the content of the file; null on error or if empty
     */
    public static function readFile(string $file): ?string
    {
        if (!\is_file($file) && !self::validateURL($file)) {
            return null;
        }

        try {
            $content = self::getFilesystem()->readFile($file);

            return '' === $content ? null : $content;
        } catch (IOException) {
            return null;
        }
    }

    /**
     * Deletes a file or a directory.
     */
    public static function remove(string $file): bool
    {
        try {
            if (\file_exists($file)) {
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
     * Gets the size, in bytes, of the given file.
     */
    public static function size(string $file): int
    {
        if (!\file_exists($file)) {
            return 0;
        }

        if (\is_file($file)) {
            return (int) \filesize($file);
        }

        return self::sizeAndFiles($file)['size'];
    }

    /**
     * Gets the size, in bytes, and the number of files for the given directory.
     *
     * @return array{size: int, files: int}
     *
     * @throws \InvalidArgumentException if the path does not exist or is not a directory
     */
    public static function sizeAndFiles(string $path): array
    {
        if (!\file_exists($path)) {
            throw new \InvalidArgumentException(\sprintf('Path "%s" does not exist.', $path));
        }
        if (!\is_dir($path)) {
            throw new \InvalidArgumentException(\sprintf('Path "%s" is not a directory.', $path));
        }

        $size = 0;
        $files = 0;
        $finder = Finder::create()->in($path)->files();
        foreach ($finder as $file) {
            if ($file->isReadable()) {
                $size += $file->getSize();
                ++$files;
            }
        }

        return [
            'size' => $size,
            'files' => $files,
        ];
    }

    /**
     * Create the temporary directory in the given directory with a unique name.
     *
     * @param ?string $dir          the directory where the temporary directory will be created or null to use
     *                              the directory path used for temporary files
     * @param string  $prefix       the prefix of the generated temporary directory
     * @param bool    $deleteOnExit if true, the directory is deleted at the end of the script
     *
     * @return ?string the new temporary directory; null on failure
     */
    public static function tempDir(?string $dir = null, string $prefix = 'tmp', bool $deleteOnExit = true): ?string
    {
        $dir ??= \sys_get_temp_dir();
        for ($i = 0; $i < 10; ++$i) {
            $path = \sprintf('%s/%s_%d', $dir, $prefix, \mt_rand());
            if (\file_exists($path) || !\mkdir(directory: $path, recursive: true)) {
                continue;
            }
            if ($deleteOnExit) {
                \register_shutdown_function(static fn (): bool => self::remove($path));
            }

            return $path;
        }

        return null;
    }

    /**
     * Create the temporary file in the given directory with a unique name.
     *
     * @param ?string $dir          the directory where the temporary directory will be created or null to use
     *                              the directory path used for temporary files
     * @param string  $prefix       the prefix of the generated temporary filename.
     *                              Note: Windows uses only the first three characters of prefix
     * @param string  $suffix       the suffix of the generated temporary filename
     * @param bool    $deleteOnExit if true, the file is deleted at the end of the script
     *
     * @return ?string the new temporary file with the path; null on failure
     */
    public static function tempFile(
        ?string $dir = null,
        string $prefix = 'tmp',
        string $suffix = '',
        bool $deleteOnExit = true
    ): ?string {
        try {
            $dir ??= \sys_get_temp_dir();
            $file = self::getFilesystem()->tempnam($dir, $prefix, $suffix);
            if ($deleteOnExit) {
                \register_shutdown_function(static fn (): bool => self::remove($file));
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
