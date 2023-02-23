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

namespace App\Util;

use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

use function Symfony\Component\String\u;

use Symfony\Component\String\UnicodeString;

/**
 * Utility class for strings and sort.
 */
final class Utils
{
    // prevent instance creation
    private function __construct()
    {
        // no-op
    }

    /**
     * Iteratively reduce the array to a single value using a callback function.
     *
     * The callable function must be of type <code>function($carry, $key, $value)</code>.
     *
     * @param array    $array    the input array
     * @param callable $callback the callback function
     * @param mixed    $initial  the optional initial value. It will be used at the beginning of the process, or as a final result in case the array is empty
     *
     * @return mixed the resulting value
     */
    public static function arrayReduceKey(array $array, callable $callback, mixed $initial = null): mixed
    {
        return \array_reduce(\array_keys($array), fn (mixed $carry, string|int $key): mixed => $callback($carry, $key, $array[$key]), $initial);
    }

    /**
     * Converts the given string to ASCII transliteration.
     */
    public static function ascii(string $value): string
    {
        return u($value)->ascii()->toString();
    }

    /**
     * Capitalizes a string.
     *
     * The first character will be uppercase, all others lowercase:
     * <pre>
     * 'my first Car' => 'My first car'
     * </pre>
     *
     * @param string $string the string to capitalize
     *
     * @return string the capitalized string
     */
    public static function capitalize(string $string): string
    {
        return u($string)->lower()->title()->toString();
    }

    /**
     * Compare 2 values.
     *
     * @param object|array              $a         the first object to get field value from
     * @param object|array              $b         the second object to get field value from
     * @param string                    $field     the field name to get value for
     * @param PropertyAccessorInterface $accessor  the property accessor to get values
     * @param bool                      $ascending true to sort ascending, false to sort descending
     *
     * @return int -1 if the first value is less than the second value;
     *             1 if the second value is greater than the first value and
     *             0 if both values are equal.
     *             If $ascending is false, the result is reversed.
     *
     * @throws \Symfony\Component\PropertyAccess\Exception\InvalidArgumentException If the property path is invalid
     * @throws \Symfony\Component\PropertyAccess\Exception\AccessException          If a property/index does not exist or is not public
     * @throws \Symfony\Component\PropertyAccess\Exception\UnexpectedTypeException  If a value within the path is neither object
     */
    public static function compare(object|array $a, object|array $b, string $field, PropertyAccessorInterface $accessor, bool $ascending = true): int
    {
        /** @var mixed $valueA */
        $valueA = $accessor->getValue($a, $field);
        /** @var mixed $valueB */
        $valueB = $accessor->getValue($b, $field);
        $result = \is_string($valueA) && \is_string($valueB) ? \strnatcasecmp($valueA, $valueB) : $valueA <=> $valueB;

        return $ascending ? $result : -$result;
    }

    /**
     * Tests if a substring is contained within a string.
     *
     * <b>NB:</b> If the needle is empty, this function return false.
     *
     * @param string $haystack    the string to search in
     * @param string $needle      the string to search for
     * @param bool   $ignore_case true for case-insensitive; false for case-sensitive
     *
     * @return bool true if substring is contained within a string
     */
    public static function contains(string $haystack, string $needle, bool $ignore_case = false): bool
    {
        return self::getString($haystack, $ignore_case)->containsAny($needle);
    }

    /**
     * Tests if a string ends within a substring.
     *
     * <b>NB:</b> If the needle is empty, this function return false.
     *
     * @param string $haystack    the string to search in
     * @param string $needle      the string to search for
     * @param bool   $ignore_case true for case-insensitive; false for case-sensitive
     *
     * @return bool true if ends with substring
     */
    public static function endWith(string $haystack, string $needle, bool $ignore_case = false): bool
    {
        return self::getString($haystack, $ignore_case)->endsWith($needle);
    }

    /**
     * Returns a value indicating if the given strings are equal ignoring case consideration.
     *
     * @param string $string1 the first string to compare
     * @param string $string2 the second string to compare
     *
     * @return bool true if equal; false otherwise
     */
    public static function equalIgnoreCase(string $string1, string $string2): bool
    {
        return 0 === \strcasecmp($string1, $string2);
    }

    /**
     * Returns a parsable string representation of a variable.
     *
     * @param mixed $expression the variable to export
     *
     * @return string the variable representation
     */
    public static function exportVar(mixed $expression): string
    {
        try {
            $export = \var_export($expression, true);

            $searches = [
                '\\\\' => '\\',
                 ',' => '',
            ];
            $export = \str_replace(\array_keys($searches), \array_values($searches), $export);

            $patterns = [
                "/array \(/" => '[',
                "/^([ ]*)\)(,?)$/m" => '$1]$2',
                "/=>[ ]?\n[ ]+\[/" => '=> [',
                "/([ ]*)(\'[^\']+\') => ([\[\'])/" => '$1$2 => $3',
            ];

            return \preg_replace(\array_keys($patterns), \array_values($patterns), $export);
        } catch (\Exception) {
            return (string) $expression;
        }
    }

    /**
     * Create a property accessor.
     */
    public static function getAccessor(): PropertyAccessorInterface
    {
        /** @psalm-var PropertyAccessorInterface|null $accessor */
        static $accessor = null;
        if (null === $accessor) {
            $accessor = PropertyAccess::createPropertyAccessor();
        }

        return $accessor;
    }

