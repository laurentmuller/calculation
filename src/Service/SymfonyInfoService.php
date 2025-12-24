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

use App\Utils\DateUtils;
use App\Utils\FileUtils;
use Symfony\Component\Clock\DatePoint;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Intl\Locales;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * Service to get information about Symfony.
 *
 * @see https://github.com/symfony/symfony/blob/7.1/src/Symfony/Bundle/FrameworkBundle/Command/AboutCommand.php
 * @see https://github.com/EasyCorp/easy-doc-bundle/blob/master/src/Command/DocCommand.php
 */
readonly class SymfonyInfoService
{
    /**
     * The disabled label.
     */
    public const LABEL_DISABLED = 'Disabled';

    /**
     * The enabled label.
     */
    public const LABEL_ENABLED = 'Enabled';

    /**
     * The not installed label.
     */
    public const LABEL_NOT_INSTALLED = 'Not installed';

    // the release information URL
    private const RELEASE_URL = 'https://symfony.com/releases/%s.%s.json';
    // the unknown label
    private const UNKNOWN = 'Unknown';

    public function __construct(
        #[Target('calculation.symfony')]
        private CacheInterface $cache,
    ) {
    }

    /**
     * Returns the 'apcu' status.
     */
    public function getApcuStatus(): string
    {
        return $this->getExtensionStatus('apcu', 'apc.enabled');
    }

    /**
     * Get architecture.
     */
    public function getArchitecture(): string
    {
        return \sprintf('%d bits', \PHP_INT_SIZE * 8);
    }

    /**
     * Gets the end of life.
     */
    public function getEndOfLife(): string
    {
        $date = $this->formatMonthYear(Kernel::END_OF_LIFE);
        $days = $this->getDaysBeforeExpiration(Kernel::END_OF_LIFE);

        return \sprintf('%s (%s)', $date, $days);
    }

    /**
     * Gets the end of maintenance.
     */
    public function getEndOfMaintenance(): string
    {
        $date = $this->formatMonthYear(Kernel::END_OF_MAINTENANCE);
        $days = $this->getDaysBeforeExpiration(Kernel::END_OF_MAINTENANCE);

        return \sprintf('%s (%s)', $date, $days);
    }

    /**
     * Get the local name.
     */
    public function getLocaleName(): string
    {
        $locale = \Locale::getDefault();
        $name = Locales::getName($locale, 'en');

        return \sprintf('%s - %s', $name, $locale);
    }

    /**
     * Gets the maintenance status.
     */
    public function getMaintenanceStatus(): string
    {
        $now = DateUtils::createDatePoint();
        $endOfLife = $this->getEndOfMonth(Kernel::END_OF_LIFE);
        if ($now > $endOfLife) {
            return 'Unmaintained';
        }
        $endOfMaintenance = $this->getEndOfMonth(Kernel::END_OF_MAINTENANCE);
        if ($now > $endOfMaintenance) {
            return 'Security Fixes Only';
        }

        return 'Maintained';
    }

    /**
     * Returns the 'Zend OPcache' status.
     */
    public function getOpCacheStatus(): string
    {
        return $this->getExtensionStatus('Zend OPcache', 'opcache.enable');
    }

    /**
     * Get the release date.
     */
    public function getReleaseDate(): string
    {
        return $this->cache->get('release_date', $this->loadReleaseDate(...));
    }

    /**
     * Gets the time zone.
     */
    public function getTimeZone(): string
    {
        return \date_default_timezone_get();
    }

    /**
     * Gets the kernel version.
     */
    public function getVersion(): string
    {
        return Kernel::VERSION;
    }

    /**
     * Returns the 'xdebug' status.
     */
    public function getXdebugStatus(): string
    {
        if (!\extension_loaded('xdebug')) {
            return self::LABEL_NOT_INSTALLED;
        }
        $xdebugMode = \ini_get('xdebug.mode');
        $disabled = false === $xdebugMode || 'off' === $xdebugMode;

        /** @phpstan-var string $xdebugMode */
        return $disabled ? self::LABEL_DISABLED : \sprintf('%s (%s)', self::LABEL_ENABLED, $xdebugMode);
    }

    /**
     * Returns if the 'apcu' extension is loaded and enabled.
     */
    public function isApcuEnabled(): bool
    {
        return \str_starts_with($this->getApcuStatus(), self::LABEL_ENABLED);
    }

    /**
     * Returns if the long-term support is enabled.
     */
    public function isLongTermSupport(): bool
    {
        return (4 <=> Kernel::MINOR_VERSION) === 0; // @phpstan-ignore identical.alwaysTrue
    }

    /**
     * Returns if the 'Zend OP cache' extension is loaded and enabled.
     */
    public function isOpCacheEnabled(): bool
    {
        return \str_starts_with($this->getOpCacheStatus(), self::LABEL_ENABLED);
    }

    /**
     * Returns if the 'xdebug' extension is loaded and enabled.
     */
    public function isXdebugEnabled(): bool
    {
        return \str_starts_with($this->getXdebugStatus(), self::LABEL_ENABLED);
    }

    private function createDate(string $date): DatePoint
    {
        return DatePoint::createFromFormat('m/Y', $date);
    }

    private function formatMonthYear(string $date): string
    {
        return $this->createDate($date)->format('F Y');
    }

    private function getDaysBeforeExpiration(string $date): string
    {
        $today = DateUtils::createDatePoint();
        $endOfMonth = $this->getEndOfMonth($date);
        if ($endOfMonth < $today) {
            return 'Expired';
        }

        return $today->diff($endOfMonth)->format('%R%a days');
    }

    private function getEndOfMonth(string $date): DatePoint
    {
        return DateUtils::modify($this->createDate($date), 'last day of this month 23:59:59');
    }

    private function getExtensionStatus(string $extension, string $enabled): string
    {
        if (!\extension_loaded($extension)) {
            return self::LABEL_NOT_INSTALLED;
        }

        return \filter_var(\ini_get($enabled), \FILTER_VALIDATE_BOOLEAN) ? self::LABEL_ENABLED : self::LABEL_DISABLED;
    }

    private function loadReleaseDate(): string
    {
        $url = \sprintf(self::RELEASE_URL, Kernel::MAJOR_VERSION, Kernel::MINOR_VERSION);

        try {
            /** @phpstan-var array{release_date: string, ...} $content */
            $content = FileUtils::decodeJson($url);
            $date = $content['release_date'];

            return $this->formatMonthYear($date);
        } catch (\InvalidArgumentException) {
            return self::UNKNOWN;
        }
    }
}
