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

use App\Enums\SortMode;
use App\Interfaces\EntityInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * The table column.
 *
 * @phpstan-type EntityType = EntityInterface|array{id: int, ...}
 * @phpstan-type ColumnType = array{order?: string, ...}
 */
class Column implements \Stringable
{
    /** The field alias name. */
    public ?string $alias = null {
        get => $this->alias ?? $this->field;
    }

    /** The cell formatter (client side). */
    public ?string $cellFormatter = null;

    /** The cell class. */
    public ?string $class = null {
        get {
            $class = (string) $this->class;
            if ($this->sortable && !\str_contains($class, 'rowlink-skip')) {
                return \trim($class . ' user-select-none cursor-pointer');
            }

            return $class;
        }
    }

    /** The default sorted column. */
    public bool $default = false;

    /** The field name. */
    public string $field = '' {
        set {
            $this->field = $value;
            $this->property = '[' . \str_replace('.', '].[', $value) . ']';
        }
    }

    /**
     * The field formatter (server side).
     *
     * @var ?callable(mixed, EntityType=): string
     */
    public $fieldFormatter {
        /** @throws \ReflectionException */
        set {
            $this->fieldFormatter = $value;
            $this->requiredParameters = $this->getRequiredParameters($value);
        }
    }

    /** The value indicating if this column is displayed as a numeric value. */
    public bool $numeric = false;

    /** The sort order. */
    public SortMode $order = SortMode::DEFAULT;

    /** The searchable behavior. */
    public bool $searchable = true {
        get => $this->visible && $this->searchable;
    }

    /** The sortable behavior. */
    public bool $sortable = true {
        get => $this->visible && $this->sortable;
    }

    /** The style formatter (client side). */
    public ?string $styleFormatter = null;

    /** The column title to be translated. */
    public ?string $title = null;

    /** The visible behavior. */
    public bool $visible = true;

    /** The shared property accessor to map JSON columns or values. */
    private static ?PropertyAccessorInterface $accessor = null;

    /** The property path for an array object. */
    private string $property = '';

    /** The number of required parameters for the formatter callable. */
    private int $requiredParameters = 2;

    #[\Override]
    public function __toString(): string
    {
        return $this->field;
    }

    /**
     * Gets the data attributes used to output the column to the Twig template.
     */
    public function attributes(): array
    {
        return \array_filter(
            [
                'class' => $this->class,
                'field' => $this->alias,
                'sort-order' => $this->order,
                'visible' => $this->visible,
                'numeric' => $this->numeric,
                'sortable' => $this->sortable,
                'searchable' => $this->searchable,
                'default' => $this->default,
                'formatter' => $this->cellFormatter,
                'cell-style' => $this->styleFormatter,
            ],
            static fn (mixed $value): bool => null !== $value
        );
    }

    /**
     * Create a new instance with the given field.
     */
    public static function instance(string $field = ''): self
    {
        return new self()->withField($field);
    }

    /**
     * Map the given entity or array to a string value using this field.
     *
     * @param EntityType $objectOrArray the entity or array to map
     */
    public function mapValue(EntityInterface|array $objectOrArray): string
    {
        $propertyPath = $this->getPropertyPath($objectOrArray);
        $value = $this->getAccessor()->getValue($objectOrArray, $propertyPath);

        return $this->formatValue($objectOrArray, $value);
    }

    public function withAlias(?string $alias): self
    {
        $this->alias = $alias;

        return $this;
    }

    public function withCellFormatter(?string $cellFormatter): self
    {
        $this->cellFormatter = $cellFormatter;

        return $this;
    }

    public function withClass(?string $class): self
    {
        $this->class = $class;

        return $this;
    }

    public function withDefault(bool $default): self
    {
        $this->default = $default;

        return $this;
    }

    public function withField(string $field): self
    {
        $this->field = $field;

        return $this;
    }

    /**
     * @param ?callable(mixed, EntityType=): string $fieldFormatter
     */
    public function withFieldFormatter(?callable $fieldFormatter): self
    {
        $this->fieldFormatter = $fieldFormatter;

        return $this;
    }

    public function withNumeric(bool $numeric): self
    {
        $this->numeric = $numeric;

        return $this;
    }

    /**
     * Sets the default sorting order.
     */
    public function withOrder(SortMode $order): self
    {
        $this->order = $order;

        return $this;
    }

    public function withSearchable(bool $searchable): self
    {
        $this->searchable = $searchable;

        return $this;
    }

    public function withSortable(bool $sortable): self
    {
        $this->sortable = $sortable;

        return $this;
    }

    public function withStyleFormatter(?string $styleFormatter): self
    {
        $this->styleFormatter = $styleFormatter;

        return $this;
    }

    public function withTitle(?string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function withVisible(bool $visible): self
    {
        $this->visible = $visible;

        return $this;
    }

    /**
     * @phpstan-param EntityType $objectOrArray
     */
    private function formatValue(EntityInterface|array $objectOrArray, mixed $value): string
    {
        if (\is_callable($this->fieldFormatter)) {
            if (1 === $this->requiredParameters) {
                return \call_user_func($this->fieldFormatter, $value);
            }

            return \call_user_func($this->fieldFormatter, $value, $objectOrArray);
        }

        if (\is_bool($value)) {
            return $value ? '1' : '0';
        }

        return (string) $value;
    }

    private function getAccessor(): PropertyAccessorInterface
    {
        return self::$accessor ??= PropertyAccess::createPropertyAccessor();
    }

    private function getPropertyPath(EntityInterface|array $objectOrArray): string
    {
        return \is_object($objectOrArray) ? $this->field : $this->property;
    }

    /**
     * @throws \ReflectionException
     */
    private function getRequiredParameters(?callable $value): int
    {
        if (\is_callable($value)) {
            return new \ReflectionFunction($value(...))->getNumberOfRequiredParameters();
        }

        return 2;
    }
}
