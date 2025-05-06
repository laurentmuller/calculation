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
use App\Utils\StringUtils;
use Symfony\Component\PropertyAccess\Exception\ExceptionInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * The table column.
 *
 * @phpstan-type EntityType = EntityInterface|array{id: int, ...}
 */
class Column implements \Stringable, SortModeInterface
{
    /**
     * The property name of the field formatter.
     */
    private const FIELD_FORMATTER = 'fieldFormatter';

    /**
     * The shared property accessor to map JSON columns or values.
     */
    private static ?PropertyAccessorInterface $accessor = null;

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
     * @phpstan-var string|callable(mixed, EntityType): string|null
     */
    private $fieldFormatter;

    /**
     * The value indicating if this column is displayed as a numeric value.
     */
    private bool $numeric = false;

    /**
     * The sort order.
     *
     * @phpstan-var self::SORT_*
     */
    private string $order = self::SORT_ASC;

    /**
     * The property path for an array object.
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

    #[\Override]
    public function __toString(): string
    {
        return $this->field;
    }

    /**
     * Create the action column.
     */
    public static function createColumnAction(): self
    {
        return self::instance('id')
            ->setAlias('action')
            ->setSortable(false)
            ->setSearchable(false)
            ->setCellFormatter('renderActions')
            ->setClass('actions rowlink-skip d-print-none');
    }

    /**
     * Creates columns from the given JSON file definitions.
     *
     * @param AbstractTable $parent the table owner
     * @param string        $path   the path to the JSON file definitions
     *
     * @return Column[] the column definitions
     *
     * @throws \InvalidArgumentException if the given file contains no definition, or if a property cannot be set
     */
    public static function fromJson(AbstractTable $parent, string $path): array
    {
        /** @var array<array<string, string|bool>> $definitions */
        $definitions = FileUtils::decodeJson($path);
        if ([] === $definitions) {
            throw new \InvalidArgumentException(\sprintf('The file "%s" contains no definition.', $path));
        }

        $accessor = self::getAccessor();

        return \array_map(
            static fn (array $definition): self => self::mapDefinition($parent, $definition, $accessor),
            $definitions
        );
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
        if (StringUtils::isString($this->cellFormatter)) {
            $result['formatter'] = $this->cellFormatter;
        }
        if (StringUtils::isString($this->styleFormatter)) {
            $result['cell-style'] = $this->styleFormatter;
        }

        return $result;
    }

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

    public function getFieldFormatter(): string|callable|null
    {
        return $this->fieldFormatter;
    }

    /**
     * Gets the default sorting order.
     *
     * @phpstan-return self::SORT_*
     */
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

    /**
     * Create a new instance with the given field.
     */
    public static function instance(string $field = ''): self
    {
        return (new self())->setField($field);
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
     * Map the given entity or array to a string value using this field.
     *
     * @phpstan-param EntityType $objectOrArray the entity or array to map
     */
    public function mapValue(EntityInterface|array $objectOrArray): string
    {
        $property = \is_array($objectOrArray) ? $this->property : $this->field;
        /** @phpstan-var mixed $value */
        $value = self::getAccessor()->getValue($objectOrArray, $property);

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
        $this->property = '[' . \str_replace('.', '].[', $field) . ']';

        return $this;
    }

    /**
     * @phpstan-param string|callable(mixed, EntityType): string|null $fieldFormatter
     */
    public function setFieldFormatter(string|callable|null $fieldFormatter): self
    {
        $this->fieldFormatter = $fieldFormatter;

        return $this;
    }

    public function setNumeric(bool $numeric): self
    {
        $this->numeric = $numeric;

        return $this;
    }

    /**
     * Sets the default sorting order.
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
     * @phpstan-param EntityType $objectOrArray
     * @phpstan-param mixed      $value
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

    private static function getAccessor(): PropertyAccessorInterface
    {
        if (!self::$accessor instanceof PropertyAccessorInterface) {
            return self::$accessor = PropertyAccess::createPropertyAccessor();
        }

        return self::$accessor;
    }

    /**
     * @param array<string, string|bool> $definition
     *
     * @throws \InvalidArgumentException if a property cannot be set
     */
    private static function mapDefinition(
        AbstractTable $parent,
        array $definition,
        PropertyAccessorInterface $accessor
    ): self {
        $column = self::instance();
        foreach ($definition as $key => $value) {
            // special case for the field formatter
            if (self::FIELD_FORMATTER === $key) {
                $value = [$parent, $value];
            }

            try {
                $accessor->setValue($column, $key, $value);
            } catch (ExceptionInterface $e) {
                throw new \InvalidArgumentException(\sprintf('Unable set the property "%s".', $key), $e->getCode(), $e);
            }
        }

        return $column;
    }
}
