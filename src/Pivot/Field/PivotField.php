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
        protected string $name,
        protected ?string $title = null,
        protected PivotMethod $method = PivotMethod::STRING
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
     * Gets the conversion value method.
     */
    public function getMethod(): PivotMethod
    {
        return $this->method;
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
     */
    public function getTitle(): ?string
    {
        return $this->title;
    }

    /**
     * Gets the field value.
     *
     * @param array $row the dataset row
     */
    public function getValue(array $row): float|int|string|\DateTimeInterface|null
    {
        /** @psalm-var mixed $value */
        $value = $this->getRowValue($row);

        if (\is_scalar($value)) {
            return $this->method->convert($value);
        }

        return null;
    }

    public function jsonSerialize(): array
    {
        return \array_filter([
            'name' => $this->name,
            'title' => $this->title,
        ]);
    }

    /**
     * Sets the conversion value method.
     */
    public function setMethod(PivotMethod $method): self
    {
        $this->method = $method;

        return $this;
    }

    /**
     * Sets the title.
     */
    public function setTitle(?string $title): self
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Gets the row value.
     */
    protected function getRowValue(array $row): mixed
    {
        return $row[$this->name] ?? null;
    }
}
