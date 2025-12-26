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
use App\Traits\CacheKeyTrait;
use App\Utils\FileUtils;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * Service to parse and cache the log file.
 */
class LogService
{
    use CacheKeyTrait;

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
        $this->cache->delete($this->getCacheKey());

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
            return $this->cache->get($this->getCacheKey(), $this->parseFile(...));
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

    private function getCacheKey(): string
    {
        return $this->cleanKey('log_file_' . \basename($this->fileName));
    }

    private function parseFile(): LogFile
    {
        $service = new LogParserService();

        return $service->parseFile($this->fileName);
    }
}
