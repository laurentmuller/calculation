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

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Trait to deals with files.
 *
 * @author Laurent Muller
 */
trait FileTrait
{
    use LoggerTrait;

    /**
     * The HTTP client.
     *
     * @var ?HttpClientInterface
     */
    private $client;

    /**
     * The file system instance.
     *
     * @var ?Filesystem
     */
    private $fs;

    /**
     * Change mode for a of file or a directory.
     *
     * @param string $filename  A file name to change mode
     * @param int    $mode      The new mode (octal)
     * @param bool   $recursive whether change the mod recursively or not
     */
    protected function chmod(string $filename, int $mode, bool $recursive = true): void
    {
        $this->getFilesystem()->chmod($filename, $mode, 0000, $recursive);
    }

    /**
     * Checks the existence of a file or directory.
     *
     * @param string $file a filename to check
     *
     * @return bool true if the file exists, false otherwise
     */
    protected function exists(string $file): bool
    {
        return $this->getFilesystem()->exists($file);
    }

    /**
     * Gets the shared file system instance.
     */
    protected function getFilesystem(): Filesystem
    {
        if (!$this->fs) {
            $this->fs = new Filesystem();
        }

        return $this->fs;
    }

    /**
     * Gets the shared HTTP client instance.
     */
    protected function getHttpClient(): HttpClientInterface
    {
        if (!$this->client) {
            $this->client = HttpClient::create();
        }

        return $this->client;
    }

    /**
     * Reads entire file into a string and decode as JSON object.
     *
     * @param string $filename the file to read
     *
     * @return \stdClass|bool the content of file, as JSON, if success; false otherwise
     */
    protected function loadJson(string $filename)
    {
        if (false === ($content = $this->readFile($filename))) {
            return false;
        }

        $data = \json_decode($content);
        if (JSON_ERROR_NONE !== \json_last_error()) {
            $this->writeError(\json_last_error_msg());
            $this->writeError("Unable to decode file '{$filename}'.");

            return false;
        }
        if (!($data instanceof \stdClass)) {
            $this->writeError("Unable to decode file '{$filename}'.");

            return false;
        }

        return $data;
    }

    /**
     * Given an existing path, convert it to a path relative to a given starting path.
     *
     * @param string $endPath   Absolute path of target
     * @param string $startPath Absolute path where traversal begins
     *
     * @return string Path of target relative to starting path
     */
    protected function makePathRelative(string $endPath, string $startPath): string
    {
        return $this->getFilesystem()->makePathRelative($endPath, $startPath);
    }

    /**
     * Checks if the object or class has the given property.
     *
     * @param \stdClass       $var        the object to test for
     * @param string[]|string $properties the properties names to check
     * @param bool            $log        true to output error
     *
     * @return bool true if the property exists, false if it doesn't exist
     */
    protected function propertyExists(\stdClass $var, $properties, bool $log = false): bool
    {
        if (!\is_array($properties)) {
            $properties = [$properties];
        }
        foreach ($properties as $property) {
            if (!\property_exists($var, $property) || empty($var->{$property})) {
                if ($log) {
                    $this->writeError("Unable to find the '{$property}' property.");
                }

                return false;
            }
        }

        return true;
    }

    /**
     * Reads entire file into a string.
     *
     * @param string $filename the file to read
     *
     * @return string|bool the content of file, if success; false otherwise
     */
    protected function readFile(string $filename)
    {
        $this->writeVeryVerbose("Load '{$filename}'");
        if (\is_file($filename)) {
            $content = \file_get_contents($filename);
        } else {
            $client = $this->getHttpClient();
            $response = $client->request('GET', $filename);
            $code = $response->getStatusCode();
            if (Response::HTTP_OK !== $code) {
                $this->writeError("Unable to get content of '{$filename}'.");

                return false;
            }
            $content = $response->getContent();
        }

        if (false === $content) {
            $this->writeError("Unable to get content of '{$filename}'.");

            return false;
        }
        if (empty($content)) {
            $this->writeError("The content of '{$filename}' is empty.");

            return false;
        }

        return $content;
    }

    /**
     * Removes the given directory.
     *
     * @param string $file A filename to remove
     */
    protected function remove($file): void
    {
        if ($this->exists($file)) {
            $this->writeVeryVerbose("Remove '{$file}'.");
            $this->getFilesystem()->remove($file);
        }
    }

    /**
     * Renames a file or a directory.
     *
     * @param string $origin    The origin filename or directory
     * @param string $target    The new filename or directory
     * @param bool   $overwrite Whether to overwrite the target if it already exists
     */
    protected function rename(string $origin, string $target, $overwrite = true): void
    {
        $this->writeVeryVerbose("Rename '{$origin}' to '{$target}'.");
        $this->getFilesystem()->rename($origin, $target, $overwrite);
    }

    /**
     * Creates a temporary directory.
     *
     * @param string $dir    The directory where the temporary filename will be created
     * @param string $prefix The prefix of the generated temporary filename
     *
     * @return string The new temporary directory, or throw an exception on failure
     */
    protected function tempDir(string $dir, string $prefix = 'tmp'): string
    {
        $dir = $this->getFilesystem()->tempnam($dir, $prefix);
        $this->remove($dir);

        return $dir;
    }

    /**
     * Write the given content into a file.
     *
     * @param string $filename the file to be written to
     * @param string $content  the data to write into the file
     */
    protected function writeFile(string $filename, string $content): void
    {
        $this->writeVeryVerbose("Save '{$filename}'");
        $this->getFilesystem()->dumpFile($filename, $content);
    }
}
