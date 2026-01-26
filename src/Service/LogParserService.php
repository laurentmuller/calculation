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
use App\Model\LogFileEntry;
use App\Reader\CsvReader;
use App\Utils\StringUtils;
use Psr\Log\LogLevel;
use Symfony\Component\Clock\DatePoint;

/**
 * Service to parse a log file.
 */
class LogParserService
{
    /**
     * The date format.
     */
    public const DATE_FORMAT = 'd.m.Y H:i:s.v';

    /**
     * The service formatter name.
     */
    public const FORMATTER_NAME = 'monolog.application.formatter';

    // the values separator.
    private const string SEPARATOR = '|';

    /**
     * Parse the given log file.
     */
    public function parseFile(LogFileEntry|string $fileName): LogFile
    {
        $path = $fileName instanceof LogFileEntry ? $fileName->path : $fileName;
        $file = new LogFile($path);
        $service = CsvService::instance(separator: self::SEPARATOR);
        $reader = CsvReader::instance($path, $service);
        foreach ($reader as $key => $values) {
            if (6 !== \count($values)) {
                continue;
            }
            /**
             * @phpstan-var array{
             *     0: string,
             *     1: string,
             *     2: LogLevel::*,
             *     3: string,
             *     4: string,
             *     5: string} $values
             */
            $date = $this->parseDate($values[0]);
            if (!$date instanceof DatePoint) {
                continue;
            }
            $log = Log::instance($key)
                ->setCreatedAt($date)
                ->setChannel($values[1])
                ->setLevel($values[2])
                ->setUser($values[3])
                ->setMessage(\trim($values[4]))
                ->setContext($this->parseContext($values[5]));
            $file->addLog($log);
        }

        return $file->sort();
    }

    /**
     * @phpstan-return array<string, string>|null
     */
    private function parseContext(string $value): ?array
    {
        try {
            /** @phpstan-var array<string, string> */
            return StringUtils::decodeJson($value);
        } catch (\InvalidArgumentException) {
            return null;
        }
    }

    private function parseDate(string $value): ?DatePoint
    {
        try {
            return DatePoint::createFromFormat(self::DATE_FORMAT, $value);
        } catch (\DateMalformedStringException) {
            return null;
        }
    }
}
