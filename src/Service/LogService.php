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
use App\Traits\LoggerAwareTrait;
use App\Traits\TranslatorAwareTrait;
use App\Utils\CSVReader;
use App\Utils\FileUtils;
use App\Utils\StringUtils;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\Service\ServiceMethodsSubscriberTrait;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

/**
 * Service to read and cache log file.
 */
class LogService implements ServiceSubscriberInterface
{
    use CacheAwareTrait;
    use LoggerAwareTrait;
    use ServiceMethodsSubscriberTrait;
    use TranslatorAwareTrait;

    /**
     * The date format.
     */
    public const DATE_FORMAT = 'd.m.Y H:i:s.v';

    /**
     * The cache timeout (15 minutes).
     */
    private const CACHE_TIMEOUT = 900;

    /**
     * The key to cache result.
     */
    private const KEY_CACHE = 'log_service_file';

    /**
     * The values separator.
     */
    private const VALUES_SEP = '|';

    public function __construct(
        #[Autowire('%kernel.logs_dir%/%kernel.environment%.log')]
        private readonly string $fileName,
    ) {
    }

    /**
     * Clear the cached log file.
     */
    public function clearCache(): self
    {
        if ($this->hasCacheItem(self::KEY_CACHE)) {
            $this->deleteCacheItem(self::KEY_CACHE);
        }

        return $this;
    }

    public function getCacheTimeout(): int
    {
        return self::CACHE_TIMEOUT;
    }

    /**
     * Gets the file name.
     */
    public function getFileName(): string
    {
        return FileUtils::normalizeDirectory($this->fileName);
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
        /** @psalm-var LogFile|null */
        return $this->getCacheValue(self::KEY_CACHE, fn (): ?LogFile => $this->parseFile());
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
     * Gets the log date.
     */
    private function parseDate(string $value): \DateTimeInterface|false
    {
        return \DateTimeImmutable::createFromFormat(self::DATE_FORMAT, $value);
    }

    /**
     * Gets the log file.
     */
    private function parseFile(): ?LogFile
    {
        if (!$this->isFileValid()) {
            return null;
        }

        try {
            $file = new LogFile($this->getFileName());
            $reader = new CSVReader(file: $this->fileName, separator: self::VALUES_SEP);
            /** @psalm-var string[]|null $values */
            foreach ($reader as $key => $values) {
                if (!\is_array($values) || 6 !== \count($values)) {
                    continue;
                }
                $date = $this->parseDate($values[0]);
                if (false === $date) {
                    continue;
                }
                $file->addLog(Log::instance($key)
                    ->setCreatedAt($date)
                    ->setChannel($values[1])
                    ->setLevel($values[2])
                    ->setMessage($values[3])
                    ->setContext($this->parseJson($values[4]))
                    ->setExtra($this->parseJson($values[5])));
            }
            $file->sort();

            return $file;
        } catch (\Exception $e) {
            $this->logException($e, $this->trans('log.download.error'));
        }

        return null;
    }

    /**
     * Decode the given JSON string.
     *
     * @psalm-return array<string, string>|null
     */
    private function parseJson(string $value): ?array
    {
        try {
            /** @psalm-var array<string, string> */
            return StringUtils::decodeJson($value);
        } catch (\InvalidArgumentException) {
            return null;
        }
    }
}
