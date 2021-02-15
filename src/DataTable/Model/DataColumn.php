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

namespace App\DataTable\Model;

use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * Represents a column in DataTables.
 *
 * @author Laurent Muller
 */
class DataColumn
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
     * The function name used to update the cell.
     *
     * @var string
     */
    protected $callback;

    /**
     * The class name.
     *
     * @var string
     */
    protected $class;

    /**
     * The default sortable column.
     *
     * @var bool
     */
    protected $default = false;

    /**
     * The sorting direction ('asc' or 'desc').
     *
     * @var string
     */
    protected $direction = self::SORT_ASC;

    /**
     * Either a sprintf compatible format string, or a callable function providing rendering conversion, or default null.
     *
     * @var string|callable
     */
    protected $formatter;

    /**
     * The header class name.
     *
     * @var string
     */
    protected $headerClass;

    /**
     * The mapped fields.
     *
     * @var string[]
     */
    protected $map;

    /**
     * The field name.
     *
     * @var string
     */
    protected $name;

    /**
     * The orderable behavior.
     *
     * @var bool
     */
    protected $orderable = true;

    /**
     * The property path for array object.
     *
     * @var string
     */
    protected $property;

    /**
     * The cell renderer behavior.
     *
     * @var bool
     */
    protected $rawData = false;

    /**
     * The function name used to render a cell.
     *
     * @var string
     */
    protected $render;

    /**
     * The searchable behavior.
     *
     * @var bool
     */
    protected $searchable = true;

    /**
     * The title (to be translated).
     *
     * @var string
     */
    protected $title;

    /**
     * The visibility behavior.
     *
     * @var bool
     */
    protected $visible = true;

    /**
     * Constructor.
     *
     * @param string $name  the field name
     * @param string $class the cell class name
     */
    public function __construct(string $name = null, string $class = null)
    {
        $this->name = $name;
        $this->class = $class;
        $this->updateProperty();
    }

    /**
     * Gets the cell value for the given data and convert to a string.
     *
     * This implementation do the following:
     * <ul>
     * <li>First, get the cell value for the given data.</li>
     * <li>Then if this formatter is a string, format the <code>value</code> using the <code>sprintf</code> function.</li>
     * <li>Else if this formatter is callable, convert the <code>value</code> using the <code>call_user_func()</code> function with the <code>value</code> and the <code>data</code> as parameters.</li>
     * <li>Else cast the <code>value</code> as string.</li>
     * </ul>
     *
     * @param object|array $data the object or array to traverse
     *
     * @return string the value as string
     */
    public function convertValue($data): string
    {
        // get value
        $property = \is_array($data) ? $this->property : $this->name;
        $value = self::accessor()->getValue($data, $property);

        // format
        if (\is_string($this->formatter)) {
            if (null !== $value) {
                return \sprintf($this->formatter, $value);
            }

            return '';
        }

        // convert
        if (\is_callable($this->formatter)) {
            return \call_user_func($this->formatter, $value, $data);
        }

        // default
        return (string) $value;
    }

    /**
     * Creates a search parameter.
     *
     * @param string $value the value to search for
     *
     * @return array the search parameter
     */
    public static function createSearch(?string $value = null): array
    {
        return [
            'value' => $value,
            'regex' => \json_encode(false),
        ];
    }

    /**
     * Convert this column to an array of attributes.
     */
    public function getAttributes(): array
    {
        return [
            'name' => $this->name ?: '',
            'class' => $this->class ?: '',
            'visible' => \json_encode($this->visible),
            'orderable' => \json_encode($this->orderable),
            'searchable' => \json_encode($this->searchable),
            'default' => \json_encode($this->default),
            'render' => $this->render ?: \json_encode(false),
            'callback' => $this->callback ?: \json_encode(false),
            'direction' => $this->direction,
        ];
    }

    /**
     * Gets the client side (java script) callback function name used to update the cell.
     */
    public function getCallback(): ?string
    {
        return $this->callback;
    }

    /**
     * Gets the cell class name.
     */
    public function getClass(): string
    {
        return $this->class ?: '';
    }

    /**
     * Gets the sorting direction.
     */
    public function getDirection(): string
    {
        return $this->direction;
    }

    /**
     * Gets either a sprintf compatible format string, a callable function providing rendering conversion or null for default.
     *
     * The callable function, if any, receives the value of the cell to format and the parent row (object or array). The callable function must have the following signature:
     * <pre>
     *     public function format($value, $data): string;
     * </pre>
     *
     * @return string|callable|null
     *
     * @see DataColumn::formatValue()
     */
    public function getFormatter()
    {
        return $this->formatter;
    }

    /**
     * Gets the header (th) class name.
     */
    public function getHeaderClass(): string
    {
        $class = $this->headerClass ?: $this->class ?: '';
        if ($this->visible) {
            if ($this->orderable) {
                $class .= ' sorting';
                if ($this->default) {
                    $class .= '_' . $this->direction;
                }
            } else {
                $class .= ' sorting_disabled';
            }
        }

        return $class;
    }

    /**
     * Gets the mapped field.
     *
     * This property map the name with a list of real field names.
     * The default implementation returns this name as array.
     *
     * @return string[]
     */
    public function getMap(): array
    {
        if (!empty($this->map)) {
            return $this->map;
        }

        return [$this->name];
    }

    /**
     * Gets the field name.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Gets the client side (java script) function name used to render the cell.
     *
     * @return string
     */
    public function getRender(): ?string
    {
        return $this->render;
    }

    /**
     * Gets the title.
     *
     * @return string
     */
    public function getTitle(): ?string
    {
        return $this->title;
    }

    /**
     * Return if this column is the default sorted column.
     */
    public function isDefault(): bool
    {
        return $this->default;
    }

    /**
     * Gets the orderable behavior.
     */
    public function isOrderable(): bool
    {
        return $this->orderable;
    }

    /**
     * Returns a value indicating the cell data must be renderer as is (raw data).
     *
     * @return bool true if raw data
     */
    public function isRawData(): bool
    {
        return $this->rawData;
    }

    /**
     * Gets the searchable behavior.
     */
    public function isSearchable(): bool
    {
        return $this->searchable;
    }

    /**
     * Gets the visibility behavior.
     */
    public function isVisible(): bool
    {
        return $this->visible;
    }

    /**
     * Sets the sorting direction to ascending.
     */
    public function setAscending(): self
    {
        return $this->setDirection(self::SORT_ASC);
    }

    /**
     * Sets function name used to update the cell.
     *
     * @param string $callback
     */
    public function setCallback(?string $callback): self
    {
        $this->callback = $callback;

        return $this;
    }

    /**
     * Sets the cell class name.
     */
    public function setClass(?string $class): self
    {
        $this->class = $class;

        return $this;
    }

    /**
     * Sets if this column is the default sorted column.
     */
    public function setDefault(bool $default): self
    {
        $this->default = $default;

        return $this;
    }

    /**
     * Sets the sorting direction to descending.
     */
    public function setDescending(): self
    {
        return $this->setDirection(self::SORT_DESC);
    }

    /**
     * Sets the sorting direction.
     *
     * @param string $direction One of the "<code>asc</code>" or "<code>desc</code>"' values
     *
     * @see DataColumn::SORT_ASC
     * @see DataColumn::SORT_DESC
     */
    public function setDirection(string $direction): self
    {
        $direction = \strtolower($direction);
        if (self::SORT_ASC === $direction || self::SORT_DESC === $direction) {
            $this->direction = $direction;
        }

        return $this;
    }

    /**
     * Sets either a sprintf compatible format string, a callable function providing rendering conversion or null for default.
     *
     * The callable function, if any, receives the value of the cell to format and the parent row (object or array). The callable function must have the following signature:
     * <pre>
     *     public function format($value, $data): string;
     * </pre>
     *
     * @param string|callable|null $formatter
     *
     * @see DataColumn::formatValue()
     */
    public function setFormatter($formatter): self
    {
        $this->formatter = $formatter;

        return $this;
    }

    /**
     * Sets the header class name.
     *
     * @param string $headerClass the class name or null to use the cell class name
     */
    public function setHeaderClass(?string $headerClass): self
    {
        $this->headerClass = $headerClass;

        return $this;
    }

    /**
     * Sets the mapped field.
     *
     * This property map the name with a list of real field names.
     *
     * @param string[] $map the mapped fields
     */
    public function setMap(array $map): self
    {
        $this->map = $map;

        return $this;
    }

    /**
     * Sets the field name.
     */
    public function setName(string $name): self
    {
        $this->name = $name;

        return $this->updateProperty();
    }

    /**
     * Sets the orderable behavior.
     */
    public function setOrderable(bool $orderable): self
    {
        $this->orderable = $orderable;

        return $this;
    }

    /**
     * Sets a value indicating if the cell data must be renderer as is (raw data).
     *
     * @param bool $rawData true if raw data
     */
    public function setRawData(bool $rawData): self
    {
        $this->rawData = $rawData;

        return $this;
    }

    /**
     * Sets the function name used to render the cell.
     *
     * @param string|null $render the render or null if none
     */
    public function setRender(?string $render): self
    {
        $this->render = $render;

        return $this;
    }

    /**
     * Sets the searchable behavior.
     */
    public function setSearchable(bool $searchable): self
    {
        $this->searchable = $searchable;

        return $this;
    }

    /**
     * Sets the title.
     *
     * @param string $title the title to translate
     */
    public function setTitle(?string $title): self
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Sets the visibility behavior.
     *
     * <b>Note:</b> if visible is <code>false</code>, the class is set to <code>'d-none'</code> if not defined.
     */
    public function setVisible(bool $visible): self
    {
        $this->visible = $visible;

        // update class
        if (!$this->visible && !$this->class) {
            $this->class = 'd-none';
        }

        return $this;
    }

    /**
     * Convert this column to an array.
     *
     * @param mixed  $key    the data column key
     * @param string $search the optional value to search for
     */
    public function toArray($key, ?string $search = null): array
    {
        return [
            'data' => $key,
            'name' => $this->name,
            'search' => self::createSearch($search),
            'orderable' => \json_encode($this->orderable),
            'searchable' => \json_encode($this->searchable),
        ];
    }

    /**
     * Gets the shared property accessor.
     */
    private static function accessor(): PropertyAccessorInterface
    {
        static $accessor;
        if (null === $accessor) {
            $accessor = PropertyAccess::createPropertyAccessor();
        }

        return $accessor;
    }

    /**
     * Update property path for array object.
     */
    private function updateProperty(): self
    {
        if ($this->name) {
            $this->property = \str_replace('.', '].[', '[' . $this->name . ']');
        }

        return $this;
    }
}
