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

/**
 * Utility class.
 */
final class Utils
{
    // prevent instance creation
    private function __construct()
    {
        // no-op
    }

    /**
     * Gets the context, as array; for the given exception.
     *
     * @param \Throwable $e the exception to get the context for
     *
     * @return array{
     *     message: string,
     *     code: string|int,
     *     file: string,
     *     line: int,
     *     class: string,
     *     trace: string}
     */
    public static function getExceptionContext(\Throwable $e): array
    {
        return [
            'message' => $e->getMessage(),
            'code' => $e->getCode(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'class' => StringUtils::getShortName($e),
            'trace' => $e->getTraceAsString(),
        ];
    }

    /**
     * Groups an array by the given key.
     *
     * Any additional keys will be used for grouping the next set of sub-arrays.
     *
     * @param array<array-key, object|array> $array
     * @param string|int|(callable(mixed):string|int) $key
     * @param string|int|(callable(mixed):string|int) ...$others
     *
     * @return array the grouped array
     */
    public static function groupBy(array $array, string|int|callable $key, string|int|callable ...$others): array
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
}
