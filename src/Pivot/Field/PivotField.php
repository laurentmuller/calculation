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

namespace App\Pivot\Field;

use App\Pivot\Formatter\DefaultFormatter;
use App\Pivot\Formatter\FormatterInterface;
use Symfony\Component\Clock\DatePoint;

/**
 * Represents a pivot field.
 */
class PivotField implements \JsonSerializable
{
    private readonly FormatterInterface $formatter;

    /**
     * @param string              $name      the field name
     * @param ?string             $title     the field title
     * @param ?FormatterInterface $formatter the optional formatter
     */
    public function __construct(
        protected readonly string $name,
        protected readonly ?string $title = null,
        ?FormatterInterface $formatter = null
    ) {
        $this->formatter = $formatter ?? new DefaultFormatter();
    }

    /**
     * Gets the display value.
     */
    public function getDisplayValue(int|float|string $value): int|float|string
    {
        return $this->formatter->format($value);
    }

    /**
     * Gets the field name.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Gets the field title.
     *
     * @return string the title or the name if not set
     */
    public function getTitle(): string
    {
        return $this->title ?? $this->name;
    }

    /**
     * Gets the field value.
     *
     * @param array<array-key, DatePoint|int|float|string> $row the dataset row
     */
    public function getValue(array $row): DatePoint|int|float|string|null
    {
        return $row[$this->name] ?? null;
    }

    #[\Override]
    public function jsonSerialize(): array
    {
        return \array_filter([
            'name' => $this->name,
            'title' => $this->title,
        ]);
    }
}
