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

namespace App\Service;

use App\Entity\Log;
use App\Model\LogFile;
use App\Traits\CacheAwareTrait;
use App\Util\FileUtils;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Symfony\Contracts\Service\ServiceSubscriberTrait;

/**
 * Service to read and cache log file.
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class LogService implements ServiceSubscriberInterface
{
    use CacheAwareTrait;
    use ServiceSubscriberTrait;

    /**
     * The format to parse the date.
     */
    private const DATE_FORMAT = 'd.m.Y H:i:s';

    /**
     * The key for cache result.
     */
    private const KEY_CACHE = 'key.log';

    /**
     * The values separator.
     */
    private const VALUES_SEP = '|';

    /**
     * The log file name.
     */
    private readonly string $fileName;

    /**
     * Constructor.
     */
    public function __construct(KernelInterface $kernel)
    {
        $this->fileName = $this->buildLogFile($kernel);
    }

    /**
     * Clear the cached log file.
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function clearCache(): self
    {
        if ($this->hasCacheItem(self::KEY_CACHE)) {
            $this->deleteCacheItem(self::KEY_CACHE);
        }

        return $this;
    }

    /**
     * Filters the given logs.
     *
     * @param Log[]   $logs        the logs to search in
     * @param ?string $value       the value to search for
     * @param bool    $skipChannel true to skip search in channel
     * @param bool    $skipLevel   true to skip search in level
     *
     * @return Log[] the filtered logs
     */
    public static function filter(array $logs, ?string $value, bool $skipChannel, bool $skipLevel): array
    {
        if (null !== $value && '' !== $value) {
            return (new LogFilter($value, $skipChannel, $skipLevel))->filter($logs);
        }

        return $logs;
    }

    /**
     * Gets the given log.
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function getLog(int $id): ?Log
    {
        return $this->getLogFile()?->getLog($id);
    }

    /**
     * Gets the parsed log file.
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function getLogFile(): ?LogFile
    {
        /** @psalm-var LogFile|null $value */
        $value = $this->getCacheValue(self::KEY_CACHE);
        if ($value instanceof LogFile) {
            return $value;
        }

        return $this->parseFile();
    }

    /**
     * Builds the log file name.
     */
    private function buildLogFile(KernelInterface $kernel): string
    {
        $dir = $kernel->getLogDir();
        $env = $kernel->getEnvironment();
        $sep = \DIRECTORY_SEPARATOR;
        $file = "$dir$sep$env.log";

        return \str_replace(['\\', '/'], \DIRECTORY_SEPARATOR, $file);
    }

    /**
     * Checks if this log file name exist and is not empty.
     *
     * @return bool true if valid
     */
    private function isFileValid(): bool
    {
        return FileUtils::exists($this->fileName) && 0 !== \filesize($this->fileName);
    }

    /**
     * Load the content of the file as array.
     *
     * @psalm-return string[]|false
     */
    private function loadFile(): array|false
    {
        return \file($this->fileName, \FILE_IGNORE_NEW_LINES | \FILE_SKIP_EMPTY_LINES);
    }

    /**
     * Gets the log date.
     *
     * @param string $value the source
     *
     * @return \DateTimeImmutable|null a new DateTime instance or null on failure
     */
    private function parseDate(string $value): ?\DateTimeImmutable
    {
        $date = \DateTimeImmutable::createFromFormat(self::DATE_FORMAT, $value);

        return $date instanceof \DateTimeImmutable ? $date : null;
    }

    /**
     * Gets the log file.
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    private function parseFile(): ?LogFile
    {
        // check file
        if (!$this->isFileValid()) {
            return null;
        }

        try {
            // load content
            if (false === $lines = $this->loadFile()) {
                return null;
            }

            $result = new LogFile($this->fileName);

            // read line by line
            foreach ($lines as $line) {
                $values = \explode(self::VALUES_SEP, $line);
                if (6 !== \count($values)) {
                    continue;
                }
                if (null === ($date = self::parseDate($values[0]))) {
                    continue;
                }

                // add
                $log = new Log();
                $log->setCreatedAt($date)
                    ->setChannel($values[1])
                    ->setLevel($values[2])
                    ->setMessage($values[3])
                    ->setContext($this->parseJson($values[4]))
                    ->setExtra($this->parseJson($values[5]));
                $result->addLog($log);
            }
        } catch (\Exception) {
            return null;
        }

        $result->sort();
        $this->setCacheValue(self::KEY_CACHE, $result);

        return $result;
    }

    /**
     * Decode the given JSON string.
     */
    private function parseJson(string $value): ?array
    {
        /** @psalm-var mixed $result */
        $result = \json_decode($value, true);
        if (\is_array($result) && \JSON_ERROR_NONE === \json_last_error()) {
            return $result;
        }

        return null;
    }
}
