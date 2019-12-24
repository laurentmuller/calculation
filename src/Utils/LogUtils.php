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

namespace App\Utils;

use App\Entity\Log;

/**
 * Utility class for log files.
 *
 * @author Laurent Muller
 *
 * @internal
 */
final class LogUtils
{
    /**
     * The application channel.
     */
    private const APP_CHANNEL = 'app';

    /**
     * The MySql date format.
     */
    private const DATE_FORMAT = 'd.m.Y H:i:s';

    /**
     * The values separator.
     */
    private const VALUES_SEP = '|';

    // prevent instance creation
    private function __construct()
    {
        // no-op
    }

    /**
     * Gets the log channel.
     *
     * @param string $value the source
     *
     * @return string the channel
     */
    public static function getChannel(string $value): string
    {
        return self::APP_CHANNEL === $value ? 'application' : \strtolower($value);
    }

    /**
     * Gets the log level.
     *
     * @param string $value the source
     *
     * @return string the level
     */
    public static function getLevel(string $value): string
    {
        return \strtolower($value);
    }

    /**
     * Gets the log message.
     *
     * @param string $value the source
     *
     * @return string the message
     */
    public static function getMessage(string $value): string
    {
        return \trim($value);
    }

    /**
     * Gets all lines of the given log file.
     *
     * @param string $filename the file name to open
     *
     * @return array|bool an array with the log entries, the levels and the channels or <code>false</code> if an error occurs or if the file is empty
     */
    public static function readAll(string $filename)
    {
        // check file
        if (!self::isFileValid($filename)) {
            return false;
        }

        try {
            $logs = [];
            $levels = [];
            $channels = [];

            // read all
            $lines = \file($filename, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

            foreach ($lines as $line) {
                // parse
                $values = \explode(self::VALUES_SEP, $line);
                $channel = self::getChannel($values[1]);
                $level = self::getLevel($values[2]);

                // add
                $log = new Log();
                $log->setChannel($channel);
                $log->setLevel($level);
                $log->setCreatedAt(self::parseDate($values[0]));
                $log->setMessage(self::getMessage($values[3]));
                $log->setContext(self::getContext($values[4]));
                $log->setExtra(self::getExtra($values[5]));
                $logs[] = $log;

                // update
                self::increment($levels, $level);
                self::increment($channels, $channel);
            }
        } catch (\Exception $e) {
            return false;
        }

        // sort
        \ksort($levels, SORT_LOCALE_STRING);
        \ksort($channels, SORT_LOCALE_STRING);

        // result
        return [
            'file' => $filename,
            'levels' => $levels,
            'channels' => $channels,
            'logs' => $logs,
        ];
    }

    /**
     * Gets the last line of the given log file.
     *
     * @param string $filename       the file name to open
     * @param int    $maxLines       the number of lines to returns or -1 for all
     * @param array  $channelFilters the channels to skip
     * @param array  $levelFilters   the levels to skip
     *
     * @return array|bool the last lines or <code>false</code> if an error occurs or if the file is empty
     */
    public static function readLog(string $filename, $maxLines = 50, array $channelFilters = [], array $levelFilters = [])
    {
        // check file
        if (!self::isFileValid($filename)) {
            return false;
        }

        // open
        $reader = new ReverseReader($filename);
        if (!$reader->isOpen()) {
            return false;
        }

        // all?
        if (-1 === $maxLines) {
            $maxLines = PHP_INT_MAX;
        }

        $lines = [];
        $levels = [];
        $channels = [];
        $limit = $maxLines;

        while (($maxLines--) > 0 && $line = $reader->current()) {
            // parse
            $values = \explode(self::VALUES_SEP, $line);
            $channel = self::getChannel($values[1]);
            $level = self::getLevel($values[2]);

            // filter
            if (\in_array($channel, $channelFilters, true) || \in_array($level, $levelFilters, true)) {
                continue;
            }

            // add
            $lines[] = [
                'level' => $level,
                'channel' => $channel,
                'date' => $values[0],
                'message' => self::getMessage($values[3]),
                'context' => self::getContext($values[4]),
                'extra' => self::getExtra($values[5]),
            ];

            // update
            self::increment($levels, $level);
            self::increment($channels, $channel);
        }

        // lines?
        if (!empty($lines)) {
            // sort
            \ksort($levels, SORT_LOCALE_STRING);
            \ksort($channels, SORT_LOCALE_STRING);

            // result
            return [
                'file' => $filename,
                'limit' => $limit,
                'channels' => $channels,
                'levels' => $levels,
                'lines' => $lines,
            ];
        }

        return false;
    }

    /**
     * Gets the context informations.
     *
     * @param string $value the source
     *
     * @return array|null the context informations
     */
    private static function getContext(string $value): ?array
    {
        try {
            return \json_decode($value, true);
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Gets the extra informations.
     *
     * @param string $value the source
     *
     * @return array|null the extra informations
     */
    private static function getExtra(string $value): ?array
    {
        try {
            return \json_decode($value, true);
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Increment by one the given array.
     *
     * @param array  $array the array to update
     * @param string $key   the array's key to incremente
     */
    private static function increment(array &$array, string $key): void
    {
        $array[$key] = ($array[$key] ?? 0) + 1;
    }

    /**
     * Checks if the given file name exist and is not empty.
     *
     * @param string $filename the file to verify
     *
     * @return bool true if valid
     */
    private static function isFileValid(string $filename): bool
    {
        return \file_exists($filename) && 0 !== \filesize($filename);
    }

    /**
     * Gets the log date.
     *
     * @param string $value the source
     *
     * @return \DateTime the date
     */
    private static function parseDate(string $value): \DateTime
    {
        return \DateTime::createFromFormat(self::DATE_FORMAT, $value);
    }
}
