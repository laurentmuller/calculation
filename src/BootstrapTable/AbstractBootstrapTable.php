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

use App\Util\Utils;
use Symfony\Component\HttpFoundation\Request;
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
     * The default page size.
     */
    public const PAGE_SIZE = 20;

    /**
     * The card parameter name.
     */
    public const PARAM_CARD = 'card';

    /**
     * The identifier parameter name.
     */
    public const PARAM_ID = 'id';

    /**
     * The limit parameter name.
     */
    public const PARAM_LIMIT = 'limit';

    /**
     * The offset parameter name.
     */
    public const PARAM_OFFSET = 'offset';

    /**
     * The order parameter name.
     */
    public const PARAM_ORDER = 'order';

    /**
     * The search parameter name.
     */
    public const PARAM_SEARCH = 'search';

    /**
     * The sort parameter name.
     */
    public const PARAM_SORT = 'sort';

    /**
     * The column definitions.
     *
     * @var BootstrapColumn[]
     */
    protected ?array $columns = null;

    /**
     * The serializer used to load definition.
     */
    protected SerializerInterface $serializer;

    /**
     * The session prefix.
     */
    private ?string $prefix = null;

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
     * Gets the request parameter value.
     *
     * @param Request $request the request to get value from
     * @param string  $name    the parameter name
     * @param mixed   $default the default value if not found
     *
     * @return mixed the parameter value
     */
    public function getRequestValue(Request $request, string $name, $default = null)
    {
        $key = $this->getSessionKey($name);
        $session = $request->hasSession() ? $request->getSession() : null;

        if ($session) {
            $default = $session->get($key, $default);
        }

        $value = $request->get($name, $default);

        if ($session) {
            $session->set($key, $value);
        }

        return $value;
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
     * Save the request parameter value to the session.
     *
     * @param Request $request the request to get value from
     * @param string  $name    the parameter name
     * @param mixed   $default the default value if not found
     *
     * @return bool true if the parameter value is saved to the session; false otherwise
     */
    public function saveRequestValue(Request $request, string $name, $default = null): bool
    {
        if ($request->hasSession()) {
            $session = $request->getSession();
            $key = $this->getSessionKey($name);
            $default = $session->get($key, $default);
            $value = $request->get($name, $default);
            $session->set($key, $value);

            return  true;
        }

        return false;
    }

    /**
     * Create the columns definitions.
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

    /**
     * Gets the session key for the given name.
     *
     * @param string $name the parameter name
     */
    protected function getSessionKey(string $name): string
    {
        if (null === $this->prefix) {
            $this->prefix = Utils::getShortName($this);
        }

        return "{$this->prefix}.$name";
    }
}
