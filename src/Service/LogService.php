<?php
/*
 * This file is part of the Calculation package.
 *
 * Copyright (c) 2019 bibi.nu. All rights reserved.
 *
 * This computer code is protected by copyright law and international
 * treaties. Unauthorised reproduction or distribution of this code, or
 * any portion of it, may result in severe civil and criminal penalties,
 * and will be prosecuted to the maximum extent possible under the law.
 */

declare(strict_types=1);

namespace App\Service;

use App\Entity\Log;
use App\Utils\Utils;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Service to read and cache log file.
 *
 * @author Laurent Muller
 */
class LogService
{
    /**
     * The key for channels.
     */
    public const KEY_CHANNELS = 'channels';

    /**
     * The key for file.
     */
    public const KEY_FILE = 'file';

    /**
     * The key for levels.
     */
    public const KEY_LEVELS = 'levels';

    /**
     * The key for logs.
     */
    public const KEY_LOGS = 'logs';

    /**
     * The application channel.
     */
    private const APP_CHANNEL = 'app';

    /**
     * The cache validity in seconds (2 minutes).
     */
    private const CACHE_TIMEOUT = 60 * 2;

    /**
     * The date format.
     */
    private const DATE_FORMAT = 'd.m.Y H:i:s';

    /**
     * The values separator.
     */
    private const VALUES_SEP = '|';

    /**
     * The cache adapter.
     */
    private AdapterInterface $adapter;

    /**
     * The log file name.
     */
    private string $fileName;

    /**
     * Constructor.
     *
     * @param KernelInterface  $kernel  the kernel used to get the log file
     * @param AdapterInterface $adapter the adapter to cache logs
     */
    public function __construct(KernelInterface $kernel, AdapterInterface $adapter)
    {
        $this->adapter = $adapter;
        $this->fileName = $this->buildLogFile($kernel);
    }

    /**
     * Clear the cached values.
     */
    public function clearCache(): self
    {
        $this->adapter->deleteItems([
            self::KEY_LOGS,
            self::KEY_LEVELS,
            self::KEY_CHANNELS,
        ]);

        return $this;
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
     * Gets the entries.
     *
     * @return array|bool an array with the file name, the logs, the levels and the channels; <code>false</code> if an error occurs or if the file is empty
     */
    public function getEntries()
    {
        if ($entries = $this->getCachedValues()) {
            return $entries;
        }

        if ($entries = $this->readFile()) {
            $this->setCachedValues($entries);

            return $entries;
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
        if ($entries = $this->getEntries()) {
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
        return \file_exists($this->fileName) && 0 !== \filesize($this->fileName);
    }

    /**
     * Builds the log file name.
     */
    private function buildLogFile(KernelInterface $kernel): string
    {
        $dir = $kernel->getLogDir();
        $env = $kernel->getEnvironment();
        $sep = \DIRECTORY_SEPARATOR;
        $file = $dir . $sep . $env . '.log';
        $search = '/' === $sep ? '\\' : '/';

        return \str_replace($search, \DIRECTORY_SEPARATOR, $file);
    }

    /**
     * Decode the given JSON string.
     *
     * @param string $value the value to decode
     *
     * @return array the decoded value
     */
    private function decodeJson(string $value): array
    {
        try {
            $result = \json_decode($value, true);
            if ($result && JSON_ERROR_NONE === \json_last_error()) {
                return $result;
            }
        } catch (\Exception $e) {
        }

        return [];
    }

    /**
     * Gets the cached values.
     *
     * @return array|bool the values, if cached; false otherwise
     */
    private function getCachedValues()
    {
        $items = $this->adapter->getItems([
            self::KEY_LOGS,
            self::KEY_LEVELS,
            self::KEY_CHANNELS,
        ]);

        $entries = [self::KEY_FILE => $this->fileName];
        foreach ($items as $item) {
            if ($item->isHit()) {
                $entries[$item->getKey()] = $item->get();
            } else {
                return false;
            }
        }

        return $entries;
    }

    /**
     * Gets the log message.
     *
     * @param string $value the source
     *
     * @return string the message
     */
    private function getMessage(string $value): string
    {
        return \trim($value);
    }

    /**
     * Increment by one the given array.
     *
     * @param array  $array the array to update
     * @param string $key   the array's key to incremente
     */
    private function increment(array &$array, string $key): void
    {
        $array[$key] = ($array[$key] ?? 0) + 1;
    }

    /**
     * Gets the log date.
     *
     * @param string $value the source
     *
     * @return \DateTime a new DateTime instance or false on failure
     */
    private function parseDate(string $value): \DateTime
    {
        $date = \DateTime::createFromFormat(self::DATE_FORMAT, $value);

        return false === $date ? new \DateTime() : $date;
    }

    /**
     * Gets all lines of the log file.
     *
     * @return array|bool an array with the logs, the levels and the channels; <code>false</code> if an error occurs or if the file is empty
     */
    private function readFile()
    {
        // check file
        if (!$this->isFileValid()) {
            return false;
        }

        try {
            $id = 1;
            $logs = [];
            $levels = [];
            $channels = [];

            // read all
            $lines = \file($this->fileName, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

            // parse lines
            foreach ($lines as $line) {
                $values = \explode(self::VALUES_SEP, $line);
                if (6 !== \count($values)) {
                    continue;
                }
                $date = self::parseDate($values[0]);
                if (false === $date) {
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
                    ->setMessage($this->getMessage($values[3]))
                    ->setContext($this->decodeJson($values[4]))
                    ->setExtra($this->decodeJson($values[5]));
                $logs[$id++] = $log;

                // update
                $this->increment($levels, $level);
                $this->increment($channels, $channel);
            }
        } catch (\Exception $e) {
            return false;
        }

        // logs?
        if (!empty($logs)) {
            // sort
            \ksort($levels, SORT_LOCALE_STRING);
            \ksort($channels, SORT_LOCALE_STRING);

            // result
            return [
                self::KEY_LOGS => $logs,
                self::KEY_LEVELS => $levels,
                self::KEY_CHANNELS => $channels,
                self::KEY_FILE => $this->fileName,
            ];
        }

        return false;
    }

    /**
     * Save a value to cache.
     *
     * @param string $key     the cache key
     * @param array  $entries the entries
     */
    private function setCachedValue(string $key, array $entries): self
    {
        $value = $entries[$key];
        $item = $this->adapter->getItem($key)
            ->expiresAfter(self::CACHE_TIMEOUT)
            ->set($value);
        $this->adapter->save($item);

        return $this;
    }

    /**
     * Save entries to cache.
     *
     * @param array $entries the entries to save
     */
    private function setCachedValues(array $entries): void
    {
        $this->setCachedValue(self::KEY_LOGS, $entries)
            ->setCachedValue(self::KEY_LEVELS, $entries)
            ->setCachedValue(self::KEY_CHANNELS, $entries);
    }
}
