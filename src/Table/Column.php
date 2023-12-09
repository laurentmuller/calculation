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

namespace App\Table;

use App\Interfaces\EntityInterface;
use App\Interfaces\SortModeInterface;
use App\Utils\FileUtils;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;

/**
 * The table column.
 */
class Column implements \Stringable, SortModeInterface
{
    /**
     * The property name of the field formatter.
     */
    private const FIELD_FORMATTER = 'fieldFormatter';

    /**
     * The field alias name.
     */
    private ?string $alias = null;

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
    private string $field = '';

    /**
     * The field formatter (server side).
     *
     * @var string|callable|null
     *
     * @psalm-var string|callable(mixed, EntityInterface|array): string|null
     */
    private $fieldFormatter;

    /**
     * The value indicating if this column is displayed as a numeric value.
     */
    private bool $numeric = false;

    /**
     * The sort order.
     *
     * @psalm-var SortModeInterface::*
     */
    private string $order = self::SORT_ASC;

    /**
     * The property path for array object.
     */
    private string $property = '';

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
     * The column title to be translated.
     */
    private ?string $title = null;

    /**
     * The visible behavior.
     */
    private bool $visible = true;

    public function __toString(): string
    {
        return $this->field;
    }

    /**
     * Create the action column.
     */
    public static function createColumnAction(): self
    {
        $column = new self();
        $column->setField('id')
            ->setAlias('action')
            ->setSortable(false)
            ->setSearchable(false)
            ->setCellFormatter('formatActions')
            ->setClass('actions rowlink-skip d-print-none');

        return $column;
    }

    /**
     * Creates columns from the given JSON file definitions.
     *
     * @param AbstractTable $parent the table owner
     * @param string        $path   the path to the JSON file definitions
     *
     * @return Column[] the column definitions
     *
     * @throws \InvalidArgumentException if a property cannot be set
     */
    public static function fromJson(AbstractTable $parent, string $path): array
    {
        /** @var array<array<string, mixed>> $definitions */
        $definitions = FileUtils::decodeJson($path);
        if ([] === $definitions) {
            throw new \InvalidArgumentException("The file '$path' does not contain any definition.");
        }

        $accessor = PropertyAccess::createPropertyAccessor();

        return \array_map(function (array $definition) use ($parent, $accessor): self {
            $column = new self();

            /** @psalm-var mixed $value */
            foreach ($definition as $key => $value) {
                // special case for the field formatter
                if (self::FIELD_FORMATTER === $key) {
                    $value = [$parent, $value];
                }

                try {
                    /*
                     * @param Column $column
                     * @param-out Column $column
                     */
                    $accessor->setValue($column, $key, $value);
                } catch (\Exception $e) {
                    throw new \InvalidArgumentException(\sprintf("Cannot set the property '%s'.", $key), (int) $e->getCode(), $e);
                }
            }

            return $column instanceof self ? $column : new self();
        }, $definitions);
    }

    public function getAlias(): string
    {
        return $this->alias ?? $this->field;
    }

    /**
     * Gets the attributes used to output the column to the Twig template.
     */
    public function getAttributes(): array
    {
        $result = [
            'class' => $this->getClass(),
            'field' => $this->getAlias(),
            'sort-order' => $this->getOrder(),
            'visible' => $this->isVisible(),
            'numeric' => $this->isNumeric(),
            'sortable' => $this->isSortable(),
            'searchable' => $this->isSearchable(),
            'default' => $this->isDefault(),
        ];
        if ($this->cellFormatter) {
            $result['formatter'] = $this->cellFormatter;
        }
        if ($this->styleFormatter) {
            $result['cell-style'] = $this->styleFormatter;
        }

        return $result;
    }

    /**
     * @psalm-api
     */
    public function getCellFormatter(): ?string
    {
        return $this->cellFormatter;
    }

    public function getClass(): string
    {
        $class = (string) $this->class;
        if ($this->isSortable() && !\str_contains($class, 'rowlink-skip')) {
            return \trim($class . ' user-select-none cursor-pointer');
        }

        return $class;
    }

    public function getField(): string
    {
        return $this->field;
    }

    /**
     * @psalm-api
     */
    public function getFieldFormatter(): string|callable|null
    {
        return $this->fieldFormatter;
    }

    /**
     * Gets the default sorting order.
     *
     * @psalm-return SortModeInterface::*
     */
    public function getOrder(): string
    {
        return $this->order;
    }

    /**
     * @psalm-api
     */
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

    public function isNumeric(): bool
    {
        return $this->numeric;
    }

    public function isSearchable(): bool
    {
        return $this->visible && $this->searchable;
    }

    public function isSortable(): bool
    {
        return $this->visible && $this->sortable;
    }

    public function isVisible(): bool
    {
        return $this->visible;
    }

    /**
     * Map the given entity or array to a string value using this field property.
     *
     * @param EntityInterface|array $objectOrArray the entity or array to map
     * @param PropertyAccessor      $accessor      the property accessor to get the value
     *
     * @return string the mapped value
     */
    public function mapValue(EntityInterface|array $objectOrArray, PropertyAccessor $accessor): string
    {
        $property = \is_array($objectOrArray) ? $this->property : $this->field;
        /** @psalm-var mixed $value */
        $value = $accessor->getValue($objectOrArray, $property);

        return $this->formatValue($objectOrArray, $value);
    }

    public function setAlias(?string $alias): self
    {
        $this->alias = $alias;

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

        return $this->updateProperty();
    }

    /**
     * @psalm-param string|callable(mixed, EntityInterface|array): string|null $fieldFormatter
     *
     * @psalm-api
     */
    public function setFieldFormatter(string|callable|null $fieldFormatter): self
    {
        $this->fieldFormatter = $fieldFormatter;

        return $this;
    }

    /**
     * @psalm-api
     */
    public function setNumeric(bool $numeric): self
    {
        $this->numeric = $numeric;

        return $this;
    }

    /**
     * Sets the default sorting order.
     *
     * @psalm-api
     */
    public function setOrder(string $order): self
    {
        $order = \strtolower($order);
        $this->order = match ($order) {
            self::SORT_ASC,
            self::SORT_DESC => $order,
            default => $this->order,
        };

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

    /**
     * @psalm-api
     */
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
     * @param EntityInterface|array $objectOrArray the parent entity or array
     * @param mixed                 $value         the value to format
     *
     * @return string the formatted value
     */
    private function formatValue(EntityInterface|array $objectOrArray, mixed $value): string
    {
        if (\is_callable($this->fieldFormatter)) {
            return \call_user_func($this->fieldFormatter, $value, $objectOrArray);
        }
        if (\is_bool($value)) {
            return $value ? '1' : '0';
        }

        return (string) $value;
    }

    /**
     * Update property path for array object.
     */
    private function updateProperty(): self
    {
        if ('' !== $this->field) {
            $this->property = \str_replace('.', '].[', '[' . $this->field . ']');
        }

        return $this;
    }
}
