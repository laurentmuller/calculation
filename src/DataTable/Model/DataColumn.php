<?php
/*
 * This file is part of the Calculation package.
 *
 * Copyright (c) 2019 bibi.nu. All rights reserved.
 *
 * This computer code is protected by copyright law and international
 * treaties. Unauthorised reproduction or distribution of this code, or
 * any portion of it, may result in severe civil and criminal penalties,
 * and will be prosecuted to the maximum extent possible under the law.
 */

declare(strict_types=1);

namespace App\DataTable\Model;

use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Validator\Constraints as Assert;

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
     * The property accessor.
     *
     * @var PropertyAccessorInterface
     */
    protected $accessor;

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
    protected $className;

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
     *
     * @Assert\Choice(choices={"asc", "desc"}, strict=true)
     */
    protected $direction = self::SORT_ASC;

    /**
     * The translation domain.
     *
     * @var string
     */
    protected $domain;

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
    protected $headerClassName;

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
     *
     * @Assert\NotBlank
     */
    protected $name;

    /**
     * The null value.
     *
     * @var string
     */
    protected $nullValue = '';

    /**
     * The additional options.
     *
     * @var array
     */
    protected $options;

    /**
     * The orderable behavior.
     *
     * @var bool
     */
    protected $orderable = true;

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
     * @param string $name    the field name
     * @param array  $options the additional options
     */
    public function __construct(string $name, array $options = [])
    {
        $this->name = $name;
        $this->options = $options;
    }

    /**
     * Creates a new instance for drop-down menu actions.
     *
     * @param string|callable|null $formatter the column formatter
     * @param string               $name      the field name
     */
    public static function actions($formatter, string $name = 'id'): self
    {
        return self::instance($name)
            ->setClassName('actions d-print-none')
            ->setTitle('common.empty')
            ->setFormatter($formatter)
            ->setSearchable(false)
            ->setOrderable(false)
            ->setRawData(true);
    }

    /**
     * Add a class name.
     *
     * @param string $className the class name to add
     */
    public function addClassName(?string $className): self
    {
        $names = $this->className ?: '';
        if (false === \stripos($names, $className)) {
            $names = \trim($names . ' ' . $className);
        }

        return $this->setClassName($names);
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
     * Creates a new instance with the 'text-currency' class.
     *
     * @param string $name the field name
     */
    public static function currency(string $name): self
    {
        return self::instance($name)->setClassName('text-currency');
    }

    /**
     * Creates a new instance with the 'text-date' class.
     *
     * @param string $name the field name
     */
    public static function date(string $name): self
    {
        return self::instance($name)->setClassName('text-date');
    }

    /**
     * Creates a new instance with the 'text-date-time' class.
     *
     * @param string $name the field name
     */
    public static function dateTime(string $name): self
    {
        return self::instance($name)->setClassName('text-date-time');
    }

    /**
     * Converts the given value to a string.
     *
     * This implementation do the following:
     * <ul>
     * <li>If the <code>$value</code> parameter is <code>null</code>, uses this <code>getNullValue()</code> function.</li>
     * <li>If the formatter is a string, format the <code>$value</code> parameter using the <code>sprintf</code> function.</li>
     * <li>If the formatter is callable, convert the <code>$value</code> using the <code>call_user_func()</code> function with the <code>$value</code> and the <code>$data</code> as parameters.</li>
     * <li>Converts the <code>$value</code> parameter as string.</li>
     * </ul>
     *
     * @param mixed        $value the value to convert
     * @param object|array $data  the parent object or array
     *
     * @return string the value as string
     *
     * @see DataColumn::getFormatter()
     * @see DataColumn::getNullValue()
     * @see DataColumn::getCellValue()
     */
    public function formatValue($value, $data): string
    {
        if (null === $value) {
            return $this->getNullValue();
        }
        if (\is_string($this->formatter)) {
            return \sprintf($this->formatter, $value);
        }
        if (\is_callable($this->formatter)) {
            return \call_user_func($this->formatter, $value, $data);
        }

        return (string) $value;
    }

    /**
     * Convert this column to an array of attributes.
     */
    public function getAttributes(): array
    {
        return [
            'name' => $this->name,
            'class-name' => $this->getCellClassName(),
            'visible' => \json_encode($this->isVisible()),
            'orderable' => \json_encode($this->isOrderable()),
            'searchable' => \json_encode($this->isSearchable()),
            'is-default' => \json_encode($this->isDefault()),
            'direction' => $this->direction,
            'created-cell' => $this->callback ?: \json_encode(false),
            'render' => $this->render ?: \json_encode(false),
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
     * Gets the cell (td) class name.
     */
    public function getCellClassName(): string
    {
        $className = $this->className ?: '';
        if ($this->visible) {
            return \trim($className . ' cursor-pointer');
        }

        return $className;
    }

    /**
     * Gets the cell value for the given data.
     *
     * @param object|array $data the object or array to traverse
     *
     * @return mixed the cell value
     *
     * @see DataColumn::getAccessor()
     * @see DataColumn::formatValue()
     */
    public function getCellValue($data)
    {
        $property = $this->name;
        if (\is_array($data)) {
            $property = '[' . $property . ']';
            $property = \str_replace('.', '].[', $property);
        }

        return $this->getAccessor()->getValue($data, $property);
    }

    /**
     * Gets the class name.
     *
     * @return string
     */
    public function getClassName(): ?string
    {
        return $this->className;
    }

    /**
     * Gets the sorting direction.
     */
    public function getDirection(): string
    {
        return $this->direction;
    }

    /**
     * Gets the translation domain.
     *
     * @return string
     */
    public function getDomain(): ?string
    {
        return $this->domain;
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
    public function getHeaderClassName(): string
    {
        $className = $this->headerClassName ?: $this->className ?: '';
        if ($this->visible) {
            if ($this->orderable) {
                $className .= ' cursor-pointer sorting';
                if ($this->default) {
                    $className .= '_' . $this->direction;
                }
            } else {
                $className .= ' sorting_disabled';
            }
        }

        return \trim($className);
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
        if ($this->map && \count($this->map)) {
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
     * Gets the string to return when value is <code>null</code>.
     *
     * @see DataColumn::formatValue()
     */
    public function getNullValue(): string
    {
        return $this->nullValue;
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
     * Creates a new instance with the visible property set to false.
     *
     * @param string $name the field name
     */
    public static function hidden(string $name): self
    {
        return self::instance($name)->setVisible(false);
    }

    /**
     * Creates a new instance with the identifier 'text-id' class.
     *
     * @param string $name the field name
     */
    public static function identifier(string $name): self
    {
        return self::instance($name)->setClassName('text-id');
    }

    /**
     * Creates a new instance.
     *
     * @param string $name the field name
     */
    public static function instance(string $name): self
    {
        return new self($name);
    }

    /**
     * Return if this is the default sorted column.
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
     * Creates a new instance with the 'text-percent' class.
     *
     * @param string $name the field name
     */
    public static function percent(string $name): self
    {
        return self::instance($name)->setClassName('text-percent');
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
     * Sets the class name.
     *
     * @param string $className
     */
    public function setClassName(?string $className): self
    {
        $this->className = $className;

        return $this;
    }

    /**
     * Sets this is the default sorted column.
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
     * Sets the translation domain.
     *
     * @param string $domain
     */
    public function setDomain(?string $domain): self
    {
        $this->domain = $domain;

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
     * @param string $headerClassName the class name or null to use the cell class name
     */
    public function setHeaderClassName(?string $headerClassName): self
    {
        $this->headerClassName = $headerClassName;

        return $this;
    }

    /**
     * Sets the mapped field.
     *
     * This property map the name with a list of real field names.
     *
     * @param string[] $map
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

        return $this;
    }

    /**
     * Sets the string to display when the value is <code>null</code>.
     *
     * @see DataColumn::formatValue()
     */
    public function setNullValue(string $nullValue): self
    {
        $this->nullValue = $nullValue;

        return $this;
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
     * Sets a value indicating the cell data must be renderer as is (raw data).
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
     * @param string $title  the title to translate
     * @param string $domain the translation domain
     */
    public function setTitle(?string $title, ?string $domain = null): self
    {
        $this->title = $title;
        if ($domain) {
            $this->domain = $domain;
        }

        return $this;
    }

    /**
     * Sets the visibility behavior.
     *
     * If the visible parameter is FALSE, the orderable and searchable are also set to FALSE and the class name
     * is set to 'd-none'.
     */
    public function setVisible(bool $visible): self
    {
        $this->visible = $visible;
        if (!$visible) {
            return $this->setOrderable(false)
                ->setSearchable(false)
                ->setClassName('d-none');
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
        return  [
            'data' => $key,
            'name' => $this->name,
            'search' => self::createSearch($search),
            'orderable' => \json_encode($this->orderable),
            'searchable' => \json_encode($this->searchable),
        ];
    }

    /**
     * Creates a new instance with the 'text-unit' class.
     *
     * @param string $name the field name
     */
    public static function unit(string $name): self
    {
        return self::instance($name)->setClassName('text-unit');
    }

    /**
     * Gets the property accessor.
     */
    protected function getAccessor(): PropertyAccessorInterface
    {
        if (null === $this->accessor) {
            $this->accessor = PropertyAccess::createPropertyAccessor();
        }

        return $this->accessor;
    }
}
