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
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Symfony\Contracts\Service\ServiceSubscriberTrait;

/**
 * Service to read and cache log file.
 */
class LogService implements ServiceSubscriberInterface
{
    use CacheAwareTrait;
    use LoggerAwareTrait;
    use ServiceSubscriberTrait;
    use TranslatorAwareTrait;

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
        #[Autowire('%log_date_format%')]
        private readonly string $dateFormat,
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
        /** @psalm-var LogFile|null $file */
        $file = $this->getCacheValue(self::KEY_CACHE, fn (): ?LogFile => $this->parseFile());

        return $file;
    }

    /**
     * Checks if this log file name exist and is not empty.
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
    private function parseDate(string $value): \DateTimeImmutable|false
    {
        return \DateTimeImmutable::createFromFormat($this->dateFormat, $value);
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
     *
     * @psalm-suppress MixedReturnTypeCoercion
     */
    private function parseJson(string $value): ?array
    {
        try {
            return StringUtils::decodeJson($value);
        } catch (\InvalidArgumentException) {
            return null;
        }
    }
}
