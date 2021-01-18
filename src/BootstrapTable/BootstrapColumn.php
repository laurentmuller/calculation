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
    private ?string $class = null;

    private bool $default = false;

    private string $field;

    private ?string $fieldFormatter = null;

    private ?string $formatter = null;

    private string $order = Criteria::ASC;

    private bool $searchable = true;

    private bool $sortable = true;

    private ?string $title = null;

    private bool $visible = true;

    public function __toString(): string
    {
        return $this->field;
    }

    public function formatAmount(float $value): string
    {
        return FormatUtils::formatAmount($value);
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
        $field = $this->getField();
        $value = $accessor->getValue($objectOrArray, $field);
        if (\is_callable([$this, $this->fieldFormatter])) {
            $value = \call_user_func([$this, $this->fieldFormatter], $value);
        }

        return (string) $value;
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
        switch ($order) {
            case Criteria::ASC:
            case Criteria::DESC:
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

    public function setTitle(?string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function setVisible(bool $visible): self
    {
        $this->visible = $visible;

        return $this;
    }
}
