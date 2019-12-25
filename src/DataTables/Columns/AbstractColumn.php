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

namespace App\DataTables\Columns;

use App\DataTables\Tables\AbstractDataTable;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Abstract data column.
 *
 * @author Laurent Muller
 */
abstract class AbstractColumn
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
     * The field name.
     *
     * @var string
     */
    protected $name;

    /**
     * The additional options.
     *
     * @var array
     */
    protected $options;

    /**
     * The parent table.
     *
     * @var AbstractDataTable
     */
    protected $table;

    /**
     * @var array<string, OptionsResolver>
     */
    private static $resolversByClass = [];

    /**
     * Contructor.
     *
     * @param AbstractDataTable $table   the parent table
     * @param string            $name    the field name
     * @param array             $options the additional options
     */
    public function __construct(AbstractDataTable $table, string $name, array $options = [])
    {
        $this->table = $table;
        $this->name = $name;
        $this->options = $options;

        $class = \get_class($this);
        if (!isset(self::$resolversByClass[$class])) {
            self::$resolversByClass[$class] = new OptionsResolver();
            $this->configureOptions(self::$resolversByClass[$class]);
        }
        $this->options = self::$resolversByClass[$class]->resolve($options);
    }

    public function __get(string $name)
    {
        if (\array_key_exists($name, $this->options)) {
            return $this->options[$name];
        }

        return null;
    }

    public function __isset(string $name): bool
    {
        return isset($this->options[$name]);
    }

    public function __set(string $name, $value): void
    {
        $this->options[$name] = $value;
    }

    public function __unset(string $name): void
    {
        unset($this->options[$name]);
    }

    /**
     * Format the given value.
     *
     * @param mixed $value
     */
    public function formatValue($value): string
    {
        return null === $value ? '' : (string) $value;
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
     * Gets the parent's table.
     */
    public function getDataTable(): AbstractDataTable
    {
        return $this->table;
    }

    /**
     * Gets the sorting direction.
     *
     * The default value is ascending ('asc').
     */
    public function getDirection(): string
    {
        return $this->direction ?? self::SORT_ASC;
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
     * Gets a value indicating if this column is the default sorted column.
     */
    public function isDefault(): bool
    {
        return $this->default ?? false;
    }

    /**
     * Gets the orderable behavior.
     */
    public function isOrderable(): bool
    {
        return $this->orderable ?? true;
    }

    /**
     * Gets the searchable behavior.
     *
     * The default value is true.
     */
    public function isSearchable(): bool
    {
        return $this->searchable ?? true;
    }

    /**
     * Gets the visibility behavior.
     *
     * The default value is true.
     */
    public function isVisible(): bool
    {
        return $this->visible ?? true;
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
     * Sets a value indicating if this column is the default sorted column.
     */
    public function setDefault(bool $default): self
    {
        $this->default = $default;

        return $this;
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
        switch ($direction) {
            case self::SORT_ASC:
            case self::SORT_DESC:
                $this->direction = $direction;
                break;
        }

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
     * Sets the orderable behavior.
     */
    public function setOrderable(bool $orderable): self
    {
        $this->orderable = $orderable;

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
     * Sets the visibility behavior.
     */
    public function setVisible(bool $visible): self
    {
        $this->visible = $visible;
        if (!$visible) {
        }

        return $this;
    }

    /**
     * @return self
     */
    protected function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setDefaults([
                'label' => null,
                'data' => null,
                'field' => null,
                'propertyPath' => null,
                'visible' => true,
                'orderable' => null,
                'orderField' => null,
                'searchable' => null,
                'globalSearchable' => null,
                'filter' => null,
                'className' => null,
                'headerClassName' => null,
                'render' => null,
                'operator' => '=',
            ])
            ->setAllowedTypes('label', ['null', 'string'])
            ->setAllowedTypes('data', ['null', 'string', 'callable'])
            ->setAllowedTypes('field', ['null', 'string'])
            ->setAllowedTypes('propertyPath', ['null', 'string'])
            ->setAllowedTypes('visible', 'boolean')
            ->setAllowedTypes('orderable', ['null', 'boolean'])
            ->setAllowedTypes('orderField', ['null', 'string'])
            ->setAllowedTypes('searchable', ['null', 'boolean'])
            ->setAllowedTypes('globalSearchable', ['null', 'boolean'])
            ->setAllowedTypes('filter', ['null', 'array'])
            ->setAllowedTypes('className', ['null', 'string'])
            ->setAllowedTypes('headerClassName', ['null', 'string'])
            ->setAllowedTypes('render', ['null', 'string', 'callable'])
            ->setAllowedTypes('operator', ['string']);

        return $this;
    }
}
