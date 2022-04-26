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
     * Parse value as float.
     */
    final public const METHOD_FLOAT = 2;

    /**
     * Parse value as integer.
     */
    final public const METHOD_INTEGER = 1;

    /**
     * Parse value as string.
     */
    final public const METHOD_STRING = 0;

    /**
     * Constructor.
     *
     * @param string      $name  the field name
     * @param string|null $title the field title
     */
    public function __construct(protected string $name, protected ?string $title = null, protected int $method = self::METHOD_STRING)
    {
    }

    /**
     * Gets the display value.
     * The default implementation returns the value as is. Subclass can override, for example to map the value.
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
     * Gets the value method.
     *
     * @return int one of the METHOD_XX constants
     */
    public function getMethod(): int
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
        if ($value) {
            return match ($this->method) {
                self::METHOD_FLOAT => (float) $value,
                self::METHOD_INTEGER => (int) $value,
                default => (string) $value,
            };
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize(): array
    {
        $result = [
            'name' => $this->name,
        ];

        if ($this->title) {
            $result['title'] = $this->title;
        }

        return $result;
    }

    /**
     * Sets the value method.
     *
     * @param int $method one of the METHOD_XX constants
     */
    public function setMethod(int $method): self
    {
        $this->method = match ($method) {
            self::METHOD_FLOAT,
            self::METHOD_INTEGER,
            self::METHOD_STRING => $method,
            default => $this->method,
        };

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