    /**
     * Gets the context for the given exception,.
     *
     * @param \Throwable $e the exception to get the context for
     *
     * @return array an array with the message, class, code, file, line and trace properties
     */
    public static function getExceptionContext(\Throwable $e): array
    {
        return [
            'message' => $e->getMessage(),
            'code' => $e->getCode(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'class' => self::getShortName($e),
            'trace' => $e->getTraceAsString(),
        ];
    }

    /**
     * Gets the short class name of the given variable.
     *
     * @param object|string $objectOrClass either a string containing the name of
     *                                     the class to reflect, or an object
     *
     * @return string the short name or null if the variable is null
     *
     * @psalm-template T of object
     *
     * @psalm-param class-string<T>|T $objectOrClass
     */
    public static function getShortName(object|string $objectOrClass): string
    {
        try {
            return (new \ReflectionClass($objectOrClass))->getShortName();
        } catch (\ReflectionException $e) {
            $type = \is_object($objectOrClass) ? \get_debug_type($objectOrClass) : $objectOrClass;
            $message = \sprintf("Unable to get short name for '%s'.", $type);
            throw new \RuntimeException($message, $e->getCode(), $e);
        }
    }

    /**
     * Groups an array by the given key. Any additional keys will be used for grouping the next set of sub-arrays.
     *
     * @psalm-param array<array-key, object|array> $array
     * @psalm-param string|int|(callable(mixed):string|int) $key
     */
    public static function groupBy(array $array, string|int|callable $key): array
    {
        $result = [];

        /** @psalm-param object|array $value */
        foreach ($array as $value) {
            if (\is_callable($key)) {
                $entry = $key($value);
            } elseif (\is_object($value)) {
                /** @psalm-var string|int $entry */
                $entry = $value->{$key};
            } else { // array
                /** @psalm-var string|int $entry */
                $entry = $value[$key];
            }
            $result[$entry][] = $value;
        }

        // Recursively build a nested grouping if more parameters are supplied
        // Each grouped array value is grouped according to the next sequential key
        if (\func_num_args() > 2) {
            $args = \func_get_args();
            $callback = [__CLASS__, __FUNCTION__];
            /** @psalm-param string|int $groupKey */
            foreach ($result as $groupKey => $value) {
                $params = \array_merge([$value], \array_slice($args, 2, \func_num_args()));
                /** @psalm-var array|object $value */
                $value = \call_user_func_array($callback, $params);
                $result[$groupKey] = $value;
            }
        }

        return $result;
    }

    /**
     * Returns if the given variable is a string and not empty.
     *
     * @param mixed $var the variable to be tested
     *
     * @return bool true if not empty string
     */
    public static function isString(mixed $var): bool
    {
        return \is_string($var) && '' !== $var;
    }

    /**
     * Sorts an array for the given field.
     *
     * @param array<object|array> $array     the array to sort
     * @param string              $field     the field name to get values for
     * @param bool                $ascending true to sort in ascending, false to sort in descending
     */
    public static function sortField(array &$array, string $field, bool $ascending = true): void
    {
        $accessor = self::getAccessor();
        \uasort($array, fn (object|array $a, object|array $b): int => self::compare($a, $b, $field, $accessor, $ascending));
    }

    /**
     * Sorts an array for the given fields.
     *
     * @param array<object|array> $array  the array to sort
     * @param array<string, bool> $fields the array where the key is field name to sort and the value is the ascending state
     *                                    (true to sort in ascending, false to sort in descending)
     */
    public static function sortFields(array &$array, array $fields): void
    {
        $count = \count($fields);
        if (1 === $count) {
            $field = (string) \array_key_first($fields);
            $ascending = $fields[$field];
            self::sortField($array, $field, $ascending);
        } elseif ($count > 1) {
            $accessor = self::getAccessor();
            \uasort($array, function (object|array $a, object|array $b) use ($accessor, $fields): int {
                foreach ($fields as $field => $ascending) {
                    if (0 !== $result = self::compare($a, $b, $field, $accessor, $ascending)) {
                        return $result;
                    }
                }

                return 0;
            });
        }
    }

    /**
     * Tests if a string starts within a substring.
     *
     * <b>NB:</b> If the needle is empty, this function return false.
     *
     * @param string $haystack    the string to search in
     * @param string $needle      the string to search for
     * @param bool   $ignore_case true for case-insensitive; false for case-sensitive
     *
     * @return bool true if starts with substring
     */
    public static function startWith(string $haystack, string $needle, bool $ignore_case = false): bool
    {
        return self::getString($haystack, $ignore_case)->startsWith($needle);
    }

    /**
     * Ensure that the given variable is a float.
     *
     * @param mixed $var the variable to cast
     *
     * @return float the variable as float
     */
    public static function toFloat(mixed $var): float
    {
        return \is_float($var) ? $var : (float) $var;
    }

    /**
     * Ensure that the given variable is an integer.
     *
     * @param mixed $var the variable to cast
     *
     * @return int the variable as integer
     */
    public static function toInt(mixed $var): int
    {
        return \is_int($var) ? $var : (int) $var;
    }

    /**
     * Ensure that the given variable is a string.
     *
     * @param mixed $var the variable to cast
     *
     * @return string the variable as string
     */
    public static function toString(mixed $var): string
    {
        return \is_string($var) ? $var : (string) $var;
    }

    /**
     * Creates a unicode string.
     *
     * @param string $string      the string content
     * @param bool   $ignore_case true to ignore case considerations
     *
     * @return UnicodeString the unicode string
     */
    private static function getString(string $string, bool $ignore_case): UnicodeString
    {
        return $ignore_case ? u($string)->ignoreCase() : u($string);
    }
}
