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

namespace App\Traits;

use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Trait for files manipulation.
 */
trait FileTrait
{
    private ?Filesystem $fileSystem = null;

    /**
     * Decode the given file as JSON.
     *
     * @param string|\SplFileInfo $file  the path to the file
     * @param bool                $assoc when true, returned objects will be converted into associative arrays
     *
     * @return mixed the mixed the value encoded in json in appropriate PHP type
     *
     * @throws \InvalidArgumentException if the file can not be decoded
     */
    public function decodeJsonFile(string|\SplFileInfo $file, bool $assoc = true): mixed
    {
        if ($file instanceof \SplFileInfo) {
            $file = $file->getRealPath();
        }
        // file?
        if (!$this->isFile($file)) {
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
     * @param mixed               $content   the data to write into the file
     * @param bool                $useNative true to use the native <code>file_put_contents</code> function, false to use the file system
     *
     * @return bool true on success, false on failure
     */
    public function dumpFile(string|\SplFileInfo $file, mixed $content, bool $useNative = false): bool
    {
        if ($file instanceof \SplFileInfo) {
            $file = $file->getRealPath();
        }

        try {
            if ($useNative) {
                return false !== \file_put_contents($file, $content);
            }
            $this->getFilesystem()->dumpFile($file, $content);

            return true;
        } catch (IOException) {
        }

        return false;
    }

    /**
     * Checks the existence of the given file.
     */
    public function fileExists(string|\SplFileInfo $file): bool
    {
        if ($file instanceof \SplFileInfo) {
            $file = $file->getRealPath();
        }

        try {
            return $this->getFilesystem()->exists((string) $file);
        } catch (IOException) {
        }

        return false;
    }

    /**
     * Gets the shared file system instance.
     */
    public function getFilesystem(): Filesystem
    {
        if (null === $this->fileSystem) {
            $this->fileSystem = new Filesystem();
        }

        return $this->fileSystem;
    }

    /**
     * Tells whether the given file is a regular file.
     */
    public function isFile(string|\SplFileInfo $file): bool
    {
        if ($file instanceof \SplFileInfo) {
            $file = $file->getRealPath();
        }

        return \is_file((string) $file);
    }

    /**
     * Deletes a file or a directory.
     */
    public function removeFile(string|\SplFileInfo $file): bool
    {
        if ($file instanceof \SplFileInfo) {
            $file = $file->getRealPath();
        }

        try {
            if ($this->fileExists($file)) {
                $this->getFilesystem()->remove($file);

                return true;
            }
        } catch (IOException) {
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
    public function renameFile(string $origin, string $target, bool $overwrite = false): bool
    {
        try {
            $this->getFilesystem()->rename($origin, $target, $overwrite);

            return true;
        } catch (IOException) {
        }

        return false;
    }

    /**
     * Create temporary file with a unique name.
     *
     * @param string $prefix the prefix of the generated temporary file name
     *
     * @return string|null the new temporary file name (with path), or null on failure
     */
    public function tempfile(string $prefix = 'tmp'): ?string
    {
        try {
            return $this->getFilesystem()->tempnam(\sys_get_temp_dir(), $prefix);
        } catch (IOException) {
            return null;
        }
    }
}
