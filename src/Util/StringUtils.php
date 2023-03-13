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

use Symfony\Component\String\Slugger\AsciiSlugger;

use function Symfony\Component\String\u;

use Symfony\Component\String\UnicodeString;

/**
 * Utility class for string.
 */
final class StringUtils
{
    // prevent instance creation
    private function __construct()
    {
        // no-op
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
     * Transforms the given string into another string that only includes safe ASCII characters.
     */
    public static function slug(string $string): string
    {
        /** @psalm-var AsciiSlugger|null $slugger */
        static $slugger = null;
        if (null === $slugger) {
            $slugger = new AsciiSlugger();
        }

        return $slugger->slug($string)->toString();
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
