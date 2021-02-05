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

use App\Util\FileUtils;
use Symfony\Component\PropertyAccess\Exception\AccessException;
use Symfony\Component\PropertyAccess\Exception\UnexpectedTypeException;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;

/**
 * The table column.
 *
 * @author Laurent Muller
 */
class Column implements \JsonSerializable
{
    /**
     * The action column name.
     */
    public const COL_ACTION = 'action';

    /**
     * The property name of the field formatter.
     */
    public const FIELD_FORMATTER = 'fieldFormatter';

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
     * The property path for array object.
     */
    private string $property;

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

    /**
     * Creates columns from the given JSON file definitions.
     *
     * @param AbstractTable $parent the table owner
     * @param string        $path   the path to the JSON file definitions
     *
     * @return Column[] the column definitions
     *
     * @throws \InvalidArgumentException if the definitions can not be parsed
     */
    public static function fromJson(AbstractTable $parent, string $path): array
    {
        // decode
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
            foreach ($definition as $key => $value) {
                // special case for the field formatter
                if (self::FIELD_FORMATTER === $key) {
                    $value = [$parent, $value];
                }

                try {
                    $accessor->setValue($column, $key, $value);
                } catch (AccessException | UnexpectedTypeException $e) {
                    $message = "Cannot set the property '$key'.";
                    throw new \InvalidArgumentException($message, 0, $e);
                }
            }

            return $column;
        }, $definitions);
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
     * {@inheritdoc}
     */
    public function jsonSerialize()
    {
        $result = [
            'field' => $this->field,
            'visible' => $this->visible,
            'sortable' => $this->sortable,
            'sortOrder' => $this->order,
            'cardBold' => $this->cardBold,
            'cardVisible' => $this->cardVisible,
        ];
        if (null !== $this->class) {
            $result['class'] = $this->class;
        }
        if (null !== $this->title) {
            $result['title'] = $this->title;
        }
        if (null !== $this->cellFormatter) {
            $result['cellFormatter'] = $this->cellFormatter;
        }
        if (null !== $this->styleFormatter) {
            $result['styleFormatter'] = $this->styleFormatter;
        }

        return $result;
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
        // special case for actions column
        if (self::COL_ACTION === $this->field) {
            $property = \is_array($objectOrArray) ? '[id]' : 'id';

            return (string) $accessor->getValue($objectOrArray, $property);
        }

        // get value
        $property = \is_array($objectOrArray) ? $this->property : $this->field;
        $value = $accessor->getValue($objectOrArray, $property);

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

        return $this->updateProperty();
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

    /**
     * Update property path for array object.
     */
    private function updateProperty(): self
    {
        if ($this->field) {
            $this->property = \str_replace('.', '].[', '[' . $this->field . ']');
        }

        return $this;
    }
}
