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

namespace App\Model;

use App\Interfaces\ComparableInterface;
use App\Traits\ClosureSortTrait;
use Symfony\Component\Clock\DatePoint;

/**
 * Contains information about a log file.
 *
 * @template-implements ComparableInterface<LogFileEntry>
 */
class LogFileEntry implements \Stringable, ComparableInterface
{
    use ClosureSortTrait;

    /**
     * @param string    $name the log file name
     * @param string    $path the log file path
     * @param DatePoint $date the date of the log file
     */
    public function __construct(
        public string $name,
        public string $path,
        public DatePoint $date
    ) {
    }

    #[\Override]
    public function __toString(): string
    {
        return $this->name;
    }

    #[\Override]
    public function compare(ComparableInterface $other): int
    {
        return $this->compareByClosures(
            $this,
            $other,
            static fn (LogFileEntry $a, LogFileEntry $b): int => $a->isDeprecation() <=> $b->isDeprecation(),
            static fn (LogFileEntry $a, LogFileEntry $b): int => $a->date <=> $b->date,
        );
    }

    /**
     * Creates a new instance.
     *
     * @param string    $name the log file name
     * @param string    $path the log file path
     * @param DatePoint $date the date of the log file
     */
    public static function instance(string $name, string $path, DatePoint $date): self
    {
        return new self($name, $path, $date);
    }

    /**
     * Gets a value indicating whether this log file is a deprecation log file.
     */
    public function isDeprecation(): bool
    {
        return 'deprecations' === $this->name;
    }
}
