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
    /**
     * The new line separator.
     */
    public const NEW_LINE = "\n";

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
        return self::unicode($value)->ascii()->toString();
    }

    /**
     * Capitalizes a string.
     *
     * The first character will be uppercase, all others lowercase:
     * <pre>
     * 'my first Car' = 'My first car'
     * </pre>
     *
     * @param string $string the string to capitalize
     *
     * @return string the capitalized string
     */
    public static function capitalize(string $string): string
    {
        return \ucfirst(\strtolower($string));
    }

    /**
     * Takes an encoded JSON string and converts it into a PHP value.
     *
     * @param string $value the value to decode
     * @param bool   $assoc when true, returned objects will be converted into associative arrays
     *
     * @return array|\stdClass the decoded value in the appropriate PHP type
     *
     * @throws \InvalidArgumentException if the value cannot be decoded
     *
     * @see StringUtils::encodeJson()
     *
     * @psalm-return ($assoc is true ? array : \stdClass)
     */
    public static function decodeJson(string $value, bool $assoc = true, int $flags = 0): array|\stdClass
    {
        try {
            /** @psalm-var array|\stdClass */
            return \json_decode(json: $value, associative: $assoc, flags: $flags | \JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new \InvalidArgumentException('Unable to decode value.', $e->getCode(), $e);
        }
    }

    /**
     * Returns the JSON representation of a value.
     *
     * @param mixed $value the value being encoded
     * @param int   $flags a bitmask flag. The <b>JSON_THROW_ON_ERROR</b> flag is always added.
     *
     * @return string an encoded JSON string
     *
     * @throws \InvalidArgumentException if the value cannot be encoded
     *
     * @see StringUtils::decodeJson()
     */
    public static function encodeJson(mixed $value, int $flags = 0): string
    {
        try {
            /** @psalm-var non-empty-string */
            return \json_encode($value, $flags | \JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            $message = \sprintf("Unable to encode value '%s'.", \get_debug_type($value));
            throw new \InvalidArgumentException($message, $e->getCode(), $e);
        }
    }

    /**
     * Returns a value indicating if the given strings are equal, ignoring case consideration.
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
        $export = \var_export($expression, true);
        $export = self::replace(self::VAR_SEARCH, $export);

        return self::pregReplaceAll(self::VAR_PATTERN, $export);
    }

    /**
     * Gets the short class name of the given variable.
     *
     * @template T of object
     *
     * @param object|string $objectOrClass either a string containing the name of
     *                                     the class to reflect, or an object
     *
     * @return string the short name
     *
     * @psalm-param T|class-string<T> $objectOrClass
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
     *
     * @psalm-assert-if-true non-empty-string $str
     */
    public static function isString(?string $str): bool
    {
        return null !== $str && '' !== $str;
    }

    /**
     * Perform a regular expression match.
     *
     * @param string $pattern the pattern to search for
     * @param string $subject the input string
     *
     * @param-out string[] $matches if matches are provided, then they are filled with the search results.
     *
     * @param int $flags  can be a combination of flags
     * @param int $offset to specify the place from which to start the search
     *
     * @return bool <code>true</code> if the pattern matches the given subject
     *
     * @psalm-param non-empty-string $pattern
     * @psalm-param int-mask<256, 512> $flags
     */
    public static function pregMatch(string $pattern, string $subject, ?array &$matches = null, int $flags = 0, int $offset = 0): bool
    {
        /** @psalm-suppress ReferenceConstraintViolation */
        return 1 === \preg_match($pattern, $subject, $matches, $flags, $offset); // @phpstan-ignore paramOut.type
    }

    /**
     * Perform a global regular expression match.
     *
     * @param string $pattern the pattern to search for
     * @param string $subject the input string
     *
     * @param-out array<int, array> $matches if matches are provided, then they ar filled with the search results.
     *
     * @param int $flags  can be a combination of flags
     * @param int $offset to specify the place from which to start the search
     *
     * @return bool <code>true</code> if the pattern matches the given subject
     *
     * @psalm-param non-empty-string $pattern
     * @psalm-param int-mask<1, 2, 256, 512> $flags
     */
    public static function pregMatchAll(string $pattern, string $subject, ?array &$matches = null, int $flags = 0, int $offset = 0): bool
    {
        /** @phpstan-ignore paramOut.type */
        $result = \preg_match_all($pattern, $subject, $matches, $flags, $offset);

        /** @psalm-suppress ReferenceConstraintViolation */
        return \is_int($result) && $result > 0;
    }

    /**
     * Perform a regular expression search and replace.
     *
     * @param string $pattern     the pattern to search for
     * @param string $replacement the string to replace
     * @param string $subject     the string being searched and replaced on
     * @param int    $limit       the maximum possible replacements for the pattern. Defaults to -1 (no limit).
     *
     * @psalm-param non-empty-string $pattern
     */
    public static function pregReplace(string $pattern, string $replacement, string $subject, int $limit = -1): string
    {
        return (string) \preg_replace($pattern, $replacement, $subject, $limit);
    }

    /**
     * Replace all occurrences of the pattern string with the replacement string.
     *
     * @param array<string, string> $values  an array where key is the pattern, and value is the replacement term
     * @param string|string[]       $subject the string or array being searched and replaced on
     * @param int                   $limit   the maximum possible replacements for each pattern in each subject string.
     *                                       Defaults to -1 (no limit).
     *
     * @return string|string[] returns a string or an array with the replaced values
     *
     * @psalm-param non-empty-array<non-empty-string, string> $values
     *
     * @psalm-return ($subject is string ? string : string[])
     */
    public static function pregReplaceAll(array $values, string|array $subject, int $limit = -1): string|array
    {
        /** @psalm-var string|string[] */
        return \preg_replace(\array_keys($values), \array_values($values), $subject, $limit); // @phpstan-ignore varTag.type
    }

    /**
     * Replace all occurrences of the search string with the replacement string.
     *
     * @param array<string, string> $values  an array where key is the search term, and value is the replacement term
     * @param string|string[]       $subject the string or array being searched and replaced on
     *
     * @return string|string[] returns a string or an array with the replaced values
     *
     * @psalm-return ($subject is string ? string : string[])
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
     * Tests if a string starts within the given prefix.
     *
     * <b>NB:</b> If the prefix argument is empty, this function returns false.
     *
     * @param string $string     the string to search in
     * @param string $prefix     the string prefix to search for
     * @param bool   $ignoreCase <code>true</code> for case-insensitive; <code>false</code> for case-sensitive
     *
     * @return bool true if the string starts within the prefix
     */
    public static function startWith(string $string, string $prefix, bool $ignoreCase = true): bool
    {
        if ('' === $prefix) {
            return false;
        }
        if ($ignoreCase) {
            return 0 === \stripos($string, $prefix);
        }

        return \str_starts_with($string, $prefix);
    }

    /**
     * Trim the given string.
     *
     * @param ?string $str the value to trim
     *
     * @return string|null the trimmed string or null if empty after trimmed
     */
    public static function trim(?string $str): ?string
    {
        return null === $str || ($str = \trim($str)) === '' ? null : $str;
    }

    /**
     * Create a new Unicode string.
     *
     * @param bool $ignoreCase <code>true</code> for case-insensitive; <code>false</code> for case-sensitive
     */
    public static function unicode(string $string, bool $ignoreCase = false): UnicodeString
    {
        if ($ignoreCase) {
            return (new UnicodeString($string))->ignoreCase();
        }

        return new UnicodeString($string);
    }
}
