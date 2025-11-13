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

use App\Enums\Environment;
use App\Utils\FileUtils;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Service to get information about the kernel.
 *
 * @phpstan-type DirectoryType = array{
 *      name: string,
 *      path: string,
 *      relative: string,
 *      size: string}
 */
readonly class KernelInfoService
{
    private Environment $environment;
    private Environment $mode;
    private string $projectDir;

    public function __construct(
        private KernelInterface $kernel,
        #[Autowire('%kernel.project_dir%')]
        string $projectDir,
        #[Autowire('%app_mode%')]
        string $app_mode
    ) {
        $this->projectDir = FileUtils::normalize($projectDir);
        $this->environment = Environment::fromKernel($this->kernel);
        $this->mode = Environment::from($app_mode);
    }

    /**
     * Gets the build directory path and the formatted size.
     *
     * @phpstan-return DirectoryType
     */
    public function getBuildInfo(): array
    {
        return $this->getDirectoryInfo('Build', $this->kernel->getBuildDir());
    }

    /**
     * Gets the cache directory path and the formatted size.
     *
     * @phpstan-return DirectoryType
     */
    public function getCacheInfo(): array
    {
        return $this->getDirectoryInfo('Cache', $this->kernel->getCacheDir());
    }

    /**
     * Gets the charset of the application.
     */
    public function getCharset(): string
    {
        return $this->kernel->getCharset();
    }

    /**
     * Returns the 'debug' status.
     */
    public function getDebugStatus(): string
    {
        return $this->isDebug() ? SymfonyInfoService::LABEL_ENABLED : SymfonyInfoService::LABEL_DISABLED;
    }

    /**
     * Gets the kernel environment.
     */
    public function getEnvironment(): Environment
    {
        return $this->environment;
    }

    /**
     * Gets the log directory path and the formatted size.
     *
     * @phpstan-return DirectoryType
     */
    public function getLogInfo(): array
    {
        return $this->getDirectoryInfo('Logs', $this->kernel->getLogDir());
    }

    /**
     * Gets the application mode.
     */
    public function getMode(): Environment
    {
        return $this->mode;
    }

    /**
     * Gets the project directory path.
     */
    public function getProjectDir(): string
    {
        return $this->projectDir;
    }

    /**
     * Gets if debug mode is enabled.
     */
    public function isDebug(): bool
    {
        return $this->kernel->isDebug();
    }

    /**
     * @phpstan-return DirectoryType
     */
    private function getDirectoryInfo(string $name, string $path): array
    {
        $path = FileUtils::normalize($path);

        return [
            'name' => $name,
            'path' => $path,
            'relative' => $this->makePathRelative($path),
            'size' => FileUtils::formatSize($path),
        ];
    }

    private function makePathRelative(string $endPath): string
    {
        return \rtrim(FileUtils::makePathRelative($endPath, $this->projectDir), '/src');
    }
}
