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

use App\Entity\AbstractEntity;
use App\Interfaces\SortModeInterface;
use App\Util\FileUtils;
use Symfony\Component\PropertyAccess\Exception\AccessException;
use Symfony\Component\PropertyAccess\Exception\UnexpectedTypeException;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;

/**
 * The table column.
 */
class Column implements SortModeInterface, \Stringable
{
    /**
     * The property name of the field formatter.
     */
    final public const FIELD_FORMATTER = 'fieldFormatter';

    /**
     * The field alias name.
     */
    private ?string $alias = null;

    /**
     * The class for the element in the card view mode.
     */
    private ?string $cardClass = null;

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
    private string $field = '';

    /**
     * The field formatter (server side).
     *
     * @var callable|string|null
     */
    private $fieldFormatter = null;

    /**
     * The value indicating if this column is displayed as a numeric value.
     */
    private bool $numeric = false;

    /**
     * The sort order.
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
     * Creates columns from the given JSON file definitions.
     *
     * @param AbstractTable $parent the table owner
     * @param string        $path   the path to the JSON file definitions
     *
     * @return array<self> the column definitions
     */
    public static function fromJson(AbstractTable $parent, string $path): array
    {
        // decode
        /** @var array $definitions */
        $definitions = FileUtils::decodeJson($path);

        // definitions?
        if (empty($definitions)) {
            throw new \InvalidArgumentException("The file '$path' does not contain any definition.");
        }

        // accessor
        $accessor = PropertyAccess::createPropertyAccessor();

        // map
        return \array_map(function (array $definition) use ($parent, $accessor): self {
            $column = new self();
            /** @var mixed $value */
            foreach ($definition as $key => $value) {
                // special case for the field formatter
                if (self::FIELD_FORMATTER === $key) {
                    $value = [$parent, $value];
                }

                try {
                    $accessor->setValue($column, (string) $key, $value);
                } catch (AccessException|UnexpectedTypeException $e) {
                    $message = "Cannot set the property '$key'.";
                    throw new \InvalidArgumentException($message, 0, $e);
                }
            }

            return $column;
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
        // required
        $result = [
            'class' => $this->getClass(),
            'field' => $this->getAlias(),
            'sort-order' => $this->getOrder(),
            'visible' => \json_encode($this->isVisible()),
            'numeric' => \json_encode($this->isNumeric()),
            'sortable' => \json_encode($this->isSortable()),
            'card-visible' => \json_encode($this->isCardVisible()),
        ];

        // optional
        if ($this->cardClass) {
            $result['card-class'] = $this->cardClass;
        }
        if ($this->cellFormatter) {
            $result['formatter'] = $this->cellFormatter;
        }
        if ($this->styleFormatter) {
            $result['cell-style'] = $this->styleFormatter;
        }

        return $result;
    }

    public function getCardClass(): ?string
    {
        return $this->cardClass;
    }

    public function getCellFormatter(): ?string
    {
        return $this->cellFormatter;
    }

    public function getClass(): string
    {
        $class = (string) $this->class;
        if ($this->visible && !\str_contains($class, 'rowlink-skip')) {
            return \trim($class . ' user-select-none cursor-pointer');
        }

        return $class;
    }

    public function getField(): string
    {
        return $this->field;
    }

    public function getFieldFormatter(): callable|string|null
    {
        return $this->fieldFormatter;
    }

    /**
     * Gets the sorting order.
     *
     * @see SortModeInterface::SORT_ASC
     * @see SortModeInterface::SORT_DESC
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

    public function isCardVisible(): bool
    {
        return $this->visible && $this->cardVisible;
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
     * Map the given object to a string value using this field property.
     *
     * @param AbstractEntity|array $objectOrArray the object to map
     * @param PropertyAccessor     $accessor      the property accessor to get the object value
     *
     * @return string the mapped value
     */
    public function mapValue(AbstractEntity|array $objectOrArray, PropertyAccessor $accessor): string
    {
        // get value
        $property = \is_array($objectOrArray) ? $this->property : $this->field;
        /** @var mixed $value */
        $value = $accessor->getValue($objectOrArray, $property);

        // format
        return $this->formatValue($objectOrArray, $value);
    }

    public function setAlias(?string $alias): self
    {
        $this->alias = $alias;

        return $this;
    }

    public function setCardClass(?string $cardClass): self
    {
        $this->cardClass = $cardClass;

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

        return $this->updateProperty();
    }

    public function setFieldFormatter(callable|string|null $fieldFormatter): self
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
     * Sets the sorting order.
     *
     * @see SortModeInterface::SORT_ASC
     * @see SortModeInterface::SORT_DESC
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
     * Formats the given value using the field formatter if applicable.
     *
     * @param AbstractEntity|array $objectOrArray the object to map
     * @param mixed                $value         the value to format
     *
     * @return string the formatted value
     */
    private function formatValue(AbstractEntity|array $objectOrArray, mixed $value): string
    {
        if (\is_callable($this->fieldFormatter)) {
            return (string) \call_user_func($this->fieldFormatter, $value, $objectOrArray);
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
