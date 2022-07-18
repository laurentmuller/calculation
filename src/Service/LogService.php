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
use App\Util\FormatUtils;
use App\Util\Utils;
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
     * The application channel.
     */
    private const APP_CHANNEL = 'app';

    /**
     * The date format.
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
     * Clear the cached values.
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function clearCache(): self
    {
        $this->deleteCacheItem(self::KEY_CACHE);

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
     * Filters the log for the given channel.
     *
     * @param Log[]   $logs  the logs to search in
     * @param ?string $value the channel value to search for
     *
     * @return Log[] the filtered logs
     */
    public static function filterChannel(array $logs, ?string $value): array
    {
        if (null !== $value && '' !== $value) {
            return \array_filter($logs, static fn (Log $log): bool => 0 === \strcasecmp($value, (string) $log->getChannel()));
        }

        return $logs;
    }

    /**
     * Filters the log for the given level.
     *
     * @param Log[]   $logs  the logs to search in
     * @param ?string $value the level value to search for
     *
     * @return Log[] the filtered logs
     */
    public static function filterLevel(array $logs, ?string $value): array
    {
        if (null !== $value && '' !== $value) {
            return \array_filter($logs, static fn (Log $log): bool => 0 === \strcasecmp($value, (string) $log->getLevel()));
        }

        return $logs;
    }

    /**
     * Gets the log channel.
     *
     * @param string $value      the source channel
     * @param bool   $capitalize true to capitalize the channel
     *
     * @return string the channel
     */
    public static function getChannel(string $value, bool $capitalize = false): string
    {
        $value = self::APP_CHANNEL === $value ? 'application' : \strtolower($value);

        return $capitalize ? Utils::capitalize($value) : $value;
    }

    /**
     * Formats the log date.
     */
    public static function getCreatedAt(\DateTimeInterface $value): string
    {
        return (string) FormatUtils::formatDateTime($value, null, \IntlDateFormatter::MEDIUM);
    }

    /**
     * Gets the log level.
     *
     * @param string $value      the source level
     * @param bool   $capitalize true to capitalize the level
     *
     * @return string the level
     */
    public static function getLevel(string $value, bool $capitalize = false): string
    {
        $value = \strtolower($value);

        return $capitalize ? Utils::capitalize($value) : $value;
    }

    /**
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function getLog(int $id): ?Log
    {
        $logFile = $this->getLogFile();
        if (false !== $logFile) {
            return $logFile->getLog($id);
        }

        return null;
    }

    /**
     * Gets the parsed log file.
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function getLogFile(): LogFile|false
    {
        /** @psalm-var LogFile|false $value */
        $value = $this->getCacheValue(self::KEY_CACHE, false);
        if ($value instanceof LogFile) {
            return $value;
        }

        return $this->parseFile();
    }

    /**
     * Sort the given logs by the created date descending.
     *
     * @param Log[] $logs
     */
    public static function sortLogs(array &$logs): void
    {
        \usort($logs, static fn (Log $a, Log $b): int => $b->getCreatedAt() <=> $a->getCreatedAt());
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
    private function parseFile(): LogFile|false
    {
        // check file
        if (!$this->isFileValid()) {
            return false;
        }

        try {
            // load content
            if (false === $lines = $this->loadFile()) {
                return false;
            }

            $result = new LogFile();
            $result->setFile($this->fileName);

            // read line by line
            foreach ($lines as $line) {
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
                $log->setLevel($level)
                    ->setChannel($channel)
                    ->setCreatedAt($date)
                    ->setMessage($this->parseMessage($values[3]))
                    ->setContext($this->parseJson($values[4]))
                    ->setExtra($this->parseJson($values[5]));
                $result->addLog($log);
            }
        } catch (\Exception) {
            return false;
        }

        // logs?
        if (!$result->isEmpty()) {
            $result->sort();
            $this->setCacheValue(self::KEY_CACHE, $result);

            return $result;
        }

        return false;
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
}
