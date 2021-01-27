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

use Symfony\Component\PropertyAccess\PropertyAccessor;

/**
 * Bootstrap column definition.
 *
 * @author Laurent Muller
 */
class BootstrapColumn
{
    /**
     * The action column name.
     */
    public const COL_ACTION = 'action';

    /**
     * The ascending sortable direction.
     */
    public const SORT_ASC = 'asc';

    /**
     * The descending sortable direction.
     */
    public const SORT_DESC = 'desc';

    /**
     * Use the bold font style for the element when displayed in card view.
     */
    private bool $cardBold = false;

    /**
     * The displayed behavior for the element in the card view mode.
     */
    private bool $cardVisible = true;

    /**
     * The cell formatter (client side).
     */
    private ?string $cellFormatter = null;

    /**
     * The cell class.
     */
    private ?string $class = null;

    /**
     * The default sorted column.
     */
    private bool $default = false;

    /**
     * The field name.
     */
    private string $field;

    /**
     * The field formatter (server side).
     *
     * @var string|callable|null
     */
    private $fieldFormatter = null;

    /**
     * The sort order.
     */
    private string $order = self::SORT_ASC;

    /**
     * The searchable behavior.
     */
    private bool $searchable = true;

    /**
     * The sortable behavior.
     */
    private bool $sortable = true;

    /**
     * The style formatter (client side).
     */
    private ?string $styleFormatter = null;

    /**
     * The column title to be transalted.
     */
    private ?string $title = null;

    /**
     * The visible behavior.
     */
    private bool $visible = true;

    public function __toString(): string
    {
        return (string) $this->field;
    }

    public function getCellFormatter(): ?string
    {
        return $this->cellFormatter;
    }

    public function getClass(): ?string
    {
        return $this->class;
    }

    public function getField(): string
    {
        return $this->field;
    }

    /**
     * @return string|callable|null
     */
    public function getFieldFormatter()
    {
        return $this->fieldFormatter;
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

    public function isCardBold(): bool
    {
        return $this->cardBold;
    }

    public function isCardVisible(): bool
    {
        return $this->cardVisible;
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
        if (self::COL_ACTION === $this->field) {
            return (string) $accessor->getValue($objectOrArray, 'id');
        }

        // get value
        $value = $accessor->getValue($objectOrArray, $this->field);

        // format
        return $this->formatValue($objectOrArray, $value);
    }

    public function setCardBold(bool $cardBold): self
    {
        $this->cardBold = $cardBold;

        return $this;
    }

    public function setCardVisible(bool $cardVisible): self
    {
        $this->cardVisible = $cardVisible;

        return $this;
    }

    public function setCellFormatter(?string $cellFormatter): self
    {
        $this->cellFormatter = $cellFormatter;

        return $this;
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

    /**
     * @param string|callable|null $fieldFormatter
     */
    public function setFieldFormatter($fieldFormatter): self
    {
        $this->fieldFormatter = $fieldFormatter;

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

    public function setVisible(bool $visible): self
    {
        $this->visible = $visible;

        return $this;
    }

    /**
     * Formats the given value using the field formatter if applicable.
     *
     * @param mixed $objectOrArray the object to map
     * @param mixed $value         the value to format
     *
     * @return string the formatted value
     */
    private function formatValue($objectOrArray, $value): string
    {
        if (\is_callable($this->fieldFormatter)) {
            return (string) \call_user_func($this->fieldFormatter, $value, $objectOrArray);
        }

        return (string) $value;
    }
}
