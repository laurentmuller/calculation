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

namespace App\Service;

use App\Entity\Log;
use App\Traits\CacheTrait;
use App\Util\FileUtils;
use App\Util\FormatUtils;
use App\Util\Utils;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Service to read and cache log file.
 *
 * @author Laurent Muller
 */
class LogService
{
    use CacheTrait;

    /**
     * The key for channels.
     */
    final public const KEY_CHANNELS = 'channels';

    /**
     * The key for file.
     */
    final public const KEY_FILE = 'file';

    /**
     * The key for levels.
     */
    final public const KEY_LEVELS = 'levels';

    /**
     * The key for logs.
     */
    final public const KEY_LOGS = 'logs';

    /**
     * The application channel.
     */
    private const APP_CHANNEL = 'app';

    /**
     * The date format.
     */
    private const DATE_FORMAT = 'd.m.Y H:i:s';

    /**
     * The values separator.
     */
    private const VALUES_SEP = '|';

    /**
     * The log file name.
     */
    private string $fileName;

    /**
     * Constructor.
     */
    public function __construct(KernelInterface $kernel, CacheItemPoolInterface $adapter)
    {
        $this->fileName = $this->buildLogFile($kernel);
        $this->setAdapter($adapter);
    }

    /**
     * Clear the cached values.
     */
    public function clearCache(): self
    {
        $this->deleteCacheItems([
            self::KEY_FILE,
            self::KEY_LOGS,
            self::KEY_CHANNELS,
            self::KEY_LEVELS,
        ]);

        return $this;
    }

    /**
     * Filters the given logs.
     *
     * @param Log[]       $logs        the logs to search in
     * @param string|null $value       the value to search for
     * @param bool        $skipChannel true to skip search in channel
     * @param bool        $skipLevel   true to skip search in level
     *
     * @return Log[] the filtered logs
     */
    public static function filter(array $logs, ?string $value, bool $skipChannel, bool $skipLevel): array
    {
        if (null !== $value && '' !== $value) {
            $filter = static function (Log $log) use ($value, $skipChannel, $skipLevel): bool {
                if (!$skipChannel && $log->getChannel()) {
                    $channel = self::getChannel((string) $log->getChannel());
                    if (Utils::contains($channel, $value, true)) {
                        return true;
                    }
                }

                if (!$skipLevel && $log->getLevel()) {
                    $level = self::getLevel((string) $log->getLevel());
                    if (Utils::contains($level, $value, true)) {
                        return true;
                    }
                }

                if (null !== $createdAt = $log->getCreatedAt()) {
                    $date = self::getCreatedAt($createdAt);
                    if (Utils::contains($date, $value, true)) {
                        return true;
                    }
                }

                if (null !== $log->getMessage()) {
                    return Utils::contains((string) $log->getMessage(), $value, true);
                }

                return false;
            };

            return \array_filter($logs, $filter);
        }

        return $logs;
    }

    /**
     * Filters the log for the given channel.
     *
     * @param Log[]       $logs  the logs to search in
     * @param string|null $value the channel value to search for
     *
     * @return Log[] the filtered logs
     */
    public static function filterChannel(array $logs, ?string $value): array
    {
        if (Utils::isString($value)) {
            return \array_filter($logs, fn (Log $log): bool => 0 === \strcasecmp((string) $value, (string) $log->getChannel()));
        }

        return $logs;
    }

    /**
     * Filters the log for the given level.
     *
     * @param Log[]       $logs  the logs to search in
     * @param string|null $value the level value to search for
     *
     * @return Log[] the filtered logs
     */
    public static function filterLevel(array $logs, ?string $value): array
    {
        if (Utils::isString($value)) {
            return \array_filter($logs, fn (Log $log): bool => 0 === \strcasecmp((string) $value, (string) $log->getLevel()));
        }

        return $logs;
    }

