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

use App\Util\FileUtils;
use Symfony\Component\PropertyAccess\Exception\AccessException;
use Symfony\Component\PropertyAccess\Exception\UnexpectedTypeException;
use Symfony\Component\PropertyAccess\PropertyAccess;

/**
 * Factory to create instances of {@link DataColumn}.
 *
 * @author Laurent Muller
 */
class DataColumnFactory
{
    /**
     * Creates a new instance for drop-down menu actions.
     *
     * @param string|callable|null $formatter the column formatter
     * @param string               $name      the field name
     */
    public static function actions($formatter, string $name = 'id'): DataColumn
    {
        return self::instance($name, 'actions rowlink-skip d-print-none')
            ->setTitle('common.empty')
            ->setFormatter($formatter)
            ->setSearchable(false)
            ->setOrderable(false)
            ->setRawData(true);
    }

    /**
     * Creates a new instance with the 'text-currency' class.
     *
     * @param string $name the field name
     */
    public static function currency(string $name): DataColumn
    {
        return self::instance($name, 'text-currency');
    }

    /**
     * Creates a new instance with the 'text-date' class.
     *
     * @param string $name the field name
     */
    public static function date(string $name): DataColumn
    {
        return self::instance($name, 'text-date');
    }

    /**
     * Creates a new instance with the 'text-date-time' class.
     *
     * @param string $name the field name
     */
    public static function dateTime(string $name): DataColumn
    {
        return self::instance($name, 'text-date-time');
    }

    /**
     * Creates data columns from the given JSON definitions.
     *
     * @param AbstractDataTable $parent the datatable owner
     * @param string            $path   the path to the JSON file definitions
     *
     * @return DataColumn[] the column definitions
     *
     * @throws \InvalidArgumentException if the definitions can not be parsed
     */
    public static function fromJson(AbstractDataTable $parent, string $path): array
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
        return \array_map(function (array $definition) use ($parent, $accessor): DataColumn {
            $column = self::instance();
            /** @var string $key */
            foreach ($definition as $key => $value) {
                // special case for the formatter
                if ('formatter' === $key) {
                    $value = [$parent, $value];
                }

                try {
                    $accessor->setValue($column, $key, $value);
                } catch (AccessException|UnexpectedTypeException $e) {
                    $message = "Cannot set the property '$key'.";
                    throw new \InvalidArgumentException($message, 0, $e);
                }
            }

            return $column;
        }, $definitions);
    }

    /**
     * Creates a new instance with the visible, searchable and orderable properties set to false.
     *
     * @param string $name the field name
     */
    public static function hidden(string $name): DataColumn
    {
        return self::instance($name, 'd-none')
            ->setSearchable(false)
            ->setOrderable(false)
            ->setVisible(false);
    }

    /**
     * Creates a new instance for the hidden identifier.
     *
     * @param string $name the field name
     */
    public static function hiddenId(string $name = 'id'): DataColumn
    {
        return self::hidden($name);
    }

    /**
     * Creates a new instance with the identifier 'text-id' and 'text-border' class.
     *
     * @param string $name the field name
     */
    public static function identifier(string $name): DataColumn
    {
        return self::instance($name, 'text-id text-border');
    }

    /**
     * Creates a new instance.
     *
     * @param string $name  the field name
     * @param string $class the cell class name
     */
    public static function instance(string $name = null, string $class = null): DataColumn
    {
        return new DataColumn($name, $class);
    }

    /**
     * Creates a new instance with the 'text-percent' class.
     *
     * @param string $name the field name
     */
    public static function percent(string $name): DataColumn
    {
        return self::instance($name, 'text-percent');
    }

    /**
     * Creates a new instance with the 'text-unit' class.
     *
     * @param string $name the field name
     */
    public static function unit(string $name): DataColumn
    {
        return self::instance($name, 'text-unit');
    }
}
