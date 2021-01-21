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

namespace App\BootstrapTable;

use App\Util\FormatUtils;
use Doctrine\Common\Collections\Criteria;
use Symfony\Component\PropertyAccess\PropertyAccessor;

/**
 * Bootstrap column definition.
 *
 * @author Laurent Muller
 */
class BootstrapColumn
{
    /**
     * The ascending sortable direction.
     */
    public const SORT_ASC = 'asc';

    /**
     * The descending sortable direction.
     */
    public const SORT_DESC = 'desc';

    /**
     * The callback formatter.
     *
     * @var callable|bool
     */
    private $callbackFormatter = null;

    private ?string $class = null;

    private bool $default = false;

    private string $field;

    private ?string $fieldFormatter = null;

    private ?string $formatter = null;

    private string $order = Criteria::ASC;

    private bool $searchable = true;

    private bool $sortable = true;

    private ?string $styleFormatter = null;

    private ?string $title = null;

    private bool $virtual = false;

    private bool $visible = true;

    public function __toString(): string
    {
        return $this->field;
    }

    public function formatAmount(float $value): string
    {
        return FormatUtils::formatAmount($value);
    }

    public function formatDate(\DateTimeInterface $value): string
    {
        return FormatUtils::formatDate($value);
    }

    public function formatId(int $value): string
    {
        return FormatUtils::formatId($value);
    }

    public function formatPercentSign(float $value): string
    {
        return FormatUtils::formatPercent($value);
    }

    public function getClass(): ?string
    {
        return $this->class;
    }

    public function getField(): string
    {
        return $this->field;
    }

    public function getFieldFormatter(): ?string
    {
        return $this->fieldFormatter;
    }

    public function getFormatter(): ?string
    {
        return $this->formatter;
    }

    public function getOrder(): string
    {
        return $this->order;
    }

    public function getStyleFormatter(): ?string
    {
        return $this->styleFormatter;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function isDefault(): bool
    {
        return $this->default;
    }

    public function isSearchable(): bool
    {
        return $this->searchable;
    }

    public function isSortable(): bool
    {
        return $this->sortable;
    }

    public function isVirtual(): bool
    {
        return $this->virtual;
    }

    public function isVisible(): bool
    {
        return $this->visible;
    }

    /**
     * Map the given object to a string value using this field property.
     *
     * @param mixed            $objectOrArray the object to map
     * @param PropertyAccessor $accessor      the property accessor to get the object value
     *
     * @return string the mapped value
     */
    public function mapValue($objectOrArray, PropertyAccessor $accessor): string
    {
        if ($this->virtual) {
            return (string) $accessor->getValue($objectOrArray, 'id');
        }

        // get value
        $value = $accessor->getValue($objectOrArray, $this->field);

        // format
        return $this->formatValue($value);
    }

    public function setClass(?string $class): self
    {
        $this->class = $class;

        return $this;
    }

    public function setDefault(bool $default): self
    {
        $this->default = $default;

        return $this;
    }

    public function setField(string $field): self
    {
        $this->field = $field;

        return $this;
    }

    public function setFieldFormatter(?string $fieldFormatter): self
    {
        $this->fieldFormatter = $fieldFormatter;

        return $this;
    }

    public function setFormatter(?string $formatter): self
    {
        $this->formatter = $formatter;

        return $this;
    }

    public function setOrder(string $order): self
    {
        $order = \strtolower($order);
        switch ($order) {
            case self::SORT_ASC:
            case self::SORT_DESC:
                $this->order = $order;
            break;
        }

        return $this;
    }

    public function setSearchable(bool $searchable): self
    {
        $this->searchable = $searchable;

        return $this;
    }

    public function setSortable(bool $sortable): self
    {
        $this->sortable = $sortable;

        return $this;
    }

    public function setStyleFormatter(?string $styleFormatter): self
    {
        $this->styleFormatter = $styleFormatter;

        return $this;
    }

    public function setTitle(?string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function setVirtual(bool $virtual): self
    {
        $this->virtual = $virtual;

        return $this;
    }

    public function setVisible(bool $visible): self
    {
        $this->visible = $visible;

        return $this;
    }

    /**
     * Formats the given value using the field formatter if applicable.
     *
     * @param mixed $value the value to format
     *
     * @return string the formatted value
     */
    private function formatValue($value): string
    {
        if (null === $this->callbackFormatter) {
            if (\is_callable([$this, $this->fieldFormatter])) {
                $this->callbackFormatter = [$this, $this->fieldFormatter];
            } else {
                $this->callbackFormatter = false;
            }
        }
        if ($this->callbackFormatter) {
            return (string) \call_user_func($this->callbackFormatter, $value);
        }

        return (string) $value;
    }
}
