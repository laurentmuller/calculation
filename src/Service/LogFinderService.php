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

use App\Model\LogFileEntry;
use App\Traits\ComparableTrait;
use Symfony\Component\Clock\DatePoint;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Finder\Finder;

/**
 * Service to find log files.
 */
class LogFinderService
{
    use ComparableTrait;

    private const DATE_FORMAT = 'Y-m-d';
    private const FILE_EXTENSION = '.log';
    private const FILE_NAMES = [
        '{dev,test,prod}-????-??-??.log',
        'deprecations.log',
    ];

    public function __construct(
        #[Autowire('%kernel.logs_dir%')]
        private readonly string $path
    ) {
    }

    /**
     * Find log files.
     *
     * @return array<string, LogFileEntry>
     */
    public function find(): array
    {
        $results = [];
        $finder = $this->createFinder();
        foreach ($finder as $file) {
            $path = $file->getRealPath();
            $name = $file->getBasename(self::FILE_EXTENSION);
            $date = $this->getDate($name, (int) $file->getMTime());
            $results[$name] = LogFileEntry::instance($name, $path, $date);
        }

        return $this->sortComparable($results);
    }

    private function createFinder(): Finder
    {
        return Finder::create()
            ->in($this->path)
            ->name(self::FILE_NAMES)
            ->files();
    }

    private function getDate(string $name, int $default): DatePoint
    {
        try {
            return DatePoint::createFromFormat(self::DATE_FORMAT, \substr($name, -10));
        } catch (\DateMalformedStringException) {
            return DatePoint::createFromTimestamp($default);
        }
    }
}