    /**
     * Gets the log channel.
     *
     * @param string $value      the source
     * @param bool   $capitalize true to capitlize the channel
     *
     * @return string the channel
     */
    public static function getChannel(string $value, bool $capitalize = false): string
    {
        $value = self::APP_CHANNEL === $value ? 'application' : \strtolower($value);
        if ($capitalize) {
            return Utils::capitalize($value);
        }

        return $value;
    }

    /**
     * Formats the log date.
     */
    public static function getCreatedAt(\DateTimeInterface $value): string
    {
        return (string) FormatUtils::formatDateTime($value, null, \IntlDateFormatter::MEDIUM);
    }

    /**
     * Gets the entries.
     *
     * @psalm-return array{
     *      file: string, logs:
     *      array<int, Log>,
     *      levels: array<string, int>,
     *      channels: array<string, int>}|false
     */
    public function getEntries(): array|false
    {
        if ($entries = $this->getCachedValues()) {
            return $entries;
        }

        $entries = $this->readFile();
        if (\is_array($entries)) {
            return $this->setCachedValues($entries);
        }

        return false;
    }

    /**
     * Gets the log file name.
     */
    public function getFileName(): string
    {
        return $this->fileName;
    }

    /**
     * Gets the log level.
     *
     * @param string $value      the source
     * @param bool   $capitalize true to capitlize the level
     *
     * @return string the level
     */
    public static function getLevel(string $value, bool $capitalize = false): string
    {
        $value = \strtolower($value);
        if ($capitalize) {
            return Utils::capitalize($value);
        }

        return $value;
    }

    /**
     * Gets the log for the given identifier.
     *
     * @param int $id the log identifier to find
     *
     * @return Log|null the log, if found; null otherwise
     */
    public function getLog(int $id): ?Log
    {
        $entries = $this->getEntries();
        if (\is_array($entries)) {
            return $entries[self::KEY_LOGS][$id] ?? null;
        }

        return null;
    }

