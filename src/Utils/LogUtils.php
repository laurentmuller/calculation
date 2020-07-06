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
    public const KEY_CHANNELS = 'channels';
    public const KEY_FILE = 'file';
    public const KEY_LEVELS = 'levels';
    public const KEY_LOGS = 'logs';
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
     * Checks if the given file name exist and is not empty.
     *
     * @param string $filename the file to verify
     *
     * @return bool true if valid
     */
    public static function isFileValid(string $filename): bool
    {
        return \file_exists($filename) && 0 !== \filesize($filename);
    }

    /**
     * Gets all lines of the given log file.
     *
     * @param string $filename the file name to open
     *
     * @return Log[]|bool an array with the logs, the levels and the channels or <code>false</code> if an error occurs or if the file is empty
     */
    public static function readAll(string $filename)
    {
        // check file
        if (!self::isFileValid($filename)) {
            return false;
        }

        try {
            $id = 1;
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
                $log->setId($id++)
                    ->setChannel($channel)
                    ->setLevel($level)
                    ->setCreatedAt(self::parseDate($values[0]))
                    ->setMessage(self::getMessage($values[3]))
                    ->setContext(self::decodeJson($values[4]))
                    ->setExtra(self::decodeJson($values[5]));
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
            self::KEY_FILE => $filename,
            self::KEY_LEVELS => $levels,
            self::KEY_CHANNELS => $channels,
            self::KEY_LOGS => $logs,
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

            // skip if filtered
            if (\in_array($channel, $channelFilters, true) || \in_array($level, $levelFilters, true)) {
                continue;
            }

            // add
            $lines[] = [
                'level' => $level,
                'channel' => $channel,
                'date' => $values[0],
                'message' => self::getMessage($values[3]),
                'context' => self::decodeJson($values[4]),
                'extra' => self::decodeJson($values[5]),
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
                'limit' => $limit,
                'channels' => $channels,
                'levels' => $levels,
                'lines' => $lines,
            ];
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
    private static function decodeJson(string $value): array
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
