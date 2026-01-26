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
use App\Utils\StringUtils;
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
    public const string LABEL_DISABLED = 'Disabled';

    /**
     * The enabled label.
     */
    public const string LABEL_ENABLED = 'Enabled';

    /**
     * The not installed label.
     */
    public const string LABEL_NOT_INSTALLED = 'Not installed';

    // the release information URL
    private const string RELEASE_URL = 'https://symfony.com/releases/%s.%s.json';
    // the unknown label
    private const string UNKNOWN = 'Unknown';

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
        return $this->formatMonthYear(Kernel::END_OF_LIFE);
    }

    /**
     * Gets the end of maintenance.
     */
    public function getEndOfMaintenance(): string
    {
        return $this->formatMonthYear(Kernel::END_OF_MAINTENANCE);
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
        return StringUtils::startWith($this->getApcuStatus(), self::LABEL_ENABLED);
    }

    /**
     * Returns if the long-term support is enabled.
     */
    public function isLongTermSupport(): bool
    {
        return 4 === \intdiv(Kernel::VERSION_ID, 100) % 10;
    }

    /**
     * Returns if the 'Zend OP cache' extension is loaded and enabled.
     */
    public function isOpCacheEnabled(): bool
    {
        return StringUtils::startWith($this->getOpCacheStatus(), self::LABEL_ENABLED);
    }

    /**
     * Returns if the 'xdebug' extension is loaded and enabled.
     */
    public function isXdebugEnabled(): bool
    {
        return StringUtils::startWith($this->getXdebugStatus(), self::LABEL_ENABLED);
    }

    private function createDate(string $date): DatePoint
    {
        return DatePoint::createFromFormat('m/Y', $date);
    }

    private function formatMonthYear(string $date): string
    {
        return $this->createDate($date)->format('F Y');
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
        try {
            $url = \sprintf(self::RELEASE_URL, Kernel::MAJOR_VERSION, Kernel::MINOR_VERSION);

            /** @phpstan-var array{release_date: string} $content */
            $content = FileUtils::decodeJson($url);

            return $this->formatMonthYear($content['release_date']);
        } catch (\InvalidArgumentException) {
            return self::UNKNOWN;
        }
    }
}
