<?php
/*
 * This file is part of the Calculation package.
 *
 * (c) bibi.nu. <bibi@bibi.nu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace App\Pivot\Field;

/**
 * Represents a pivot field.
 *
 * @author Laurent Muller
 */
class PivotField implements \JsonSerializable
{
    /**
     * Parse value as float.
     */
    public const METHOD_FLOAT = 2;

    /**
     * Parse value as integer.
     */
    public const METHOD_INTEGER = 1;

    /**
     * Parse value as string.
     */
    public const METHOD_STRING = 0;

    protected int $method;

    protected string $name;

    /**
     * @var string
     */
    protected ?string $title;

    /**
     * Constructor.
     *
     * @param string $name  the field name
     * @param string $title the field title
     */
    public function __construct(string $name, ?string $title = null)
    {
        $this->name = $name;
        $this->title = $title;
        $this->method = self::METHOD_STRING;
    }

    /**
     * Gets the display value.
     *
     * The default implementation returns the value as is. Subclass can override, for example to map the value.
     *
     * @param mixed $value the field value
     *
     * @return mixed the display value
     */
    public function getDisplayValue($value)
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
     *
     * @return mixed the value
     */
    public function getValue(array $row)
    {
        if (isset($row[$this->name]) && $value = $row[$this->name]) {
            switch ($this->method) {
                case self::METHOD_FLOAT:
                    return (float) $value;
                case self::METHOD_INTEGER:
                    return (int) $value;
                case self::METHOD_STRING:
                default:
                    return (string) $value;
            }
        }

        return null;
    }

    /**
     * {@inheritdoc}
     *
     * @return mixed
     */
    public function jsonSerialize()
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
        switch ($method) {
            case self::METHOD_FLOAT:
            case self::METHOD_INTEGER:
            case self::METHOD_STRING:
                $this->method = $method;
                break;
        }

        return $this;
    }

    /**
     * Sets the title.
     *
     * @param string $title
     */
    public function setTitle(?string $title): self
    {
        $this->title = $title;

        return $this;
    }
}
