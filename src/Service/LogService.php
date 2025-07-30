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
use App\Traits\LoggerTrait;
use App\Traits\TranslatorTrait;
use App\Utils\FileUtils;
use App\Utils\StringUtils;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel as PsrLevel;
use Symfony\Component\Clock\DatePoint;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Service to read and cache the log file.
 */
class LogService
{
    use LoggerTrait;
    use TranslatorTrait;

    /**
     * The date format.
     */
    public const DATE_FORMAT = 'd.m.Y H:i:s.v';

    /**
     * The service formatter name.
     */
    public const FORMATTER_NAME = 'monolog.application.formatter';

    /**
     * The key for the cache result.
     */
    private const KEY_CACHE = 'log_file';

    /**
     * The values separator.
     */
    private const VALUES_SEP = '|';

    public function __construct(
        #[Autowire('%kernel.logs_dir%/%kernel.environment%.log')]
        private readonly string $fileName,
        private readonly LoggerInterface $logger,
        private readonly TranslatorInterface $translator,
        #[Target('calculation.log')]
        private readonly CacheInterface $cache
    ) {
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
     * Gets the file name.
     */
    public function getFileName(): string
    {
        return FileUtils::normalize($this->fileName);
    }

    /**
     * Gets the given log.
     */
    public function getLog(int $id): ?Log
    {
        return $this->getLogFile()?->getLog($id);
    }

    /**
     * Gets the parsed log file.
     */
    public function getLogFile(): ?LogFile
    {
        return $this->cache->get(self::KEY_CACHE, fn (): ?LogFile => $this->parseFile());
    }

    #[\Override]
    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    #[\Override]
    public function getTranslator(): TranslatorInterface
    {
        return $this->translator;
    }

    /**
     * Checks if the log file name exists and is not empty.
     *
     * @return bool true if valid
     */
    public function isFileValid(): bool
    {
        return FileUtils::exists($this->fileName) && !FileUtils::empty($this->fileName);
    }

    /**
     * @phpstan-return non-empty-string
     */
    private function parseChannel(string $value): string
    {
        /** @phpstan-var non-empty-string */
        return $value;
    }

    /**
     * Gets the log date.
     */
    private function parseDate(string $value): ?DatePoint
    {
        try {
            return DatePoint::createFromFormat(self::DATE_FORMAT, $value);
        } catch (\DateMalformedStringException) {
            return null;
        }
    }

    /**
     * Gets the log file.
     */
    private function parseFile(): ?LogFile
    {
        if (!$this->isFileValid()) {
            return null;
        }

        $file = new LogFile($this->getFileName());
        $reader = new CSVReader(file: $this->fileName, separator: self::VALUES_SEP);

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
     * Decode the given JSON string.
     *
     * @return array<string, string>|null
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
     * @phpstan-return PsrLevel::*
     */
    private function parseLevel(string $value): string
    {
        /** @phpstan-var PsrLevel::*  */
        return \strtolower($value);
    }

    private function parseMessage(string $value): string
    {
        return \trim($value);
    }
}