    /**
     * Checks if this log file name exist and is not empty.
     *
     * @return bool true if valid
     */
    public function isFileValid(): bool
    {
        return FileUtils::exists($this->fileName) && 0 !== \filesize($this->fileName);
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
     * Gets the cached values.
     *
     * @return array{
     *      file: string,
     *      logs: array<int, Log>,
     *      levels: array<string, int>,
     *      channels: array<string, int>}|false
     */
    private function getCachedValues(): array|false
    {
        /** @psalm-var array{
         *      file: string,
         *      logs: array<int, Log>,
         *      levels: array<string, int>,
         *      channels: array<string, int>} $entries */
        $entries = [];

        // file
        $item = $this->getCacheItem(self::KEY_FILE);
        if (!$item instanceof CacheItemInterface || !$item->isHit()) {
            return false;
        }
        /** @psalm-var string $file */
        $file = $item->get();
        $entries[self::KEY_FILE] = $file;

        // logs
        $item = $this->getCacheItem(self::KEY_LOGS);
        if (!$item instanceof CacheItemInterface || !$item->isHit()) {
            return false;
        }
        /** @psalm-var array<int, Log> $logs */
        $logs = $item->get();
        $entries[self::KEY_LOGS] = $logs;

        // levels
        $item = $this->getCacheItem(self::KEY_LEVELS);
        if (!$item instanceof CacheItemInterface || !$item->isHit()) {
            return false;
        }
        /** @psalm-var array<string, int> $levels */
        $levels = $item->get();
        $entries[self::KEY_LEVELS] = $levels;

        // channels
        $item = $this->getCacheItem(self::KEY_CHANNELS);
        if (!$item instanceof CacheItemInterface || !$item->isHit()) {
            return false;
        }
        /** @psalm-var array<string, int> $channels */
        $channels = $item->get();
        $entries[self::KEY_CHANNELS] = $channels;

        return $entries;
    }

    /**
     * Increment by one the given array.
     *
     * @param array<string, int> $array the array to update
     * @param string             $key   the array's key to incremente
     */
    private function increment(array &$array, string $key): void
    {
        $value = $array[$key] ?? 0;
        $array[$key] = $value + 1;
    }

    /**
     * Gets the log date.
     *
     * @param string $value the source
     *
     * @return \DateTimeInterface|null a new DateTime instance or null on failure
     */
    private function parseDate(string $value): ?\DateTimeInterface
    {
        $date = \DateTime::createFromFormat(self::DATE_FORMAT, $value);

        return $date instanceof \DateTime ? $date : null;
    }

    /**
     * Decode the given JSON string.
     *
     * @param string $value the value to decode
     *
     * @return array the decoded value
     */
    private function parseJson(string $value): array
    {
        /** @psalm-var mixed $result */
        $result = \json_decode($value, true);
        if (\is_array($result) && \JSON_ERROR_NONE === \json_last_error()) {
            return $result;
        }

        return [];
    }

    /**
     * Gets the log message.
     *
     * @param string $value the source
     *
     * @return string the message
     */
    private function parseMessage(string $value): string
    {
        return \trim($value);
    }

    /**
     * Gets all lines of the log file.
     *
     * @return array|bool an array with the file, logs, the levels and the channels; <code>false</code> if an error occurs or if the file is empty
     *
     * @psalm-return array{
     *      file: string,
     *      logs: array<int, Log>,
     *      levels: array<string, int>,
     *      channels: array<string, int>}|false
     */
    private function readFile(): array|false
    {
        // check file
        if (!$this->isFileValid()) {
            return false;
        }

        $handle = false;

        try {
            // open
            if (false === $handle = \fopen($this->fileName, 'r')) {
                return false;
            }

            $id = 1;
            /** @psalm-var array<int, Log> $logs */
            $logs = [];
            /** @psalm-var array<string, int> $levels */
            $levels = [];
            /** @psalm-var array<string, int> $channels */
            $channels = [];

            // read line by line
            while (false !== ($line = \fgets($handle))) {
                $values = \explode(self::VALUES_SEP, $line);
                if (6 !== \count($values)) {
                    continue;
                }
                if (null === ($date = self::parseDate($values[0]))) {
                    continue;
                }

                $channel = self::getChannel($values[1]);
                $level = self::getLevel($values[2]);

                // add
                $log = new Log();
                $log->setId($id)
                    ->setCreatedAt($date)
                    ->setChannel($channel)
                    ->setLevel($level)
                    ->setMessage($this->parseMessage($values[3]))
                    ->setContext($this->parseJson($values[4]))
                    ->setExtra($this->parseJson($values[5]));
                $logs[$id++] = $log;

                // update
                $this->increment($levels, $level);
                $this->increment($channels, $channel);
            }
        } catch (\Exception) {
            return false;
        } finally {
            if (\is_resource($handle)) {
                \fclose($handle);
            }
        }

        // logs?
        if (!empty($logs)) {
            // sort
            \ksort($levels, \SORT_LOCALE_STRING);
            \ksort($channels, \SORT_LOCALE_STRING);

            // result
            return [
                self::KEY_FILE => $this->fileName,
                self::KEY_LOGS => $logs,
                self::KEY_LEVELS => $levels,
                self::KEY_CHANNELS => $channels,
            ];
        }

        return false;
    }

    /**
     * Save entries to cache.
     *
     * @psalm-param array{
     *      file: string,
     *      logs: array<int, Log>,
     *      levels: array<string, int>,
     *      channels: array<string, int>} $entries the entries to cache
     *
     * @psalm-return array{
     *      file: string,
     *      logs: array<int, Log>,
     *      levels: array<string, int>,
     *      channels: array<string, int>} the entries parameter
     */
    private function setCachedValues(array $entries): array
    {
        /** @psalm-var mixed $value */
        foreach ($entries as $key => $value) {
            $this->setCacheValue($key, $value);
        }

        return $entries;
    }
}
