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

use App\Entity\Log;
use App\Enums\SortMode;
use App\Utils\FileUtils;
use App\Utils\FormatUtils;
use Symfony\Component\PropertyAccess\Exception\ExceptionInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * Service to load columns from a JSON file.
 *
 * @phpstan-type ColumnType = array{order?: string, ...}
 */
class ColumnFactory
{
    /** The mapping of class names to their respective classes. */
    private const array CLASSES_MAPPING = [
        'FormatUtils' => FormatUtils::class,
        'Log' => Log::class,
    ];

    /** The property name of the field formatter. */
    private const string FIELD_FORMATTER = 'fieldFormatter';

    /** The property name of the field order. */
    private const string FIELD_ORDER = 'order';

    /** The shared property accessor to map JSON columns or values. */
    private static ?PropertyAccessorInterface $accessor = null;

    /**
     * Create the action column.
     */
    public function createColumnAction(): Column
    {
        return Column::instance('id')
            ->withAlias('action')
            ->withSortable(false)
            ->withSearchable(false)
            ->withCellFormatter('renderActions')
            ->withClass('actions rowlink-skip d-print-none');
    }

    /**
     * Creates columns from the given JSON file definitions.
     *
     * @param AbstractTable $parent the table owner
     * @param string        $path   the path to the JSON file definitions
     *
     * @return non-empty-array<int, Column> the columns
     *
     * @throws \InvalidArgumentException if the given file contains no definition, or if columns cannot be created
     */
    public function load(AbstractTable $parent, string $path): array
    {
        /** @var ColumnType[] $definitions */
        $definitions = FileUtils::decodeJson($path);
        if ([] === $definitions) {
            throw new \InvalidArgumentException(\sprintf('The file "%s" contains no definition.', $path));
        }

        return \array_map(
            fn (array $definition): Column => $this->mapDefinition($parent, $definition),
            $definitions
        );
    }

    private function createCallable(AbstractTable $parent, string $value): array
    {
        $values = \explode('::', $value, 2);
        $owner = self::CLASSES_MAPPING[$values[0]] ?? null;
        $callable = null !== $owner ? [$owner, $values[1]] : [$parent, $value];
        if (!\is_callable($callable)) {
            throw new \ValueError(\sprintf('Invalid callable: "%s".', $value));
        }

        return $callable;
    }

    private function getAccessor(): PropertyAccessorInterface
    {
        return self::$accessor ??= PropertyAccess::createPropertyAccessor();
    }

    /**
     * @param ColumnType $definition
     *
     * @throws \InvalidArgumentException if a property cannot be set
     */
    private function mapDefinition(AbstractTable $parent, array $definition): Column
    {
        $column = Column::instance();
        $accessor = $this->getAccessor();
        foreach ($definition as $key => $value) {
            try {
                $value = match ($key) {
                    self::FIELD_ORDER => SortMode::from($value),
                    self::FIELD_FORMATTER => $this->createCallable($parent, $value),
                    default => $value
                };
                $accessor->setValue($column, $key, $value);
            } catch (\ValueError|ExceptionInterface $e) {
                throw new \InvalidArgumentException(\sprintf('Unable set the property "%s".', $key), $e->getCode(), $e);
            }
        }

        return $column;
    }
}
