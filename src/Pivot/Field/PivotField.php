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

use Symfony\Component\Clock\DatePoint;

/**
 * Represents a pivot field.
 */
class PivotField implements \JsonSerializable
{
    /**
     * @param string  $name  the field name
     * @param ?string $title the field title
     */
    public function __construct(
        protected readonly string $name,
        protected readonly ?string $title = null
    ) {
    }

    /**
     * Gets the display value.
     *
     * The default implementation returns the value as is. Subclass can override, for example, to map the value.
     *
     * @param mixed $value the field value
     *
     * @return mixed the display value
     */
    public function getDisplayValue(mixed $value): mixed
    {
        return $value;
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
     * @param array $row the dataset row
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
