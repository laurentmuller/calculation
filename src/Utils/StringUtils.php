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

namespace App\Utils;

use Symfony\Component\String\Slugger\AsciiSlugger;
use Symfony\Component\String\UnicodeString;

/**
 * Utility class for string.
 */
final class StringUtils
{
    private const VAR_PATTERN = [
        "/array \(/" => '[',
        "/^([ ]*)\)(,?)$/m" => '$1]$2',
        "/=>[ ]?\n[ ]+\[/" => '=> [',
        "/([ ]*)(\'[^\']+\') => ([\[\'])/" => '$1$2 => $3',
    ];

    private const VAR_SEARCH = [
        '\\\\' => '\\',
        ',' => '',
    ];

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
        return self::createString($value)->ascii()->toString();
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
        return self::createString($string)->lower()->title()->toString();
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
    public static function contains(string $haystack, string $needle, bool $ignore_case = true): bool
    {
        return self::createString($haystack, $ignore_case)->containsAny($needle);
    }

    /**
     * Create a new unicode string.
     */
    public static function createString(string $string, bool $ignore_case = false): UnicodeString
    {
        $result = new UnicodeString($string);

        return $ignore_case ? $result->ignoreCase() : $result;
    }

    /**
     * Takes a JSON encoded string and converts it into a PHP value.
     *
     * @param string $value the value to decode
     * @param bool   $assoc when true, returned objects will be converted into associative arrays
     *
     * @return array|\stdClass the decoded value in appropriate PHP type
     *
     * @throws \InvalidArgumentException if the value can not be decoded
     *
     * @see StringUtils::encodeJson()
     *
     * @psalm-return ($assoc is true ? array : \stdClass)
     *
     * @psalm-suppress MixedInferredReturnType
     * @psalm-suppress MixedReturnStatement
     */
    public static function decodeJson(string $value, bool $assoc = true): array|\stdClass
    {
        try {
            return \json_decode(json: $value, associative: $assoc, flags: \JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new \InvalidArgumentException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Returns the JSON representation of a value.
     *
     * @param mixed $value the value being encoded
     * @param int   $flags a bitmask flag. The <b>JSON_THROW_ON_ERROR</b> flag is always added.
     *
     * @return string a JSON encoded string
     *
     * @throws \InvalidArgumentException if the value can not be encoded
     *
     * @see StringUtils::decodeJson()
     */
    public static function encodeJson(mixed $value, int $flags = 0): string
    {
        try {
            return (string) \json_encode($value, $flags | \JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new \InvalidArgumentException($e->getMessage(), $e->getCode(), $e);
        }
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
    public static function endWith(string $haystack, string $needle, bool $ignore_case = true): bool
    {
        return self::createString($haystack, $ignore_case)->endsWith($needle);
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
            $export = self::replace(self::VAR_SEARCH, $export);

            return self::pregReplace(self::VAR_PATTERN, $export);
        } catch (\Exception) {
            return (string) $expression;
        }
    }

    /**
     * Gets the short class name of the given variable.
     *
     * @template T of object
     *
     * @param T|class-string<T> $objectOrClass either a string containing the name of
     *                                         the class to reflect, or an object
     *
     * @return string the short name or null if the variable is null
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
     * Returns if the given string is not null nor is empty.
     */
    public static function isString(?string $str): bool
    {
        return null !== $str && '' !== $str;
    }

    /**
     * Replace all occurrences of the pattern string with the replacement string.
     *
     * @param array<string, string> $values  an array where key is the pattern and value is the replacement term
     * @param string|string[]       $subject the string or array being searched and replaced on
     *
     * @return string|string[] returns a string or an array with the replaced values
     *
     * @phpstan-return ($subject is string ? string : string[])
     */
    public static function pregReplace(array $values, string|array $subject): string|array
    {
        return \preg_replace(\array_keys($values), \array_values($values), $subject);
    }

    /**
     * Replace all occurrences of the search string with the replacement string.
     *
     * @param array<string, string> $values  an array where key is the search term and value is the replacement term
     * @param string|string[]       $subject the string or array being searched and replaced on
     *
     * @return string|string[] returns a string or an array with the replaced values
     *
     * @phpstan-return ($subject is string ? string : string[])
     */
    public static function replace(array $values, string|array $subject): string|array
    {
        return \str_replace(\array_keys($values), \array_values($values), $subject);
    }

    /**
     * Transforms the given string into another string that only includes safe ASCII characters.
     */
    public static function slug(string $string): string
    {
        /** @psalm-var AsciiSlugger|null $slugger */
        static $slugger = null;
        if (!$slugger instanceof AsciiSlugger) {
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
    public static function startWith(string $haystack, string $needle, bool $ignore_case = true): bool
    {
        return self::createString($haystack, $ignore_case)->startsWith($needle);
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
}
