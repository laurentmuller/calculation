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

use App\Entity\AbstractEntity;
use App\Traits\MathTrait;
use App\Util\FormatUtils;
use App\Util\Utils;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Exception\UnexpectedValueException;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

/**
 * Abstract Bootstrap table.
 *
 * @author Laurent Muller
 */
abstract class AbstractBootstrapTable
{
    use MathTrait;

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
     * The session prefix.
     */
    private ?string $prefix = null;

    public function formatAmount(float $value): string
    {
        return FormatUtils::formatAmount($value);
    }

    public function formatCountable(\Countable $value): string
    {
        return $this->formatInt($value->count());
    }

    public function formatDate(\DateTimeInterface $value): string
    {
        return FormatUtils::formatDate($value);
    }

    public function formatInt(int $value): string
    {
        return FormatUtils::formatInt($value);
    }

    public function formatPercent(float $value): string
    {
        return FormatUtils::formatPercent($value);
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
     * Gets the entity class name.
     */
    abstract public function getEntityClassName(): string;

    /**
     * Handles the given request and returns the result parameters.
     */
    abstract public function handleRequest(Request $request): array;

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
     * Deserialize the column definitions from the given JSON file.
     *
     * @param string $path the JSON file path
     *
     * @return BootstrapColumn[] the columns definitions
     *
     * @throws \Symfony\Component\Serializer\Exception\UnexpectedValueException if the file path can not be found or
     *                                                                          if the file can not be deserialized
     */
    protected function deserializeColumns(string $path): array
    {
        //file?
        if (!\file_exists($path) || !\is_file($path)) {
            throw new UnexpectedValueException("The file '$path' can not be found.");
        }

        $data = \file_get_contents($path);
        $normalizers = [new ArrayDenormalizer(), new ObjectNormalizer()];
        $serializer = new Serializer($normalizers, [new JsonEncoder()]);
        $type = \sprintf('%s[]', BootstrapColumn::class);

        /** var BootstrapColumn[] $columns */
        $columns = $serializer->deserialize($data, $type, 'json');

        /** var BootstrapColumn $column */
        foreach ($columns as $column) {
            if (null !== $column->getFieldFormatter()) {
                $column->setFieldFormatter([$this, $column->getFieldFormatter()]);
            }
        }

        return $columns;
    }

    /**
     * Gets the default column.
     */
    protected function getDefaultColumn(): ?BootstrapColumn
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
     * Gets the display card parameter.
     */
    protected function getParamCard(Request $request): bool
    {
        $value = $this->getRequestValue($request, self::PARAM_CARD, false);

        return (bool) \filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Gets the selected identifier parameter.
     */
    protected function getParamId(Request $request): int
    {
        return (int) $request->get(self::PARAM_ID, 0);
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
    protected function getRequestValue(Request $request, string $name, $default = null)
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

    /**
     * Maps the given entities.
     *
     * @param AbstractEntity[] $entities the entities to map
     *
     * @return array the mapped entities
     */
    protected function mapEntities(array $entities): array
    {
        $columns = $this->getColumns();
        $accessor = PropertyAccess::createPropertyAccessor();

        return \array_map(function (AbstractEntity $entity) use ($columns, $accessor) {
            return $this->mapValues($entity, $columns, $accessor);
        }, $entities);
    }

    /**
     * Map the given object to an array where the keys are the column field.
     *
     * @param mixed             $objectOrArray the object to map
     * @param BootstrapColumn[] $columns       the column definitions
     * @param PropertyAccessor  $accessor      the property accessor to get the object values
     *
     * @return string[] the mapped object
     */
    protected function mapValues($objectOrArray, array $columns, PropertyAccessor $accessor): array
    {
        $callback = static function (array $result, BootstrapColumn $column) use ($objectOrArray, $accessor) {
            $result[$column->getField()] = $column->mapValue($objectOrArray, $accessor);

            return $result;
        };

        return \array_reduce($columns, $callback, []);
    }
}
