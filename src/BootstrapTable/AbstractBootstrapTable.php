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
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Abstract Bootstrap table.
 *
 * @author Laurent Muller
 */
abstract class AbstractBootstrapTable
{
    /**
     * @var BootstrapColumn[]
     */
    protected ?array $columns = null;

    protected SerializerInterface $serializer;

    /**
     * Constructor.
     */
    public function __construct(SerializerInterface $serializer)
    {
        $this->serializer = $serializer;
    }

    /**
     * Gets the column definitions.
     *
     * @return BootstrapColumn[]
     */
    public function getColumns(): array
    {
        if (null === $this->columns) {
            $this->columns = $this->createColumns();
        }

        return $this->columns;
    }

    /**
     * Gets the default column.
     */
    public function getDefaultColumn(): ?BootstrapColumn
    {
        $columns = $this->getColumns();
        foreach ($columns as $column) {
            if ($column->isDefault()) {
                return $column;
            }
        }
        foreach ($columns as $column) {
            if ($column->isVisible()) {
                return $column;
            }
        }

        return null;
    }

    /**
     * Map the given object to array.
     *
     * @param mixed            $objectOrArray the object to map
     * @param PropertyAccessor $accessor      the property accessor to get the object values
     *
     * @return string[] the mapped object
     */
    public function mapValues($objectOrArray, PropertyAccessor $accessor): array
    {
        $result = [];
        $columns = $this->getColumns();
        foreach ($columns as $column) {
            $result[$column->getField()] = $column->mapValue($objectOrArray, $accessor);
        }

        return $result;
    }

    /**
     * Load the columns definitions.
     *
     * @return BootstrapColumn[] the columns definitions
     */
    abstract protected function createColumns(): array;

    /**
     * Deserialize the column definitions.
     *
     * @param string $path   the file path
     * @param string $format the format used to deserialize the content into an array
     *
     * @return BootstrapColumn[] the columns definitions
     */
    protected function deserializeColumns(string $path, $format = 'json'): array
    {
        $data = \file_get_contents($path);
        $type = \sprintf('%s[]', BootstrapColumn::class);

        return $this->serializer->deserialize($data, $type, $format);
    }
}
