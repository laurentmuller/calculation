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
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Symfony\Contracts\Service\ServiceSubscriberTrait;

/**
 * Service to read and cache log file.
 */
class LogService implements ServiceSubscriberInterface
{
    use CacheAwareTrait;
    use ServiceSubscriberTrait;

    /**
     * The key to cache result.
     */
    private const KEY_CACHE = 'log_service_file';

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
    public function __construct(
        #[Autowire('%kernel.logs_dir%')]
        string $directory,
        #[Autowire('%kernel.environment%')]
        string $environment,
        #[Autowire('%log_date_format%')]
        private readonly string $dateFormat
    ) {
        $this->fileName = \sprintf('%s%s%s.log', \rtrim($directory, '\\/'), \DIRECTORY_SEPARATOR, $environment);
    }

    /**
     * Clear the cached log file.
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function clearCache(): self
    {
        if ($this->hasCacheItem(self::KEY_CACHE)) {
            $this->deleteCacheItem(self::KEY_CACHE);
        }

        return $this;
    }

    /**
     * Gets the given log.
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function getLog(int $id): ?Log
    {
        return $this->getLogFile()?->getLog($id);
    }

    /**
     * Gets the parsed log file.
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function getLogFile(): ?LogFile
    {
        /** @var ?LogFile $value */
        $value = $this->getCacheValue(self::KEY_CACHE);

        return $value instanceof LogFile ? $value : $this->parseFile();
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
     */
    private function parseDate(string $value): \DateTimeImmutable|false
    {
        return \DateTimeImmutable::createFromFormat($this->dateFormat, $value);
    }

    /**
     * Gets the log file.
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    private function parseFile(): ?LogFile
    {
        // check file
        if (!$this->isFileValid()) {
            return null;
        }

        try {
            // load content
            if (false === $lines = $this->loadFile()) {
                return null;
            }

            $id = 0;
            $file = new LogFile($this->fileName);

            // read line by line
            foreach ($lines as $line) {
                $values = \explode(self::VALUES_SEP, $line);
                if (6 !== \count($values)) {
                    continue;
                }
                if (false === ($date = $this->parseDate($values[0]))) {
                    continue;
                }

                // create and add
                $file->addLog(Log::instance()
                    ->setId($id++)
                    ->setCreatedAt($date)
                    ->setChannel($values[1])
                    ->setLevel($values[2])
                    ->setMessage($values[3])
                    ->setContext($this->parseJson($values[4]))
                    ->setExtra($this->parseJson($values[5])));
            }
        } catch (\Exception) {
            return null;
        }

        $file->sort();
        $this->setCacheValue(self::KEY_CACHE, $file);

        return $file;
    }

    /**
     * Decode the given JSON string.
     */
    private function parseJson(string $value): ?array
    {
        /** @psalm-var array|null $result */
        $result = \json_decode($value, true);

        return \is_array($result) ? $result : null;
    }
}
