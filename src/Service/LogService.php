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
use App\Reader\CSVReader;
use App\Utils\FileUtils;
use App\Utils\StringUtils;
use Psr\Log\LogLevel;
use Symfony\Component\Clock\DatePoint;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * Service to parse and cache the log file.
 */
class LogService
{
    /**
     * The date format.
     */
    public const DATE_FORMAT = 'd.m.Y H:i:s.v';

    /**
     * The service formatter name.
     */
    public const FORMATTER_NAME = 'monolog.application.formatter';

    /**
     * The values separator.
     */
    public const SEPARATOR = '|';

    // The key to cache the log file.
    private const KEY_CACHE = 'log_file';

    // the file name
    private readonly string $fileName;
    // the file valid state
    private readonly bool $fileValid;

    public function __construct(
        #[Autowire('%kernel.logs_dir%/%kernel.environment%.log')]
        string $fileName,
        #[Target('calculation.log')]
        private readonly CacheInterface $cache
    ) {
        $this->fileName = FileUtils::normalize($fileName);
        $this->fileValid = !FileUtils::empty($fileName);
    }

    /**
     * Clear the cached log file.
     */
    public function clearCache(): self
    {
        $this->cache->delete(self::KEY_CACHE);

        return $this;
    }

    /**
     * Gets the log file name.
     */
    public function getFileName(): string
    {
        return $this->fileName;
    }

    /**
     * Gets the given log entry.
     */
    public function getLog(int $id): ?Log
    {
        return $this->getLogFile()?->getLog($id);
    }

    /**
     * Gets the parsed log file or null if the log file name is not valid.
     */
    public function getLogFile(): ?LogFile
    {
        if ($this->fileValid) {
            return $this->cache->get(self::KEY_CACHE, $this->parseFile(...));
        }

        return null;
    }

    /**
     * Checks if the log file name exists and is not empty.
     */
    public function isFileValid(): bool
    {
        return $this->fileValid;
    }

    /**
     * @phpstan-return non-empty-string
     */
    private function parseChannel(string $value): string
    {
        /** @phpstan-var non-empty-string */
        return $value;
    }

    private function parseDate(string $value): ?DatePoint
    {
        try {
            return DatePoint::createFromFormat(self::DATE_FORMAT, $value);
        } catch (\DateMalformedStringException) {
            return null;
        }
    }

    private function parseFile(): LogFile
    {
        $file = new LogFile($this->fileName);
        $reader = CSVReader::instance(file: $this->fileName, separator: self::SEPARATOR);

        foreach ($reader as $key => $values) {
            if (6 !== \count($values)) {
                continue;
            }
            $date = $this->parseDate($values[0]);
            if (!$date instanceof DatePoint) {
                continue;
            }
            $log = Log::instance($key)
                ->setCreatedAt($date)
                ->setChannel($this->parseChannel($values[1]))
                ->setLevel($this->parseLevel($values[2]))
                ->setMessage($this->parseMessage($values[3]))
                ->setContext($this->parseJson($values[4]))
                ->setExtra($this->parseJson($values[5]));
            $file->addLog($log);
        }

        return $file->sort();
    }

    /**
     * @phpstan-return array<string, string>|null
     */
    private function parseJson(string $value): ?array
    {
        try {
            /** @phpstan-var array<string, string> */
            return StringUtils::decodeJson($value);
        } catch (\InvalidArgumentException) {
            return null;
        }
    }

    /**
     * @phpstan-return LogLevel::*
     */
    private function parseLevel(string $value): string
    {
        /** @phpstan-var LogLevel::*  */
        return \strtolower($value);
    }

    private function parseMessage(string $value): string
    {
        return \trim($value);
    }
}
